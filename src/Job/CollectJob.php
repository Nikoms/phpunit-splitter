<?php

namespace Nikoms\PhpUnitSplitter\Job;

use Nikoms\PhpUnitSplitter\Lock\JobLocker;
use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
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
        $statsStorage = new StatsStorage();
        $groupExecutions = new GroupExecutions($groupId);

        $times = $groupExecutions->getExecutionsTime();
        foreach ($times as $id => $executionTime) {
            $statsStorage->updateTime($id, $executionTime);
        }
        $groupExecutions->delete();
        $statsStorage->save();
    }
}