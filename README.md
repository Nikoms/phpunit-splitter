[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6c31c675-d230-4330-b33f-701806e39d78/mini.png)](https://insight.sensiolabs.com/projects/6c31c675-d230-4330-b33f-701806e39d78)
[![Build Status](https://travis-ci.org/Nikoms/phpunit-splitter.svg?branch=master)](https://travis-ci.org/Nikoms/phpunit-splitter/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/Nikoms/phpunit-splitter/badges/quality-score.png)](https://scrutinizer-ci.com/g/Nikoms/phpunit-splitter/)
[![Code Coverage](https://scrutinizer-ci.com/g/Nikoms/phpunit-splitter/badges/coverage.png)](https://scrutinizer-ci.com/g/Nikoms/phpunit-splitter/)


# Phpunit-splitter

## Split to rule them all!

_paratest_ does not work well for my needs. So I was thinking of a new way of doing this.


Michelangelo van Dam (@DragonBe) opened the door with a very good idea:
```
for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do echo $i; done | time parallel docker run -d -v "$PWD":/path/to/docker/folder -w /path/to/docker/folder --name pu-docker-{} php:7.0-cli /path/to/docker/folder/vendor/bin/phpunit --group {} && for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do docker wait pu-docker-$i | grep -c 0 > /dev/null || docker logs pu-docker-$i && docker rm -f pu-docker-$i > /dev/null; done;
```

It was so cool, but limited to "@group" annotation... There are some problems with that:
* It gives too much power to the human
* It won't be split equally (timing)
* If you want to scale more, you have to update "groups" ...
* ... and by that it means committing code for "nothing"
* You need php installed (It run `/vendor/bin/phpunit`)

What I propose here is simple:

* Let's not care about groups!
* Let phpunit choose which tests must run together
* Groups of test must be ran in the same amount of time ... depending on the average execution time of each test!
* The developer must only specify in how many processes/groups the tests must be split
* You don't need php on your machine!

## Installation

Via composer, it's easy:
```
composer install nikoms/phpunit-splitter
```

Then, juste add a listener in your phpunit.xml(.dist) file... That's it!

```
<phpunit>
    ...
    <listeners>
        <listener class="Nikoms\PhpUnitSplitter\Listener\SplitListener" />
    </listeners>
    ...
</phpunit>
```

## Running like a pro

It looks like the previous command, but you only have to change the "jobs" variable. Let this plugin do the split for you :)

```
jobs="5"; for ((i=0; i<$jobs; i++)) do echo $i; done | time parallel docker run -d -v "$PWD":/path/to/docker/folder -w /path/to/docker/folder --name pu-docker-{} php:7.0-cli /path/to/docker/folder/vendor/bin/phpunit -d split-jobs=$jobs -d split-current={} && for ((i=0; i<$jobs; i++)); do docker wait pu-docker-$i | grep -c 0 > /dev/null || docker logs pu-docker-$i && docker rm -f pu-docker-$i > /dev/null; done;
```

## Bootstrap like a pro

In phpunit, you can create a "bootstrap" file that will be included before tests start.

There is the same logic with this plugin and **on top of that**, there are events to know exactly on which step your are and in which group!

```
//Put this in your bootstrap.php

SplitStep::on(
    SplitStep::EVENT_BEFORE_SPLIT,
    function () {
        // Do something before creating groups.
        // NB: It only happen on one random process. Do not create constant here
    }
);

SplitStep::on(
    SplitStep::EVENT_AFTER_SPLIT,
    function () {
        // Do something when all groups are set
        // NB: It only happen on one random process. Do not create constant here
    }
);

SplitStep::on(
    SplitStep::EVENT_BEFORE_RUN,
    function () {
        // Before running one group. Ex: Creating DB, storing a constant with "define", etc...
    }
);
SplitStep::on(
    SplitStep::EVENT_AFTER_RUN,
    function () {
        // After running one group. Ex: dropping DB
    }
);
```

## TODO
* unit test
* CI
* Be compatible with phpunit 6
