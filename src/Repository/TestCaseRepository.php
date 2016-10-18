<?php

namespace Nikoms\PhpUnitSplitter\Repository;

class TestCaseRepository
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var \PDOStatement
     */
    private $selectStatement;

    /**
     * @var \PDOStatement
     */
    private $updateStatement;

    /**
     * @var \PDOStatement
     */
    private $insertStatement;

    public function __construct()
    {
        try {
            $this->pdo = new \PDO('sqlite:'.__DIR__.'/database.sqlite');
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );
            $this->pdo->query('PRAGMA busy_timeout = 15000');

            $this->pdo->query(
                'CREATE TABLE IF NOT EXISTS tests ( 
    id            VARCHAR( 1000 ),
    average       int,
    runs          int
);'
            );
            $this->pdo->query('CREATE INDEX IF NOT EXISTS test_idx ON tests(id);');

            $this->selectStatement = $this->pdo->prepare('SELECT * FROM tests WHERE id = :id');
            $this->insertStatement = $this->pdo->prepare(
                'INSERT INTO tests (id, average, runs) VALUES (:id, :average, 0)'
            );
            $this->updateStatement = $this->pdo->prepare(
                'UPDATE tests set average = ((average*runs)+:newTime)/(runs+1), runs = runs+1 where id = :id'
            );
        } catch (\Exception $e) {
            echo "Impossible to load sqlite file : ".$e->getMessage();
            die();
        }
    }

    /**
     * @param string $id
     * @param int    $time
     *
     * @return bool
     */
    public function updateTime($id, $time)
    {
        return $this->updateStatement->execute(
            [
                'id' => $id,
                'newTime' => $time,
            ]
        );
    }

    /**
     * @param int $id
     */
    public function assureTestIsStored($id)
    {
        $this->selectStatement->execute(['id' => $id]);
        $found = $this->selectStatement->fetchAll();
        if (empty($found)) {
            $this->insert($id, 0);
        }
    }

    /**
     * @param     $id
     * @param int $time
     */
    public function insert($id, $time)
    {
        $this->insertStatement->execute(
            [
                'id' => $id,
                'average' => $time,
            ]
        );
    }

    /**
     *
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * @return array
     */
    public function getAllChronos()
    {
        return $this->pdo->query('SELECT id, average FROM tests')->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
    }

    /**
     *
     */
    public function commit()
    {
        $this->pdo->commit();
    }
}