#!/bin/bash

export NO_INTERACTION=1
export REPORT_EXIT_STATUS=1

for spec in *.xml
do
  echo testing $spec
  rm -rf `basename $spec .xml`
  if pecl-gen $spec > /dev/null
  then
    (cd `basename $spec .xml` && phpize > /dev/null && configure > /dev/null && make test > /dev/null) || echo $spec failed
  fi
done
