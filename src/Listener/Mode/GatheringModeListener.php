<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\Storage\LockMode;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use PHPUnit_Framework_TestSuite;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class GatheringModeListener
 */
class GatheringModeListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $lockHandler = new LockHandler('split.lock');
        $lockMode = new LockMode(SplitStep::getTotalJobs(), 'cache/.gathering.php');

        //Only one can update the stats at a time
        if ($lockHandler->lock(true)) {
            if (!$lockMode->exists()) {
                $lockMode->init();
            }
            $this->storeCurrentGroupExecutionTimes();

            $lockMode->done(SplitStep::getCurrent());
            $lockHandler->release();
        }
    }

    /**
     *
     */
    private function storeCurrentGroupExecutionTimes()
    {
        $statsStorage = new StatsStorage();
        $groupExecutions = new GroupExecutions(SplitStep::getCurrent());

        $times = $groupExecutions->getExecutionsTime();
        foreach ($times as $id => $executionTime) {
            $statsStorage->updateTime($id, $executionTime);
        }
        $groupExecutions->delete();
        $statsStorage->save();
    }
}