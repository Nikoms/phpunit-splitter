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
    public function exists()
    {
        return file_exists($this->lockFilePathname);
    }

    /**
     * @return $this
     */
    public function init()
    {
        $this->updateFile([]);

        return $this;
    }

    /**
     * @return $this
     */
    public function done($id)
    {
        $jobsDone = include($this->lockFilePathname);
        $jobsDone[$id] = true;
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