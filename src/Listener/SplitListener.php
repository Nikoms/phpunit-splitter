<?php
namespace Nikoms\PhpUnitSplitter\Listener;

use Nikoms\PhpUnitSplitter\Job\CollectJob;
use Nikoms\PhpUnitSplitter\Job\RunJob;
use Nikoms\PhpUnitSplitter\Job\SplitJob;
use Nikoms\PhpUnitSplitter\Splitter;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

/**
 * Class SplitListener
 */
class SplitListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * @var CollectJob
     */
    private $collectJob;

    /**
     * @var RunJob
     */
    private $runJob;

    /**
     * @var SplitJob
     */
    private $splitJob;

    /**
     * SplitListener constructor.
     */
    public function __construct()
    {
        $this->splitJob = new SplitJob(Splitter::getTotalGroups());
        $this->runJob = new RunJob();
        $this->collectJob = new CollectJob(Splitter::getTotalGroups());
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->splitJob->splitSuite($suite, Splitter::getCurrentGroupId());
        $this->runJob->filterTestsOfGroup($suite, Splitter::getCurrentGroupId());
    }

    /**
     * @param PHPUnit_Framework_Test $test
     * @param float                  $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $this->runJob->persistExecutionTime($test, $time);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->runJob->flushExecutionTimes();
        $this->collectJob->recalculateAverage(Splitter::getCurrentGroupId());
    }
}