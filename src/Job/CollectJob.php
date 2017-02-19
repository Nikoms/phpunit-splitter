<?php

namespace Nikoms\PhpUnitSplitter\Job;

use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\Lock\JobLocker;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\Splitter;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class CollectJob
 */
class CollectJob
{
    /**
     *
     */
    public function recalculateAverage()
    {
        $lockHandler = new LockHandler('collect', 'cache');
        $lockMode = new JobLocker(Splitter::getTotalProcesses(), 'collect');

        //Only one can update the stats at a time
        if ($lockHandler->lock(true)) {
            $this->storeCurrentGroupExecutionTimes();
            $lockMode->processDone(Splitter::getCurrent());
            $lockHandler->release();
        }
    }

    /**
     *
     */
    private function storeCurrentGroupExecutionTimes()
    {
        $statsStorage = new StatsStorage();
        $groupExecutions = new GroupExecutions(Splitter::getCurrent());

        $times = $groupExecutions->getExecutionsTime();
        foreach ($times as $id => $executionTime) {
            $statsStorage->updateTime($id, $executionTime);
        }
        $groupExecutions->delete();
        $statsStorage->save();
    }
}