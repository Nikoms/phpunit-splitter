<?php

namespace Nikoms\PhpUnitSplitter\Lock;

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
     * @var string
     */
    private $lockFilePathname;

    /**
     * LockMode constructor.
     *
     * @param int    $totalGroups
     * @param string $jobName
     */
    public function __construct($totalGroups, $jobName)
    {
        $this->totalGroups = $totalGroups;
        $this->lockFilePathname = sprintf('cache/.%s.php', $jobName);
    }

    /**
     * @return bool
     */
    public function isFirst()
    {
        return !file_exists($this->lockFilePathname);
    }

    /**
     * @param string $groupId
     *
     * @return $this
     */
    public function groupDone($groupId)
    {
        $executedGroups = $this->isFirst() ? [] : include($this->lockFilePathname);
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
        file_put_contents(
            $this->lockFilePathname,
            '<?php return '.var_export($executedGroups, true).';'
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function allDone()
    {
        unlink($this->lockFilePathname);

        return $this;
    }
}