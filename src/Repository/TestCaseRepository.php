<?php

namespace Nikoms\PhpUnitSplitter\Repository;

use Nikoms\PhpUnitSplitter\TestCase\TestCase;

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
                'INSERT INTO tests (id, average, runs) VALUES (:id, :average, 1)'
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
     * @param TestCase $testCase
     * @param int      $time
     */
    public function updateTime(TestCase $testCase, $time)
    {
        $this->selectStatement->execute(['id' => $testCase->getId()]);
        $isTestStored = (bool)$this->selectStatement->fetch();
        if (!$isTestStored) {
            echo 'insert : '.$testCase->getId().PHP_EOL;
            $this->insert($testCase, $time);
        } else {
            echo 'update : '.$testCase->getId().PHP_EOL;
            $this->update($testCase, $time);
        }
    }

    /**
     * @param TestCase $testCase
     * @param int      $time
     */
    public function insert(TestCase $testCase, $time)
    {
        $this->insertStatement->execute(
            [
                'id' => $testCase->getId(),
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
     *
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * @param TestCase $testCase
     * @param int      $time
     */
    private function update(TestCase $testCase, $time)
    {
        $this->updateStatement->execute(
            [
                'id' => $testCase->getId(),
                'newTime' => $time,
            ]
        );
    }
}