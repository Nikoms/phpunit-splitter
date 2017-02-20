<?php

namespace Nikoms\PhpUnitSplitter\Job;

use Nikoms\PhpUnitSplitter\Model\Group;
use Nikoms\PhpUnitSplitter\Splitter;
use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\TestCaseId;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

/**
 * Class RunJob
 */
class RunJob
{
    /**
     * @var Group
     */
    private $currentGroup;

    /**
     * @param int                         $groupId
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function filterTestsOfGroup(\PHPUnit_Framework_TestSuite $suite, $groupId)
    {
        Splitter::dispatch(Splitter::BEFORE_RUN);
        $this->initCurrentGroup($groupId);
        $testsOfCurrentGroup = array_filter(
            $this->getTestCases($suite),
            function (\PHPUnit_Framework_TestCase $testCase) {
                $testCaseId = TestCaseId::fromTestCase($testCase);

                return $this->currentGroup->has($testCaseId);
            }
        );

        $suite->setTests(array_values($testsOfCurrentGroup));
    }

    /**
     * @param int $groupId
     */
    private function initCurrentGroup($groupId)
    {
        $this->currentGroup = new Group($groupId);
        echo sprintf(
            'Running group "%s". Estimated time : %s sec.',
            $this->currentGroup->getId(),
            $this->currentGroup->getEstimatedTimeInSec()
        );
        echo PHP_EOL.PHP_EOL;
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

    public function flushExecutionTimes()
    {
        $this->currentGroup->save();
        Splitter::dispatch(Splitter::AFTER_RUN);
    }

    /**
     * @param PHPUnit_Framework_Test $test
     * @param float                  $time
     */
    public function persistExecutionTime(PHPUnit_Framework_Test $test, $time)
    {
        if (!$test instanceof \PHPUnit_Framework_TestCase) {
            return;
        }
        $this->currentGroup->set(TestCaseId::fromTestCase($test), $time);
    }

}