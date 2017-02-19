<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Model\Group;
use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use Nikoms\PhpUnitSplitter\TestCaseId;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

/**
 * Class RunningModeListener
 */
class RunningModeListener
{
    /**
     * @var Group
     */
    private $currentGroup;

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        SplitStep::dispatch(SplitStep::EVENT_BEFORE_RUN);
        $this->initCurrentGroup();
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
     *
     */
    private function initCurrentGroup()
    {
        $this->currentGroup = new Group(new GroupExecutions(SplitStep::getCurrent()), 0);
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

    public function endTestSuite()
    {
        $this->currentGroup->save();
        SplitStep::dispatch(SplitStep::EVENT_AFTER_RUN);
    }

    /**
     * @param PHPUnit_Framework_Test $test
     * @param float                  $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        if (!$test instanceof \PHPUnit_Framework_TestCase) {
            return;
        }
        $this->currentGroup->set(TestCaseId::fromTestCase($test), $time);
    }

}