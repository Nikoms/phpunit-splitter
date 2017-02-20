<?php

namespace Nikoms\PhpUnitSplitter\Job;

use Nikoms\PhpUnitSplitter\Model\Groups;
use Nikoms\PhpUnitSplitter\Lock\JobLocker;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\Splitter;
use PHPUnit_Framework_TestSuite;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class SplitJob
 */
class SplitJob
{

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function splitSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        //The first will split tests for others!
        $lockHandler = new LockHandler('split', 'cache');
        $lockMode = new JobLocker(Splitter::getTotalGroups(), 'split');

        //Only the first will create groups, others will wait for it :)
        if ($lockHandler->lock(true)) {
            $isFirst = $lockMode->isFirst();
            if ($isFirst) {
                Splitter::dispatch(Splitter::BEFORE_SPLIT);
                $groups = (new Groups(Splitter::getTotalGroups(), new StatsStorage()))->reset();

                foreach ($this->getTestCases($suite) as $testCase) {
                    $groups->addInBestGroup($testCase);
                }
                $groups->save();
                Splitter::dispatch(Splitter::AFTER_SPLIT);
                $this->displayGroups($groups);
            } else {
                echo sprintf('Running group "%s"', Splitter::getCurrentGroup()).PHP_EOL.PHP_EOL;
            }
            $lockMode->groupDone(Splitter::getCurrentGroup());
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