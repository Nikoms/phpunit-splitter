<?php

namespace Nikoms\PhpUnitSplitter\TestCase;

class TestCase
{
    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     *
     * @return string
     */
    public static function convertToId(\PHPUnit_Framework_TestCase $testCase)
    {
        return get_class($testCase).'::'.$testCase->getName(true);
    }
}