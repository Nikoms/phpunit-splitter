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
./vendor/bin/phpunit -d split-jobs=5 && for i in {0..4}; do echo $i; done | time parallel ./vendor/bin/phpunit -d split-running-group={};
```

## Do the dance (in docker)!
```
for i in {0..4}; do echo $i; done | time parallel docker run -d -v "$PWD":/var/run/phpunit -w /var/run/phpunit --name pu-docker-{} php:7.0-cli /var/run/phpunit/vendor/bin/phpunit -d split-running-group={} && for i in `for((i=0;i<5;i+=1)); do echo " - $i"; done | grep "^ -" | awk {'print $2'}`; do docker wait pu-docker-$i | grep -c 0 > /dev/null || docker logs pu-docker-$i && docker rm -f pu-docker-$i > /dev/null; done;
```

## Split modes (temporary)

* 1: Remove tests that does not exist anymore
* 2: Add new tests
* 4: Init groups before testing in parallel

## How does it work?
* Issue: Sometimes the sqlite is still in lock mode. So we have to create a tmp table (another sqlite file)
* Split equally depending on the number of jobs and create fake groups
* Pass this fake group to the loop (with a grep maybe)
* Run tests that only have this fake group
* When all tests are over get the new timers, recalculate all averages, delete tmp sqlite files
 