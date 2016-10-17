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
     * @param string $bigFilter
     * @param int    $numberOfTests
     */
    public function createDatabase($bigFilter, $numberOfTests)
    {
        $this->pdo->query(
            'CREATE TABLE IF NOT EXISTS tests ( 
    id                VARCHAR( 1000 ),
    currentTime       int
);'
        );
        $this->pdo->query(
            'CREATE TABLE IF NOT EXISTS tests_stats (filter TEXT, number_of_tests int);'
        );
        $insertStats = $this->pdo->prepare(
            'INSERT INTO tests_stats (filter, number_of_tests) VALUES (:filter, :number_of_tests)'
        );
        $insertStats->execute(['filter' => $bigFilter, 'number_of_tests' => $numberOfTests]);
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
                'INSERT INTO tests (id, currentTime) VALUES (:id, :time)'
            );
        }

        return $this->insertStatement->execute(['id' => $id, 'currentTime' => $time]);
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        $result = $this->pdo->query('SELECT * FROM tests_stats')->fetch();
        if (!$result) {
            return '^Empty::Class\function';
        }

        return $result['filter'];
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
}