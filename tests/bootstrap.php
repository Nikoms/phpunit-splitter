<?php
include(__DIR__.'/../vendor/autoload.php');
use Nikoms\PhpUnitSplitter\Splitter;

Splitter::on(
    Splitter::BEFORE_SPLIT,
    function () {
        // Do something before creating groups.
        // NB: It only happen on one random process. Do not create constant here
    }
);

Splitter::on(
    Splitter::AFTER_SPLIT,
    function () {
        // Do something when all groups are set
        // NB: It only happen on one random process. Do not create constant here
    }
);

Splitter::on(
    Splitter::BEFORE_RUN,
    function () {
        // Before running one group. Ex: Creating DB, storing a constant with "define", etc...
    }
);
Splitter::on(
    Splitter::AFTER_RUN,
    function () {
        // After running one group. Ex: dropping DB
    }
);