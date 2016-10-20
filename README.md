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


## How does it work?
* Split equally depending on the number of jobs and create fake groups
* Parallel loop over jobs
* Run tests that only have this fake group
* When all tests are over get the new timers, recalculate all averages, delete tmp sqlite files


## TODO
* unit test
* CI
