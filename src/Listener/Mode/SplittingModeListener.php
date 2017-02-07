<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Model\Groups;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use Nikoms\PhpUnitSplitter\TestCase\TestCase;
use PHPUnit_Framework_TestSuite;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class SplittingModeListener
 */
class SplittingModeListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        //File will exist if somebody already split tests ... Then we will wait :)
        $fileLock = 'cache/.split.in-progress';

        //The first to run will split tests for others!
        $lockHandler = new LockHandler('split.lock');
        if ($lockHandler->lock(true)) {
            if (!file_exists($fileLock)) {
                touch($fileLock);
                $groups = $this->getGroup()->reset();

                foreach ($this->getTestCases($suite) as $testCase) {
                    $groups->addTestCase($testCase);
                }
                $groups->save();
                $lockHandler->release();
            }else{
                $groups = $this->getGroup();
            }

            $this->displayHelp($groups);
        }
        $this->doNotRunTests($suite);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     *
     * @return TestCase[]
     */
    private function getTestCases(PHPUnit_Framework_TestSuite $suite)
    {
        $testCases = [];
        foreach ($suite as $test) {
            if ($test instanceof PHPUnit_Framework_TestSuite) {
                $testCases += $this->getTestCases($test);
            } else {
                $testCase = new TestCase($test);
                $testCases[$testCase->getId()] = $testCase;
            }
        }

        return $testCases;
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    private function doNotRunTests(PHPUnit_Framework_TestSuite $suite)
    {
        $suite->setTests([]);
    }

    /**
     * @param Groups $groups
     */
    private function displayHelp(Groups $groups)
    {
        foreach ($groups->toArray() as $id => $group) {
            echo sprintf(
                    '> Group %s : %s tests (Estimated time: %s sec)',
                    $id,
                    $group->count(),
                    $group->getEstimatedTimeInSec()
                ).PHP_EOL;
        }
    }

    /**
     * @return int
     */
    private function getTotalJobs()
    {
        return SplitStep::getValue();
    }

    /**
     * @return Groups
     */
    private function getGroup()
    {
        return (new Groups($this->getTotalJobs(), new StatsStorage()));
    }
}