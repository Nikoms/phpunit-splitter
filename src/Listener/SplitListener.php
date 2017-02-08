<?php
namespace Nikoms\PhpUnitSplitter\Listener;

use Nikoms\PhpUnitSplitter\Listener\Mode\GatheringModeListener;
use Nikoms\PhpUnitSplitter\Listener\Mode\RunningModeListener;
use Nikoms\PhpUnitSplitter\Listener\Mode\SplittingModeListener;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

/**
 * Class SplitListener
 */
class SplitListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * @var GatheringModeListener
     */
    private $gatheringModeListener;

    /**
     * @var RunningModeListener
     */
    private $runningModeListener;

    /**
     * @var SplittingModeListener
     */
    private $splittingModeListener;

    /**
     * SplitListener constructor.
     */
    public function __construct()
    {
        $this->splittingModeListener = new SplittingModeListener();
        $this->runningModeListener = new RunningModeListener();
        $this->gatheringModeListener = new GatheringModeListener();

    }
    /**
     * @param PHPUnit_Framework_Test $test
     * @param float                  $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $this->runningModeListener->endTest($test, $time);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->splittingModeListener->startTestSuite($suite);
        $this->runningModeListener->startTestSuite($suite);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->runningModeListener->endTestSuite();
        $this->gatheringModeListener->endTestSuite();
    }
}