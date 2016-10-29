<?php

namespace Nikoms\PhpUnitSplitter\TestCase;

class Token
{
    /**
     * @var string
     */
    private static $testToken;

    /**
     * @var int
     */
    private static $runningGroup;

    /**
     * @return string
     */
    public static function getTestToken()
    {
        self::init();

        return self::$testToken;
    }

    /**
     * @return int
     */
    public static function getRunningGroup()
    {
        self::init();

        return self::$runningGroup;
    }

    /**
     *
     */
    private static function init()
    {
        if (!SplitStep::isRunning()) {
            return;
        }

        self::$runningGroup = (int) SplitStep::getValue();
        self::$testToken = uniqid(self::$runningGroup);
    }
}