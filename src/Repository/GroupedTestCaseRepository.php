<?php


namespace Nikoms\PhpUnitSplitter\Repository;


class GroupedTestCaseRepository
{
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
        try {
            $this->pdo = new \PDO('sqlite:'.__DIR__.'/phpunit-split-'.$this->groupId.'.sqlite');
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
     *
     */
    public function resetDatabase()
    {
        $this->pdo->query('DROP TABLE IF EXISTS tests');
        $this->pdo->query(
            'CREATE TABLE tests ( 
    id                VARCHAR( 1000 ),
    executionTime       int
);'
        );
        $this->pdo->query('CREATE INDEX IF NOT EXISTS test_idx ON tests(id);');
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
     *
     */
    public function close()
    {
        $this->pdo = null;
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