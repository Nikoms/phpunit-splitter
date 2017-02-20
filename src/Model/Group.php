<?php

namespace Nikoms\PhpUnitSplitter\Model;

use Nikoms\PhpUnitSplitter\Storage\FileStorage;

/**
 * Class Group
 */
class Group
{
    const TIME_PRECISION = 1000000;

    /**
     * @var array
     */
    private $executionTimes = [];

    /**
     * @var int
     */
    private $estimatedTime = 0;
    /**
     * @var int
     */
    private $id;

    /**
     * @var FileStorage
     */
    private $storage;

    /**
     * Group constructor.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->storage = new FileStorage('cache/phpunit-split-'.$this->id.'.php');

        $model = $this->storage->get();
        if (!empty($model)) {
            $this->executionTimes = $model['executionTimes'];
            $this->estimatedTime = $model['estimatedTime'];
        }

    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return int
     */
    public function count()
    {
        return count($this->executionTimes);
    }

    /**
     * @param string $testCaseId
     * @param int    $time
     *
     * @return $this
     */
    public function set($testCaseId, $time)
    {
        $this->executionTimes[$testCaseId] = round($time * self::TIME_PRECISION);

        return $this;
    }

    /**
     * @param string $testCaseId
     *
     * @return bool
     */
    public function has($testCaseId)
    {
        return isset($this->executionTimes[$testCaseId]);
    }

    /**
     * @param string $testCaseId
     * @param int    $estimatedTime
     */
    public function addToRun($testCaseId, $estimatedTime)
    {
        $this->executionTimes[$testCaseId] = 0;
        $this->estimatedTime += $estimatedTime;
    }

    /**
     *
     */
    public function delete()
    {
        $this->executionTimes = [];
        $this->storage->delete();
    }

    /**
     * @return array
     */
    public function getExecutionsTimes()
    {
        return $this->executionTimes;
    }

    /**
     *
     */
    public function save()
    {
        $this->saveInFile();
    }

    /**
     * @return $this
     */
    private function saveInFile()
    {
        $model = [
            'executionTimes' => $this->executionTimes,
            'estimatedTime' => $this->estimatedTime,
        ];

        $this->storage->save($model);

        return $this;
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
        return round($this->getEstimatedTime() / self::TIME_PRECISION, 2);
    }
}