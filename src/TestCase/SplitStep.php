<?php

namespace Nikoms\PhpUnitSplitter\TestCase;

class SplitStep
{
    const EVENT_BEFORE_SPLIT = 'before.split';
    const EVENT_AFTER_SPLIT = 'after.split';
    const EVENT_BEFORE_RUN = 'before.run';
    const EVENT_AFTER_RUN = 'after.run';
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
     * @var callable[][]
     */
    private static $listeners = [];


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
        if (self::$totalJobs === null) {
            self::$totalJobs = 1;
        }
        if (self::$current === null) {
            self::$current = 0;
        }
    }

    /**
     * @param string   $eventName
     * @param callable $call
     */
    public static function on($eventName, callable $call)
    {
        self::$listeners[$eventName][] = $call;
    }

    /**
     * @param string $eventName
     */
    public static function dispatch($eventName)
    {
        if(self::$listeners[$eventName]){
            foreach(self::$listeners[$eventName] as $listener){
                $listener();
            }
        }
    }
}