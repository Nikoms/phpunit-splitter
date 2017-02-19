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
    private $maxProcess;

    /**
     * @var string
     */
    private $lockFilePathname;

    /**
     * LockMode constructor.
     *
     * @param int    $maxProcess
     * @param string $jobName
     */
    public function __construct($maxProcess, $jobName)
    {
        $this->maxProcess = $maxProcess;
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
     * @param string $processId
     *
     * @return $this
     */
    public function processDone($processId)
    {
        $processesDone = $this->isFirst() ? [] : include($this->lockFilePathname);
        $processesDone[$processId] = true;
        $this->updateFile($processesDone);

        if ($this->maxProcess === count($processesDone)) {
            $this->allDone();
        }

        return $this;
    }

    /**
     * @param array $processesDone
     *
     * @return $this
     */
    private function updateFile(array $processesDone)
    {
        file_put_contents(
            $this->lockFilePathname,
            '<?php return '.var_export($processesDone, true).';'
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