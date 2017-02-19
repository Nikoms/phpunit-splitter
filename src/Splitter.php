<?php

namespace Nikoms\PhpUnitSplitter;

class Splitter
{
    const BEFORE_SPLIT = 'before.split';
    const AFTER_SPLIT = 'after.split';
    const BEFORE_RUN = 'before.run';
    const AFTER_RUN = 'after.run';

    /**
     * @var bool
     */
    private static $isInitialized = false;

    /**
     * @var int
     */
    private static $totalProcesses = 1;

    /**
     * @var int
     */
    private static $currentProcess = 0;

    /**
     * @var callable[][]
     */
    private static $listeners = [];


    /**
     * @return int
     */
    public static function getTotalProcesses()
    {
        self::init();

        return self::$totalProcesses;
    }

    /**
     * @return int
     */
    public static function getCurrentProcess()
    {
        self::init();

        return self::$currentProcess;
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

        $options = getopt('d:');

        if (isset($options['d'])) {
            $options['d'] = (array)$options['d'];
            foreach ($options['d'] as $option) {
                list($key, $value) = explode('=', $option);
                if ($key === 'split-total') {
                    self::$totalProcesses = (int)$value;
                    continue;
                }

                if ($key === 'split-current') {
                    self::$currentProcess = (int)$value;
                    continue;
                }
            }
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
        if(isset(self::$listeners[$eventName])){
            foreach(self::$listeners[$eventName] as $listener){
                $listener();
            }
        }
    }
}