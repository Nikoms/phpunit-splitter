<?php

namespace Nikoms\PhpUnitSplitter;

use Nikoms\PhpUnitSplitter\Storage\FileStorage;

/**
 * Class Stats
 */
class Stats
{
    const CACHE_STATS_PATHNAME = 'cache/stats.php';

    /**
     * @var array
     */
    private $stats;

    /**
     * @var FileStorage
     */
    private $storage;

    /**
     * StatsStorage constructor.
     */
    public function __construct()
    {
        $this->storage = new FileStorage(self::CACHE_STATS_PATHNAME);
        $this->stats = $this->storage->get();
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
        $this->storage->save($this->stats);
    }
}