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

        $this->orderGroups();
        $this->groups[0]->addToRun($testCaseId, $this->statsStorage->getAverage($testCaseId));

        return $this;
    }

    /**
     * @return $this
     */
    private function orderGroups()
    {
        usort(
            $this->groups,
            function ($group1, $group2) {
                if ($group1->getEstimatedTime() === $group2->getEstimatedTime()) {
                    if ($group1->count() === $group2->count()) {
                        return 0;
                    }

                    return $group1->count() < $group2->count() ? -1 : 1;
                }

                return ($group1->getEstimatedTime() < $group2->getEstimatedTime()) ? -1 : 1;
            }
        );

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
}