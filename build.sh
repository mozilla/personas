#!/bin/sh -x

substitutions="unpacked jar"
unpacked=
jar="# "

if [[ $1 == xpi ]]; then
    unpacked="# "
    jar=
fi

for in in `find . -type f -name \*.in`; do
    out=`echo $in | sed 's/\.in$//'`
    cp $in $out
    for subst in $substitutions; do
        sed -e "s/@$subst@/${!subst}/g" $out > $out.tmp
        mv $out.tmp $out
    done
done

cd components
./xptgen
cd -

if [[ ! $1 == xpi ]]; then
    exit 0;
fi

cd chrome
zip -9 -ur personas.jar *
cd ..
zip -9 -ur personas.xpi chrome/personas.jar defaults components install.rdf chrome.manifest
