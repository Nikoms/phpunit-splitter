# Phpunit-splitter

paratest does not work well for my needs. So I was thinking of a new way of doing this.



Michelangelo van Dam (@DragonBe) opened the door with a very nice idea:
```
for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do echo $i; done | time parallel docker run -d -v "$PWD":/path/to/docker/folder -w /path/to/docker/folder --name pu-docker-{} php:7.0-cli /path/to/docker/folder/vendor/bin/phpunit --group {} && for i in `./vendor/bin/phpunit --list-groups | grep "^ -" | awk {'print $2'}`; do docker wait pu-docker-$i | grep -c 0 > /dev/null || docker logs pu-docker-$i && docker rm -f pu-docker-$i > /dev/null; done;
```

It was so cool, but limited to "@group" annotation... And I don't want to give to much power to the developer. I want a scalable solution without touching the code!

* Let's not care about groups! 
* Let phpunit choose which tests must be run together
* Group must depend on the average execution time of each test!
* The developer must only specify in how many process the tests must be split

```
jobs="5"; for ((i=0; i<$jobs; i++)) do echo $i; done | time parallel docker run -d -v "$PWD":/path/to/docker/folder -w /path/to/docker/folder --name pu-docker-{} php:7.0-cli /path/to/docker/folder/vendor/bin/phpunit -d split-jobs=$jobs -d split-current={} && for ((i=0; i<$jobs; i++)); do docker wait pu-docker-$i | grep -c 0 > /dev/null || docker logs pu-docker-$i && docker rm -f pu-docker-$i > /dev/null; done;
```

## Installation

JUST listener in your phpunit.xml file... That's it!

```
<phpunit>
    ...
    <listeners>
        <listener class="Nikoms\PhpUnitSplitter\Listener\SplitListener" />
    </listeners>
    ...
</phpunit>
```

## To know on which step you are (in bootstrap.php for example)
```
//TODO: Use callback
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