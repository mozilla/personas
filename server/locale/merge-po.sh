#!/bin/bash

# syntax:
# merge-po.sh extracted.pot destination-dir/

function usage() {
    echo "syntax:"
    echo "merge-po.sh extracted.pot destination-dir/"
    exit 1
}

tempfoo=`basename $0`
TMPFILE=`mktemp /tmp/${tempfoo}.XXXXXX` || exit 1

# check if file and dir are there
if [[ ($# -ne 2) || (! -f "$1") || (! -d "$2") ]]; then usage; fi

for lang in `find $2 -type f -name "*.po"`; do
    msgmerge --update $lang $1 > $TMPFILE
    cp "$TMPFILE" "$lang"
done
rm "$TMPFILE"
