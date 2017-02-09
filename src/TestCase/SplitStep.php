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
    private static $isInitialized = false;

    /**
     * @var int
     */
    private static $totalJobs = null;

    /**
     * @var int
     */
    private static $current = null;


    /**
     * @return int
     */
    public static function getTotalJobs()
    {
        self::init();

        return self::$totalJobs;
    }

    /**
     * @return int
     */
    public static function getCurrent()
    {
        self::init();

        return self::$current;
    }

    /**
     *
     */
    public static function init()
    {
        if (self::$isInitialized) {
            return;
        }
        self::$isInitialized = true;

        $options = getopt(
            'd:'
        );
        if (isset($options['d'])) {
            $options['d'] = (array)$options['d'];
            foreach ($options['d'] as $option) {
                list($key, $value) = explode('=', $option);
                if ($key === 'split-jobs') {
                    self::$totalJobs = (int)$value;
                    continue;
                }

                if ($key === 'split-current') {
                    self::$current = (int)$value;
                    continue;
                }
            }
        }
        if(self::$totalJobs === null){
            self::$totalJobs = 1;
        }
        if(self::$current === null){
            self::$current = 0;
        }

    }
}