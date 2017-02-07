<?php

namespace Nikoms\PhpUnitSplitter\Model;

use Nikoms\PhpUnitSplitter\Storage\GroupExecutions;
use Nikoms\PhpUnitSplitter\Storage\StatsStorage;
use Nikoms\PhpUnitSplitter\TestCase\TestCase;

/**
 * Class Groups
 */
class Groups
{
    /**
     * @var Group[]
     */
    private $groups = [];

    /**
     * @var StatsStorage
     */
    private $statsStorage;

    /**
     * Groups constructor.
     *
     * @param int          $numberOfGroups
     * @param StatsStorage $statsStorage
     */
    public function __construct($numberOfGroups, StatsStorage $statsStorage)
    {
        for ($i = 0; $i < $numberOfGroups; $i++) {
            $this->groups[] = new Group(
                new GroupExecutions($i),
                0,
                '^EmptyFilter\Class::function$'
            );
        }

        $this->statsStorage = $statsStorage;
    }

    /**
     * @param TestCase $testCase
     *
     * @return $this
     */
    public function addTestCase(TestCase $testCase)
    {
        $testCaseId = $testCase->getId();
        $this->statsStorage->assureTestIsStored($testCaseId);

        $this->getFasterGroup()->addToRun($testCaseId, $this->statsStorage->getAverage($testCaseId));

        return $this;
    }

    /**
     * @return Group[]
     */
    public function toArray()
    {
        return $this->groups;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        foreach ($this->groups as $group) {
            $group->reset();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->statsStorage->save();

        foreach ($this->groups as $group) {
            $group->save();
        }

        return $this;
    }


    /**
     * @return Group
     */
    private function getFasterGroup()
    {
        $fasterGroup = $this->groups[0];

        foreach ($this->groups as $group) {
            if ($fasterGroup->getEstimatedTime() > $group->getEstimatedTime()) {
                $fasterGroup = $group;
                continue;
            }
            if ($fasterGroup->count() > $group->count()) {
                $fasterGroup = $group;
                continue;
            }
        }

        return $fasterGroup;
    }
}