<?php

namespace Nikoms\PhpUnitSplitter\Storage;

/**
 * Class GroupExecutions
 */
class GroupExecutions
{

    /**
     * @var string
     */
    private $pathname;

    /**
     * @var array
     */
    private $executionTimes = [];

    /**
     * GroupedTestCaseRepository constructor.
     *
     * @param int $groupId
     */
    public function __construct($groupId)
    {
        $this->pathname = sys_get_temp_dir().'/.cache-phpunit-split-'.$groupId.'.php';

        $this->executionTimes = file_exists($this->pathname)
            ? include($this->pathname)
            : [];
    }

    /**
     * @return GroupExecutions
     */
    public function reset()
    {
        $this->executionTimes = [];
        $this->delete();

        return $this;
    }

    /**
     * @param array $executionTimes
     *
     * @return $this
     */
    public function saveInFile(array $executionTimes)
    {
        file_put_contents($this->pathname, '<?php return '.var_export($executionTimes, true).';');

        return $this;
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->saveInFile($this->executionTimes);

        return $this;
    }

    /**
     *
     */
    public function delete()
    {
        if (file_exists($this->pathname)) {
            unlink($this->pathname);
        }
    }

    /**
     * @return array
     */
    public function getTestIds()
    {
        return array_keys($this->executionTimes);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->executionTimes);
    }

    /**
     * @return array
     */
    public function getExecutionTimes()
    {
        return $this->executionTimes;
    }

    /**
     * @param string $id
     * @param int    $time
     *
     * @return $this
     */
    public function set($id, $time)
    {
        $this->executionTimes[$id] = $time;

        return $this;
    }
}