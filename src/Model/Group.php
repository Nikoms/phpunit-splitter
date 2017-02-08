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
     * Group constructor.
     *
     * @param GroupExecutions $groupExecutions
     * @param int             $estimatedTime
     */
    public function __construct(GroupExecutions $groupExecutions, $estimatedTime)
    {
        $this->groupExecutions = $groupExecutions;
        $this->estimatedTime = $estimatedTime;
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
     *
     * @return bool
     */
    public function has($testCaseId)
    {
        return $this->groupExecutions->has($testCaseId);
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