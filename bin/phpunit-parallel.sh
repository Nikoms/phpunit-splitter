#!/bin/bash

phpunitArgs="";

while [ $# -gt 0 ]; do
  case "$1" in
    --jobs=*)
      jobs="${1#*=}"
      ;;
      *) phpunitArgs="$phpunitArgs $1"
      ;;
  esac
  shift
done


echo "Splitting in $jobs jobs (with phpunit arguments: $phpunitArgs)";
./vendor/bin/phpunit -d split-jobs=$jobs $phpunitArgs --printer="Nikoms\PhpUnitSplitter\Printer\SplitPrinter";

for (( i=0; i<$jobs; i++ ))
do
    echo "$i $phpunitArgs"; done | parallel --colsep=' ' ./vendor/bin/phpunit -d split-running-group={};

./vendor/bin/phpunit -d split-gathering-data=$jobs $phpunitArgs --printer="Nikoms\PhpUnitSplitter\Printer\AggregatePrinter";
