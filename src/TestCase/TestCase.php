<?php

namespace Nikoms\PhpUnitSplitter\TestCase;

class TestCase
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $dataName;

    /**
     * TestCase constructor.
     *
     * @param \PHPUnit_Framework_TestCase $testCase
     */
    public function __construct(\PHPUnit_Framework_TestCase $testCase)
    {
        $reflectionClass = new \ReflectionClass($testCase);
        $this->className = $reflectionClass->getName();
        $this->method = $testCase->getName(false);
        $this->dataName = $this->extractDataName($testCase);
        $this->id = $this->className.'::'.$testCase->getName(true);
    }


    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     *
     * @return string
     */
    private function extractDataName(\PHPUnit_Framework_TestCase $testCase)
    {
        if ($this->hasTestCaseDataSet($testCase)) {
            return null;
        }
        $dataName = substr($testCase->getName(true), strlen($testCase->getName(false)));
        $dataName = str_replace('with data set', '', $dataName);

        return $this->removeDataNameDecoration(trim($dataName));
    }

    /**
     * @param string $dataName
     *
     * @return string
     */
    private function removeDataNameDecoration($dataName)
    {
        return ($dataName[0] === '#') ? substr($dataName, 1) : substr($dataName, 1, -1);
    }

    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     *
     * @return bool
     */
    private function hasTestCaseDataSet(\PHPUnit_Framework_TestCase $testCase)
    {
        return $testCase->getName(false) === $testCase->getName(true);
    }

    /**
     * @return string
     */
    private function getName()
    {
        return $this->className.'::'.$this->method.$this->getSuffix();
    }

    /**
     * @param string $separator
     *
     * @return string
     */
    public function getFilter($separator = '/')
    {
        return '^'.preg_quote($this->getName(), $separator).'$';
    }

    /**
     * @return string
     */
    private function getSuffix()
    {
        if ($this->dataName === null || $this->dataName === '') {
            return '';
        }
        if (is_numeric($this->dataName)) {
            return ' with data set #'.$this->dataName;
        } else {
            return ' with data set "'.$this->dataName.'"';
        }
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return null|string
     */
    public function getDataName()
    {
        return $this->dataName;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}