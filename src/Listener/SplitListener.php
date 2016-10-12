<?php
namespace Nikoms\PhpUnitSplitter\Listener;

use Nikoms\PhpUnitSplitter\TestCase\TestCase;
use Nikoms\PhpUnitSplitter\Repository\TestCaseRepository;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

class SplitListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * @var \SplObjectStorage | TestCase[]
     */
    private $chronos;

    /**
     * @var TestCaseRepository
     */
    private $testCaseRepository;

    /**
     * @var int
     */
    private $suiteDeepLevel = 0;

    public function __construct()
    {
        $this->testCaseRepository = new TestCaseRepository();
        $this->chronos = new \SplObjectStorage();
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        if (!$test instanceof \PHPUnit_Framework_TestCase) {
            return;
        }

        $testCase = new TestCase($test);
        $this->chronos->attach($testCase, round($time * 1000000));
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     *
     * @return TestCase[]
     */
    private function getSuiteTestCases(PHPUnit_Framework_TestSuite $suite)
    {
        $testCases = [];
        foreach ($suite->getGroupDetails() as $groupSuites) {
            foreach ($groupSuites as $k => $test) {
                if ($test instanceof PHPUnit_Framework_TestSuite) {
                    $testCases = array_merge($testCases, $this->getSuiteTestCases($test));
                } else {
                    $testCases[] = new TestCase($test);
                }
            }
        }

        return $testCases;
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->suiteDeepLevel++;

        //TODO: This must come from the command line :)
        $onlySee = true;
        if ($onlySee) {
            $testCases = $this->getSuiteTestCases($suite);
            foreach ($testCases as $testCase) {
                echo 'test: '.$testCase->getId().PHP_EOL;
            }
            $this->doNotRunTests($suite);
        }
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->suiteDeepLevel--;

        if ($this->suiteDeepLevel === 0) {
            $this->updateAverages();
        }
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    private function doNotRunTests(PHPUnit_Framework_TestSuite $suite)
    {
        $filterFactory = new \PHPUnit_Runner_Filter_Factory();
        //This filter will remove all tests to be executed
        $filterFactory->addFilter(
            new \ReflectionClass('PHPUnit_Runner_Filter_Test'),
            'EmptyFilter\Class::function'
        );
        $suite->injectFilter($filterFactory);
    }

    private function updateAverages()
    {
        $this->testCaseRepository->beginTransaction();
        foreach ($this->chronos as $testCase) {
            $time = $this->chronos[$testCase];
            $this->testCaseRepository->updateTime($testCase, $time);
        }
        $this->testCaseRepository->commit();
    }


}