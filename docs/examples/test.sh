#!/bin/bash

if test $# -gt 0 
then
	files=$@
else
	files=`ls *.xml`
fi

export NO_INTERACTION=1
export REPORT_EXIT_STATUS=1

mkdir -p testing
cd testing

for spec in $files
do
  echo testing $spec
  rm -rf `basename $spec .xml`
  if pecl-gen ../$spec > /dev/null
  then
    (cd `basename $spec .xml` && phpize > /dev/null && configure > /dev/null && make test > /dev/null) || echo $spec failed
  fi
done
