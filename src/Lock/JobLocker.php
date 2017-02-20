<?php

namespace Nikoms\PhpUnitSplitter\Lock;

use Nikoms\PhpUnitSplitter\Storage\FileStorage;

/**
 * Class LockMode
 */
class JobLocker
{
    /**
     * @var int
     */
    private $totalGroups;

    /**
     * @var FileStorage
     */
    private $storage;

    /**
     * LockMode constructor.
     *
     * @param int    $totalGroups
     * @param string $jobName
     */
    public function __construct($totalGroups, $jobName)
    {
        $this->totalGroups = $totalGroups;
        $this->storage = new FileStorage(sprintf('cache/.%s.php', $jobName));
    }

    /**
     * @return bool
     */
    public function isFirst()
    {
        return empty($this->storage->get());
    }

    /**
     * @param string $groupId
     *
     * @return $this
     */
    public function groupDone($groupId)
    {
        $executedGroups = $this->storage->get();
        $executedGroups[$groupId] = true;
        $this->updateFile($executedGroups);

        if ($this->totalGroups === count($executedGroups)) {
            $this->allDone();
        }

        return $this;
    }

    /**
     * @param array $executedGroups
     *
     * @return $this
     */
    private function updateFile(array $executedGroups)
    {
        $this->storage->save($executedGroups);

        return $this;
    }

    /**
     * @return $this
     */
    private function allDone()
    {
        $this->storage->delete();

        return $this;
    }
}