<?php


namespace Nikoms\PhpUnitSplitter\Repository;


class GroupedTestCaseRepository
{
    /**
     * @var string
     */
    private $pathname;

    /**
     * @var int
     */
    private $groupId;

    /**
     * @var \PDOStatement
     */
    private $insertStatement;

    /**
     * GroupedTestCaseRepository constructor.
     *
     * @param int $groupId
     */
    public function __construct($groupId)
    {
        $this->groupId = $groupId;
        $this->pathname = __DIR__.'/phpunit-split-'.$this->groupId.'.sqlite';
        try {
            $this->pdo = new \PDO('sqlite:'.$this->pathname);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );
        } catch (\Exception $e) {
            echo "Impossible to load sqlite file : ".$e->getMessage();
            die();
        }
    }

    /**
     * @return $this
     */
    public function resetDatabase()
    {
        $this->pdo->query(
            'CREATE TABLE IF NOT EXISTS tests ( 
    id                VARCHAR( 1000 ),
    executionTime       int
);'
        );
        $this->pdo->query('CREATE INDEX IF NOT EXISTS test_idx ON tests(id);');

        return $this;
    }

    /**
     * @param int $id
     * @param int $time
     *
     * @return bool
     */
    public function insert($id, $time)
    {
        if ($this->insertStatement === null) {
            $this->insertStatement = $this->pdo->prepare(
                'INSERT INTO tests (id, executionTime) VALUES (:id, :executionTime)'
            );
        }

        return $this->insertStatement->execute(['id' => $id, 'executionTime' => $time]);
    }

    /**
     * @param string $id
     * @param int    $time
     *
     * @return bool
     */
    public function updateTime($id, $time)
    {
        return $this
            ->pdo
            ->prepare('UPDATE tests set executionTime = :executionTime WHERE id = :id')
            ->execute(['id' => $id, 'executionTime' => $time]);
    }

    /**
     *
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     *
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * @return $this
     */
    public function close()
    {
        $this->pdo = null;

        return $this;
    }

    /**
     * @return $this
     */
    public function drop()
    {
        unlink($this->pathname);

        return $this;
    }

    /**
     * @return array
     */
    public function getTestIds()
    {
        return array_column($this->pdo->query('select id from tests')->fetchAll(), 'id');
    }

    /**
     * @return array
     */
    public function getTimes()
    {
        return $this->pdo->query('select id, executionTime from tests')->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_GROUP);
    }
}