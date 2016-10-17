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
    const MODE_INIT_GROUPS = 4; //Init groups before testing in parallel
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
     * @var int
     */
    private $mode;

    /**
     * @var int
     */
    private $jobs = 1;

    public function __construct()
    {
        $this->testCaseRepository = new TestCaseRepository();
        $this->chronos = new \SplObjectStorage();
        $options = getopt(
            'd:'
        );
        if (isset($options['d'])) {
            $options['d'] = (array)$options['d'];
            foreach ($options['d'] as $option) {
                list($key, $value) = explode('=', $option);
                if ($key === 'split-mode') {
                    $this->mode = (int)$value;
                    continue;
                }
                if ($key === 'split-jobs') {
                    $this->jobs = (int)$value;
                    continue;
                }

            }
        }
    }

    /**
     * @return int
     */
    private function isInitMode()
    {
        return (bool)($this->mode & self::MODE_INIT_GROUPS);
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
                    $testCase = new TestCase($test);
                    $testCases[$testCase->getId()] = $testCase;
                }
            }
        }

        return $testCases;
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->suiteDeepLevel++;

        $groups = array_fill(1, $this->jobs, ['tests' => [], 'totalAverages' => 0]);

        if ($this->isInitMode()) {
            $testCases = $this->getSuiteTestCases($suite);
            $chronos = $this->testCaseRepository->getAllChronos();
            foreach ($testCases as $testCase) {
                $groups = $this->putInBestGroup($groups, $chronos, $testCase);
            }
            $this->doNotRunTests($suite);
        }

        foreach($groups as $id => $group){
            echo '> Group '.$id.' : '.$group['totalAverages'].' ('.count($group['tests']).' tests)'.PHP_EOL;
        }
    }

    /**
     * @param array    $groups
     * @param array    $chronos
     * @param TestCase $testCase
     *
     * @return array
     */
    private function putInBestGroup(array $groups, array $chronos, TestCase $testCase)
    {
        $groups = $this->orderGroups($groups);

        $average = isset($chronos[$testCase->getId()]) ? $chronos[$testCase->getId()]['average'] : 0;
        $groups[0]['tests'][] = $testCase;
        $groups[0]['totalAverages']+= $average;

        return $groups;
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

    /**
     * @param array $groups
     *
     * @return array
     */
    private function orderGroups(array $groups)
    {
        usort(
            $groups,
            function ($group1, $group2) {
                if ($group1['totalAverages'] === $group2['totalAverages']) {
                    if (count($group1['tests']) === count($group2['tests'])) {
                        return 0;
                    }

                    return (count($group1['tests']) < count($group2['tests'])) ? -1 : 1;
                }

                return ($group1['totalAverages'] < $group2['totalAverages']) ? -1 : 1;
            }
        );

        return $groups;
    }


}