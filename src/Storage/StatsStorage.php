<?php

namespace Nikoms\PhpUnitSplitter\Storage;

/**
 * Class StatsStorage
 */
class StatsStorage
{
    const CACHE_STATS_PATHNAME = 'cache/stats.php';

    /**
     * StatsStorage constructor.
     */
    public function __construct()
    {
        $this->stats = file_exists(self::CACHE_STATS_PATHNAME)
            ? include(self::CACHE_STATS_PATHNAME)
            : [];
    }

    /**
     * @param string $id
     * @param int    $newExecutionTime
     *
     * @return bool
     */
    public function updateTime($id, $newExecutionTime)
    {
        $test = $this->stats[$id];

        $total = ($test['average'] * $test['runs']) + $newExecutionTime;

        $this->stats[$id]['average'] = $total / ($test['runs'] + 1);
        $this->stats[$id]['runs']++;
    }

    /**
     * @param int $id
     */
    public function assureTestIsStored($id)
    {
        if (!isset($this->stats[$id])) {
            $this->insert($id, 0);
        }
    }

    /**
     * @param     $id
     * @param int $time
     */
    public function insert($id, $time)
    {
        $this->stats[$id] = [
            'id' => $id,
            'average' => $time,
            'runs' => 0,
        ];
    }

    /**
     * @return array
     */
    public function getAverages()
    {
        return array_column($this->stats, 'average', 'id');
    }

    /**
     * @param string $id
     *
     * @return int
     */
    public function getAverage($id)
    {
        return $this->stats[$id]['average'];
    }

    /**
     *
     */
    public function save()
    {
        file_put_contents(self::CACHE_STATS_PATHNAME, '<?php return '.var_export($this->stats, true).';');
        //When docker run the command
        @chmod(self::CACHE_STATS_PATHNAME, 0777);
    }
}