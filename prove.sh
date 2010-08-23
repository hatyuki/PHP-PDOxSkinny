#!/bin/bash
ROOT=`readlink -f 0`
ROOT=`dirname $ROOT`
TEST_CASE=$ROOT/t

TESTS=(`find $TEST_CASE -maxdepth 1 -type f -name "*.php" | sort`)
for (( i = 0 ; i < ${#TESTS[*]} ; i++))
{
    TEST=${TESTS[i]}

    echo "*** $TEST"
    phpunit $TEST || exit
    echo ""; echo ""
}
