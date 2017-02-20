<?php

namespace Nikoms\PhpUnitSplitter\Model;

use Nikoms\PhpUnitSplitter\Stats;
use Nikoms\PhpUnitSplitter\TestCaseId;

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
     * @var Stats
     */
    private $stats;

    /**
     * Groups constructor.
     *
     * @param int          $numberOfGroups
     * @param Stats $stats
     */
    public function __construct($numberOfGroups, Stats $stats)
    {
        for ($i = 0; $i < $numberOfGroups; $i++) {
            $this->groups[] = new Group($i);
        }

        $this->stats = $stats;
    }

    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     *
     * @return $this
     */
    public function addInBestGroup(\PHPUnit_Framework_TestCase $testCase)
    {
        $testCaseId = TestCaseId::fromTestCase($testCase);
        $this->stats->assureTestIsStored($testCaseId);

        $this->getFasterGroup()->addToRun($testCaseId, $this->stats->getAverage($testCaseId));

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
            $group->delete();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->stats->save();

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