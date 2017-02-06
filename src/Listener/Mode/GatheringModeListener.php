<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use PHPUnit_Framework_TestSuite;

/**
 * Class GatheringModeListener
 */
class GatheringModeListener extends \PHPUnit_Framework_BaseTestListener
{
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->doNotRunTests($suite);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    private function doNotRunTests(PHPUnit_Framework_TestSuite $suite)
    {
        $suite->setTests([]);
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $numberOfJobs = SplitStep::getValue();
        $statsStorage = new StatsStorage();
        for ($i = 0; $i < $numberOfJobs; $i++) {
            $groupExecutions = new GroupExecutions($i);
            $times = $groupExecutions->getExecutionTimes();
            echo sprintf('Gathering %s tests from group %s'.PHP_EOL, count($times), $i);
            foreach ($times as $id => $executionTime) {
                $statsStorage->updateTime($id, $executionTime);
            }
            $groupExecutions->delete();
        }
        $statsStorage->save();
    }


}