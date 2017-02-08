<?php
namespace Nikoms\PhpUnitSplitter\Listener;

use Nikoms\PhpUnitSplitter\Listener\Mode\GatheringModeListener;
use Nikoms\PhpUnitSplitter\Listener\Mode\RunningModeListener;
use Nikoms\PhpUnitSplitter\Listener\Mode\SplittingModeListener;
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

/**
 * Class SplitListener
 */
class SplitListener extends \PHPUnit_Framework_BaseTestListener
{

    /**
     * @var \PHPUnit_Framework_BaseTestListener[]
     */
    private $listeners;

    /**
     * SplitListener constructor.
     */
    public function __construct()
    {
        $this->listeners = [
            SplitStep::GATHERING => new GatheringModeListener(),
            SplitStep::RUN => new RunningModeListener(),
            SplitStep::SPLIT => new SplittingModeListener(),
        ];

    }
    /**
     * @param PHPUnit_Framework_Test $test
     * @param float                  $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $this->listeners[SplitStep::RUN]->endTest($test, $time);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->listeners[SplitStep::SPLIT]->startTestSuite($suite);
        $this->listeners[SplitStep::RUN]->startTestSuite($suite);
    }

    /**
     * @param PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->listeners[SplitStep::RUN]->endTestSuite($suite);
    }
}