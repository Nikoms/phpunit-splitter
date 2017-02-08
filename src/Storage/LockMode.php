<?php

namespace Nikoms\PhpUnitSplitter\Storage;

/**
 * Class LockMode
 */
class LockMode
{
    /**
     * @var int
     */
    private $totalJobs;
    /**
     * @var string
     */
    private $lockFilePathname;

    /**
     * LockMode constructor.
     *
     * @param int    $totalJobs
     * @param string $lockFilePathname
     */
    public function __construct($totalJobs, $lockFilePathname)
    {
        $this->totalJobs = $totalJobs;
        $this->lockFilePathname = $lockFilePathname;
    }

    /**
     * @return bool
     */
    public function isFirst()
    {
        return !file_exists($this->lockFilePathname);
    }

    /**
     * @param string $testCaseId
     *
     * @return $this
     */
    public function done($testCaseId)
    {
        $jobsDone = $this->isFirst() ? [] : include($this->lockFilePathname);
        $jobsDone[$testCaseId] = true;
        $this->updateFile($jobsDone);

        if ($this->totalJobs === count($jobsDone)) {
            $this->allDone();
        }

        return $this;
    }

    /**
     * @param array $jobsDone
     *
     * @return $this
     */
    private function updateFile(array $jobsDone)
    {
        file_put_contents(
            $this->lockFilePathname,
            '<?php return '.var_export($jobsDone, true).';'
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