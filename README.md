# Phpunit-splitter

First idea from Michelangelo van Dam (@DragonBe):
```
for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do echo $i; done | time parallel docker run -d -v "$PWD":/var/run/phpunit -w /var/run/phpunit --name pu-docker-{} php:7.0-cli /var/run/phpunit/vendor/bin/phpunit --group {} && for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do docker wait pu-docker-$i | grep -c 0 > /dev/null || docker logs pu-docker-$i && docker rm -f pu-docker-$i > /dev/null; done;

```

## Making groups
```
./vendor/bin/phpunit -d split-jobs=5
```

## Running group
```
./vendor/bin/phpunit -d split-running-group=0
```

## Gathering data
```
./vendor/bin/phpunit -d split-gathering-data=5
```

## Do the dance!
```
 vendor/bin/phpunit-parallel.sh --jobs=5 --filter=_58 --debug
```

## To know on which step you are (in bootstrap.php for example)
```

if (Nikoms\PhpUnitSplitter\TestCase\SplitStep::isSplitting()) {
    //Do something specific before splitting all tests. For example, prepare distant API docker 
}

if (Nikoms\PhpUnitSplitter\TestCase\SplitStep::isRunning()) {
    //Do something specific like a normal bootstrap. For example init DB, etc...
    //Use Token to have a unique id per "running group":
    echo 'A token: '.Nikoms\PhpUnitSplitter\TestCase\Token::getTestToken();
    echo 'My running group : '.Nikoms\PhpUnitSplitter\TestCase\Token::getRunningGroup();
}

if (Nikoms\PhpUnitSplitter\TestCase\SplitStep::isGathering()) {
    //Do something specific after all tests. For example, destroy a distant API?
}
```

## Activate

Add listener in your phpunit.xml file

```
<phpunit>
    ...
    <listeners>
        <listener class="Nikoms\PhpUnitSplitter\Listener\SplitListener" />
    </listeners>
    ...
</phpunit>
```

## How does it work?
* Split equally depending on the number of jobs and create fake groups
* Parallel loop over jobs
* Run tests that only have this fake group
* When all tests are over get the new timers, recalculate all averages, delete tmp sqlite files


## TODO
* unit test
* CI
* Only display failing tests