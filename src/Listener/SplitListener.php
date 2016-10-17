<?php
namespace Nikoms\PhpUnitSplitter\Listener;

use Nikoms\PhpUnitSplitter\Repository\GroupedTestCaseRepository;
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

    /**
     * @var int
     */
    private $jobs;

    /**
     * @var int
     */
    private $runningGroup;

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
                if ($key === 'split-jobs') {
                    $this->jobs = (int)$value;
                    continue;
                }

                if ($key === 'split-running-group') {
                    $this->runningGroup = (int)$value;
                    continue;
                }
            }
        }
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

        if ($this->jobs > 0) {
            $groups = array_fill(
                1,
                $this->jobs,
                ['tests' => [], 'totalAverages' => 0, 'filter' => '^EmptyFilter\Class::function$']
            );

            $testCases = $this->getSuiteTestCases($suite);
            $chronos = $this->testCaseRepository->getAllChronos();
            foreach ($testCases as $testCase) {
                $groups = $this->putInBestGroup($groups, $chronos, $testCase);
            }

            foreach ($groups as $id => $group) {
                $numberOfTests = count($group['tests']);
                echo '> Group '.$id.' : '.$group['totalAverages'].' ('.$numberOfTests.' tests)'.PHP_EOL;
                $groupRepository = new GroupedTestCaseRepository($id);
                $groupRepository->createDatabase($group['filter'], $numberOfTests);
                $groupRepository->close();
            }

            $this->doNotRunTests($suite);

            return;
        }

        if ($this->runningGroup !== null) {
            $groupRepository = new GroupedTestCaseRepository($this->runningGroup);

            //ERROR: This create a too big regex: preg_match(): Compilation failed: regular expression is too large at offset
            $filterFactory = new \PHPUnit_Runner_Filter_Factory();
            $filterFactory->addFilter(
                new \ReflectionClass('PHPUnit_Runner_Filter_Test'),
                $groupRepository->getFilter()
            );
            $suite->injectFilter($filterFactory);

            //Maybe this is another kind of doing it... (it does not work but it's an idea)

//            $filters = explode('|', $groupRepository->getFilter());
//            foreach ($filters as $filter) {
//                $filterFactory->addFilter(
//                    new \ReflectionClass('PHPUnit_Runner_Filter_Test'),
//                    $filter
//                );
//            }

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
        $groups[0]['totalAverages'] += $average;
        $groups[0]['filter'] .= '|'.$testCase->getFilter('#');

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