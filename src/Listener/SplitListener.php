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
    private $splitInJobs;

    /**
     * @var int
     */
    private $runningGroup;

    /**
     * @var int
     */
    private $gatheringData;

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
                    $this->splitInJobs = (int)$value;
                    continue;
                }

                if ($key === 'split-running-group') {
                    $this->runningGroup = (int)$value;
                    continue;
                }

                if ($key === 'split-gathering-data') {
                    $this->gatheringData = (int)$value;
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

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->suiteDeepLevel++;
        if ($this->gatheringData) {
            $this->doNotRunTests($suite);

            return;
        }
        $testCases = $this->getSuiteTestCases($suite);

        if ($this->splitInJobs > 0) {
            $groups = array_fill(
                0,
                $this->splitInJobs,
                ['tests' => [], 'totalAverages' => 0, 'filter' => '^EmptyFilter\Class::function$']
            );

            $chronos = $this->testCaseRepository->getAllChronos();
            //TODO: Optimize this loop (takes 700 ms for 10.000)
            foreach ($testCases as $testCase) {
                $groups = $this->putInBestGroup($groups, $chronos, $testCase);
            }

            //TODO: Optimize This loop (takes 600 ms for 10.000)
            $this->testCaseRepository->beginTransaction();
            foreach ($groups as $id => $group) {
                $numberOfTests = count($group['tests']);
                echo '> Group '.$id.' : '.$group['totalAverages'].' ('.$numberOfTests.' tests)'.PHP_EOL;
                $groupRepository = new GroupedTestCaseRepository($id);
                $groupRepository->resetDatabase();
                $groupRepository->beginTransaction();
                foreach ($group['tests'] as $testCase) {
                    $this->testCaseRepository->assureTestIsStored($testCase->getId());
                    $groupRepository->insert($testCase->getId(), 0);
                }
                $groupRepository->commit();
                $groupRepository->close();
            }
            $this->testCaseRepository->commit();

            $this->doNotRunTests($suite);

            return;
        }

        if ($this->runningGroup !== null) {
            $groupRepository = new GroupedTestCaseRepository($this->runningGroup);
            $testIds = $groupRepository->getTestIds();
            $groupRepository->close();

            $filteredTest = array_filter(
                $testCases,
                function (TestCase $testCase) use ($testIds) {
                    return in_array($testCase->getId(), $testIds);
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

        return $groups;
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->suiteDeepLevel--;

        if ($this->runningGroup !== null && $this->suiteDeepLevel === 0) {
            $this->updateTimes();
        }
        if ($this->gatheringData) {
            $testCaseRepository = new TestCaseRepository();
            $testCaseRepository->beginTransaction();
            for ($i = 0; $i < $this->gatheringData; $i++) {
                $repo = new GroupedTestCaseRepository($i);
                $times = $repo->getTimes();
                echo sprintf('Gathering %s tests from group %s'.PHP_EOL, count($times), $i);
                foreach ($times as $id => $test) {
                    $testCaseRepository->updateTime($id, $test['executionTime']);
                }
                $repo->resetDatabase()->drop();
            }
            $testCaseRepository->commit();
        }
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    private function doNotRunTests(PHPUnit_Framework_TestSuite $suite)
    {
        $suite->setTests([]);
    }

    /**
     *
     */
    private function updateTimes()
    {
        $groupRepository = new GroupedTestCaseRepository($this->runningGroup);
        $groupRepository->beginTransaction();
        foreach ($this->chronos as $testCase) {
            $time = $this->chronos[$testCase];
            $groupRepository->updateTime($testCase->getId(), $time);
        }
        $groupRepository->commit();
        $groupRepository->close();
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