<?php
namespace Nikoms\PhpUnitSplitter\Listener;

use Nikoms\PhpUnitSplitter\TestCase\TestCase;
use Nikoms\PhpUnitSplitter\Repository\TestCaseRepository;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

class SplitListener extends \PHPUnit_Framework_BaseTestListener
{
    const MODE_CLEAN_DELETED_TESTS = 1; //Remove tests that does not exist anymore
    const MODE_ADD_NEW_TESTS = 2; //Add new tests
    const MODE_SHOW_GROUPS = 4; //Display groups (to make the grep)
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

    /**
     * @var string
     */
    private $mode;

    public function __construct()
    {
        $this->testCaseRepository = new TestCaseRepository();
        $this->chronos = new \SplObjectStorage();
        $options = getopt(
            'd:'
        );
        if (isset($options['d'])) {
            $options['d'] = (array) $options['d'];
            foreach ($options['d'] as $option) {
                list($key, $value) = explode('=', $option);
                echo $key;
                if ($key === 'split-mode') {
                    $this->mode = (int)$value;
                    break;
                }
            }
        }
    }

    /**
     * @return int
     */
    private function isViewMode()
    {
        return (bool) ($this->mode & self::MODE_SHOW_GROUPS);
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

        if ($this->isViewMode()) {
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