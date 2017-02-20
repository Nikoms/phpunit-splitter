<?php

namespace Nikoms\PhpUnitSplitter\Job;

use Nikoms\PhpUnitSplitter\Lock\JobLocker;
use Nikoms\PhpUnitSplitter\Model\Group;
use Nikoms\PhpUnitSplitter\Model\Groups;
use Nikoms\PhpUnitSplitter\Splitter;
use Nikoms\PhpUnitSplitter\Stats;
use PHPUnit_Framework_TestSuite;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class SplitJob
 */
class SplitJob
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
     * @param PHPUnit_Framework_TestSuite $suite
     * @param int                         $currentGroupId
     */
    public function splitSuite(\PHPUnit_Framework_TestSuite $suite, $currentGroupId)
    {
        //The first will split tests for others!
        $lockHandler = new LockHandler('split', 'cache');
        $lockMode = new JobLocker($this->totalGroups, 'split');

        //Only the first will create groups, others will wait for it :)
        if ($lockHandler->lock(true)) {
            $isFirst = $lockMode->isFirst();
            if ($isFirst) {
                Splitter::dispatch(Splitter::BEFORE_SPLIT);
                $groups = (new Groups($this->totalGroups, new Stats()))->reset();

                foreach ($this->getTestCases($suite) as $testCase) {
                    $groups->addInBestGroup($testCase);
                }
                $groups->save();
                Splitter::dispatch(Splitter::AFTER_SPLIT);
                $this->displayGroups($groups);
            }
            $lockMode->groupDone($currentGroupId);
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
        foreach ($groups->toArray() as $group) {
            echo sprintf(
                    '> Group %s : %s tests (Estimated time: %s sec)',
                    $group->getId(),
                    $group->count(),
                    $group->getEstimatedTimeInSec()
                ).PHP_EOL.PHP_EOL;
        }
    }
}