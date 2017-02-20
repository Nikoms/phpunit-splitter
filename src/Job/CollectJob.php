<?php

namespace Nikoms\PhpUnitSplitter\Job;

use Nikoms\PhpUnitSplitter\Lock\JobLocker;
use Nikoms\PhpUnitSplitter\Model\Group;
use Nikoms\PhpUnitSplitter\Stats;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class CollectJob
 */
class CollectJob
{
    /**
     * @var int
     */
    private $totalGroups;

    /**
     * CollectJob constructor.
     *
     * @param int $totalGroups
     */
    public function __construct($totalGroups)
    {
        $this->totalGroups = $totalGroups;
    }

    /**
     * @param int $groupId
     */
    public function recalculateAverage($groupId)
    {
        $lockHandler = new LockHandler('collect', 'cache');
        $lockMode = new JobLocker($this->totalGroups, 'collect');

        //Only one can update the stats at a time
        if ($lockHandler->lock(true)) {
            $this->storeGroupExecutionTimes($groupId);
            $lockMode->groupDone($groupId);
            $lockHandler->release();
        }
    }

    /**
     * @param int $groupId
     */
    private function storeGroupExecutionTimes($groupId)
    {
        $stats = new Stats();
        $group = new Group($groupId);

        $times = $group->getExecutionsTimes();
        foreach ($times as $id => $executionTime) {
            $stats->updateTime($id, $executionTime);
        }
        $group->delete();
        $stats->save();
    }
}