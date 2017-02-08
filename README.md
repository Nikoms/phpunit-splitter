# Phpunit-splitter

First idea from Michelangelo van Dam (@DragonBe):
```
for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do echo $i; done | time parallel docker run -d -v "$PWD":/var/run/phpunit -w /var/run/phpunit --name pu-docker-{} php:7.0-cli /var/run/phpunit/vendor/bin/phpunit --group {} && for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do docker wait pu-docker-$i | grep -c 0 > /dev/null || docker logs pu-docker-$i && docker rm -f pu-docker-$i > /dev/null; done;

```
# New Idea

```
vendor/bin/phpunit -d split-jobs=2 -d split-current=0
vendor/bin/phpunit -d split-jobs=2 -d split-current=1

```
 
## To know on which step you are (in bootstrap.php for example)
```
//TODO: Use callback
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