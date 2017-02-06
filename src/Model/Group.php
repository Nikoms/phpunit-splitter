<?php

namespace Nikoms\PhpUnitSplitter\Model;

use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;

/**
 * Class Group
 */
class Group
{
    const TIME_PRECISION = 1000000;

    /**
     * @var GroupExecutions
     */
    private $groupExecutions;

    /**
     * @var int
     */
    private $estimatedTime;

    /**
     * @var string
     */
    private $filter;

    /**
     * Group constructor.
     *
     * @param GroupExecutions $groupExecutions
     * @param int             $estimatedTime
     * @param string          $filter
     */
    public function __construct(GroupExecutions $groupExecutions, $estimatedTime, $filter)
    {
        $this->groupExecutions = $groupExecutions;
        $this->estimatedTime = $estimatedTime;
        $this->filter = $filter;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->groupExecutions->count();
    }

    /**
     * @param string $testCaseId
     * @param int    $time
     *
     * @return $this
     */
    public function set($testCaseId, $time)
    {
        $this->groupExecutions->set($testCaseId, round($time * self::TIME_PRECISION));

        return $this;
    }

    /**
     * @param string $testCaseId
     * @param int    $estimatedTime
     */
    public function addToRun($testCaseId, $estimatedTime)
    {
        $this->groupExecutions->set($testCaseId, 0);
        $this->estimatedTime += $estimatedTime;

    }

    /**
     *
     */
    public function reset()
    {
        $this->groupExecutions->reset();
    }

    /**
     *
     */
    public function save()
    {
        $this->groupExecutions->save();
    }

    /**
     * @return int
     */
    public function getEstimatedTime()
    {
        return $this->estimatedTime;
    }

    /**
     * @return float
     */
    public function getEstimatedTimeInSec()
    {
        return round($this->getEstimatedTime() / Group::TIME_PRECISION, 2);
    }
}