<?php

namespace Nikoms\PhpUnitSplitter\Listener\Mode;

use Nikoms\PhpUnitSplitter\Model\Groups;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use Nikoms\PhpUnitSplitter\TestCase\TestCase;
use PHPUnit_Framework_TestSuite;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Class SplittingModeListener
 */
class SplittingModeListener extends \PHPUnit_Framework_BaseTestListener
{

    private $splitCounterPahtname = 'cache/.split.done.php';

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        //File will exist if somebody already split tests ... Then we will wait :)

        //The first will split tests for others!
        $lockHandler = new LockHandler('split.lock');
        if ($lockHandler->lock(true)) {
            if (!file_exists($this->splitCounterPahtname)) {
                sleep(10);
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
        $this->doNotRunTests($suite);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     *
     * @return TestCase[]
     */
    private function getTestCases(PHPUnit_Framework_TestSuite $suite)
    {
        $testCases = [];
        foreach ($suite as $test) {
            if ($test instanceof PHPUnit_Framework_TestSuite) {
                $testCases += $this->getTestCases($test);
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
    private function doNotRunTests(PHPUnit_Framework_TestSuite $suite)
    {
        $suite->setTests([]);
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
     * @return int
     */
    private function getTotalJobs()
    {
        return SplitStep::getValue();
    }

    /**
     * @return Groups
     */
    private function getGroup()
    {
        return (new Groups($this->getTotalJobs(), new StatsStorage()));
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
            $this->splitCounterPahtname,
            '<?php return '.$count.';'
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function splitDone()
    {
        $initDone = include($this->splitCounterPahtname);
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
        unlink($this->splitCounterPahtname);
    }
}