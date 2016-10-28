<?php

namespace Nikoms\PhpUnitSplitter\TestCase;

class SplitStep
{
    const SPLIT = 'split';
    const RUN = 'run';
    const gathering = 'gathering';
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
        self::$step = self::gathering;
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

        return self::$step === self::gathering;
    }

    /**
     * @return int
     */
    public static function getValue()
    {
        return self::$value;
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
                    self::$value = $value;
                    self::splitting();
                    continue;
                }

                if ($key === 'split-running-group') {
                    self::$value = $value;
                    self::running();
                    continue;
                }

                if ($key === 'split-gathering-data') {
                    self::$value = $value;
                    self::gathering();
                    continue;
                }
            }
        }
    }
}