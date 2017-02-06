<?php

namespace Nikoms\PhpUnitSplitter\TestCase;

class TestCase
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \PHPUnit_Framework_TestCase
     */
    private $testCase;

    /**
     * TestCase constructor.
     *
     * @param \PHPUnit_Framework_TestCase $testCase
     */
    public function __construct(\PHPUnit_Framework_TestCase $testCase)
    {
        $reflectionClass = new \ReflectionClass($testCase);
        $this->id = $reflectionClass->getName().'::'.$testCase->getName(true);
        $this->testCase = $testCase;
    }

    /**
     * @return \PHPUnit_Framework_TestCase
     */
    public function getTestCase()
    {
        return $this->testCase;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}