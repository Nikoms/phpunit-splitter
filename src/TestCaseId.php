<?php

namespace Nikoms\PhpUnitSplitter;

class TestCaseId
{
    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     *
     * @return string
     */
    public static function fromTestCase(\PHPUnit_Framework_TestCase $testCase)
    {
        return get_class($testCase).'::'.$testCase->getName(true);
    }
}