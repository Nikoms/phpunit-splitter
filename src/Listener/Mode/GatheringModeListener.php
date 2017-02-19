<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\Lock\JobLocker;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\Splitter;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class GatheringModeListener
 */
class GatheringModeListener
{
    /**
     *
     */
    public function endTestSuite()
    {
        $lockHandler = new LockHandler('gathering', 'cache');
        $lockMode = new JobLocker(Splitter::getTotalProcesses(), 'gathering');

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