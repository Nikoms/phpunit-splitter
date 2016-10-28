<?php
include(__DIR__.'/../vendor/autoload.php');
use Nikoms\PhpUnitSplitter\TestCase\SplitStep;
use Nikoms\PhpUnitSplitter\TestCase\Token;

if (SplitStep::isSplitting()) {
    echo sprintf('Bootstrap of splitting (%s jobs)', SplitStep::getValue()).PHP_EOL;
}

if (SplitStep::isRunning()) {
    echo sprintf(
            'Bootstrap of run. Token: %s. Running group:',
            Token::getTestToken(),
            Token::getRunningGroup()
        )
        .PHP_EOL;
}

if (SplitStep::isGathering()) {
    echo sprintf('Bootstrap of gathering (%s tests)', SplitStep::getValue()).PHP_EOL;
}