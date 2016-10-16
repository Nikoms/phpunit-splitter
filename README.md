# Phpunit-splitter

First idea from Michelangelo van Dam (@DragonBe):
```
for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do echo $i; done | time parallel docker run -d -v "$PWD":/var/run/phpunit -w /var/run/phpunit --name pu-docker-{} php:7.0-cli /var/run/phpunit/vendor/bin/phpunit --group {} && for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do docker wait pu-docker-$i | grep -c 0 > /dev/null || docker logs pu-docker-$i && docker rm -f pu-docker-$i > /dev/null; done;

```

## How does it work?
* Issue: Sometimes the sqlite is still in lock mode. So we have to create a tmp table (another sqlite file)
* Split equally depending on the number of jobs and create fake groups
* Pass this fake group to the loop (with a grep maybe)
* Run tests that only have this fake group
* When all tests are over get the new timers, recalculate all averages, delete tmp sqlite files
 