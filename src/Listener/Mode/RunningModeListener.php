<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use Nikoms\PhpUnitSplitter\TestCase\TestCase;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

/**
 * Class RunningModeListener
 */
class RunningModeListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * @var \SplObjectStorage
     */
    private $chronos;

    /**
     * @var GroupExecutions
     */
    private $groupExecutions;

    /**
     * RunningModeListener constructor.
     */
    public function __construct()
    {
        $this->chronos = new \SplObjectStorage();
    }

    /**
     * @return GroupExecutions
     */
    private function getGroupExecutions()
    {
        if($this->groupExecutions === null){
            $this->groupExecutions = new GroupExecutions(SplitStep::getValue());
        }

        return $this->groupExecutions;
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $testCases = $this->getSuiteTestCases($suite);

        $executionTimes = $this->getGroupExecutions()->getExecutionTimes();

        $filteredTest = array_filter(
            $testCases,
            function (TestCase $testCase) use ($executionTimes) {
                return isset($executionTimes[$testCase->getId()]);
            }
        );
        $filteredPhpUnitTestCases = array_map(
            function (TestCase $testCase) {
                return $testCase->getTestCase();
            },
            $filteredTest,
            [] //This will put the key as auto-inc numeric
        );

        $suite->setTests($filteredPhpUnitTestCases);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     *
     * @return TestCase[]
     */
    private function getSuiteTestCases(PHPUnit_Framework_TestSuite $suite)
    {
        $testCases = [];
        foreach ($suite as $test) {
            if ($test instanceof PHPUnit_Framework_TestSuite) {
                $testCases = array_merge($testCases, $this->getSuiteTestCases($test));
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
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        $this->updateExecutionTimes();
    }

    /**
     *
     */
    private function updateExecutionTimes()
    {
        foreach ($this->chronos as $testCase) {
            $time = $this->chronos[$testCase];
            $this->getGroupExecutions()->set($testCase->getId(), $time);
        }
        $this->getGroupExecutions()->save();
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

        $testCase = new TestCase($test);
        $this->chronos->attach($testCase, $time);
    }

}