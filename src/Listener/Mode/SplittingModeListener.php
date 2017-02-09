<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Model\Groups;
use Nikoms\PhpUnitSplitter\Storage\LockMode;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use PHPUnit_Framework_TestSuite;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class SplittingModeListener
 */
class SplittingModeListener
{

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        //The first will split tests for others!
        $lockHandler = new LockHandler('split', 'cache');
        $lockMode = new LockMode(SplitStep::getTotalJobs(), 'cache/.split.php');

        //Only the first will create groups, others will wait for it :)
        if ($lockHandler->lock(true)) {
            $isFirst = $lockMode->isFirst();
            if ($isFirst) {
                SplitStep::dispatch(SplitStep::EVENT_BEFORE_SPLIT);
                $groups = (new Groups(SplitStep::getTotalJobs(), new StatsStorage()))->reset();

                foreach ($this->getTestCases($suite) as $testCase) {
                    $groups->addInBestGroup($testCase);
                }
                $groups->save();
                SplitStep::dispatch(SplitStep::EVENT_AFTER_SPLIT);
                $this->displayGroups($groups);
            } else {
                echo sprintf('Running group "%s"', SplitStep::getCurrent()).PHP_EOL.PHP_EOL;
            }
            $lockMode->done(SplitStep::getCurrent());
            $lockHandler->release();
        }
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     *
     * @return \PHPUnit_Framework_TestCase[]
     */
    private function getTestCases(PHPUnit_Framework_TestSuite $suite)
    {
        $testCases = [];
        foreach ($suite as $test) {
            if ($test instanceof PHPUnit_Framework_TestSuite) {
                $testCases = array_merge($testCases, $this->getTestCases($test));
            } else {
                $testCases[] = $test;
            }
        }

        return $testCases;
    }

    /**
     * @param Groups $groups
     */
    private function displayGroups(Groups $groups)
    {
        foreach ($groups->toArray() as $id => $group) {
            echo sprintf(
                    '> Group %s : %s tests (Estimated time: %s sec)',
                    $id,
                    $group->count(),
                    $group->getEstimatedTimeInSec()
                ).PHP_EOL.PHP_EOL;
        }
    }
}