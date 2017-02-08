<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Model\Groups;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use PHPUnit_Framework_TestSuite;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class SplittingModeListener
 */
class SplittingModeListener extends \PHPUnit_Framework_BaseTestListener
{

    private $splitCounterPathname = 'cache/.split.done.php';

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        //The first will split tests for others!
        $lockHandler = new LockHandler('split.lock');
        if ($lockHandler->lock(true)) {
            if (!file_exists($this->splitCounterPathname)) {
                $this->initSplitFile();
                $groups = $this->getGroup()->reset();

                foreach ($this->getTestCases($suite) as $testCase) {
                    $groups->addTestCase($testCase);
                }
                $groups->save();
                $lockHandler->release();
            } else {
                $groups = $this->getGroup();
            }
            $this->splitDone();
            $this->displayHelp($groups);
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
    private function displayHelp(Groups $groups)
    {
        foreach ($groups->toArray() as $id => $group) {
            echo sprintf(
                    '> Group %s : %s tests (Estimated time: %s sec)',
                    $id,
                    $group->count(),
                    $group->getEstimatedTimeInSec()
                ).PHP_EOL;
        }
    }

    /**
     * @return Groups
     */
    private function getGroup()
    {
        return (new Groups(SplitStep::getTotalJobs(), new StatsStorage()));
    }

    /**
     * @return $this
     */
    private function initSplitFile()
    {
        $this->updateSplitFile(0);

        return $this;
    }

    /**
     * @param int $count
     *
     * @return $this
     */
    private function updateSplitFile($count)
    {
        file_put_contents(
            $this->splitCounterPathname,
            '<?php return '.$count.';'
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function splitDone()
    {
        $initDone = include($this->splitCounterPathname);
        ++$initDone;
        $this->updateSplitFile($initDone);

        if (SplitStep::getTotalJobs() === $initDone) {
            $this->allSplitsDone();
        }

        return $this;
    }

    /**
     *
     */
    private function allSplitsDone()
    {
        unlink($this->splitCounterPathname);
    }
}