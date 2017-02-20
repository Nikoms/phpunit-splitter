<?php

namespace Nikoms\PhpUnitSplitter\Model;

/**
 * Class Group
 */
class Group
{
    const TIME_PRECISION = 1000000;

    /**
     * @var string
     */
    private $pathname;

    /**
     * @var array
     */
    private $executionTimes = [];

    /**
     * @var int
     */
    private $estimatedTime = 0;

    /**
     * Group constructor.
     *
     * @param int $groupId
     */
    public function __construct($groupId)
    {
        $this->pathname = 'cache/phpunit-split-'.$groupId.'.php';

        if (file_exists($this->pathname)) {
            $storage = include($this->pathname);
            $this->executionTimes = $storage['executionTimes'];
            $this->estimatedTime = $storage['estimatedTime'];
        }
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
        if (file_exists($this->pathname)) {
            unlink($this->pathname);
        }
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
        $storage = [
            'executionTimes' => $this->executionTimes,
            'estimatedTime' => $this->estimatedTime,
        ];

        file_put_contents($this->pathname, '<?php return '.var_export($storage, true).';');
        //When docker run the command
        @chmod($this->pathname, 0777);

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
        return round($this->getEstimatedTime() / Group::TIME_PRECISION, 2);
    }
}