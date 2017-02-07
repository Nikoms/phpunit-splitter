<?php

namespace Nikoms\PhpUnitSplitter\TestCase;

class SplitStep
{
    const SPLIT = 'split';
    const RUN = 'run';
    const GATHERING = 'gathering';
    /**
     * @var string
     */
    private static $step;
    /**
     * @var int
     */
    private static $value;

    private static function splitting()
    {
        self::$step = self::SPLIT;
    }

    private static function running()
    {
        self::$step = self::RUN;
    }

    private static function gathering()
    {
        self::$step = self::GATHERING;
    }

    public static function isSplitting()
    {
        self::init();

        return self::$step === self::SPLIT;
    }

    public static function isRunning()
    {
        self::init();

        return self::$step === self::RUN;
    }

    public static function isGathering()
    {
        self::init();

        return self::$step === self::GATHERING;
    }

    /**
     * @return int
     */
    public static function getValue()
    {
        return self::$value;
    }

    /**
     * @return int
     */
    public static function getTotalJobs()
    {
        return self::$value;
    }

    /**
     * @return string
     */
    public static function getStep()
    {
        return self::$step;
    }

    public static function init()
    {
        if (self::$step !== null) {
            return;
        }
        $options = getopt(
            'd:'
        );
        if (isset($options['d'])) {
            $options['d'] = (array)$options['d'];
            foreach ($options['d'] as $option) {
                list($key, $value) = explode('=', $option);
                if ($key === 'split-jobs') {
                    self::$value = (int) $value;
                    self::splitting();
                    continue;
                }

                if ($key === 'split-running-group') {
                    self::$value = (int) $value;
                    self::running();
                    continue;
                }

                if ($key === 'split-gathering-data') {
                    self::$value = (int) $value;
                    self::gathering();
                    continue;
                }
            }
        }
    }
}