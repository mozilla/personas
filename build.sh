#!/bin/sh -x

substitutions="unpacked jar"
unpacked=
jar="# "

if [[ $1 == xpi ]]; then
    unpacked="# "
    jar=
    # Back up the chrome.manifest file so we can restore it to its pristine
    # state after horking it to point to the JAR archives in the XPI file.
    cp chrome.manifest .chrome.manifest.bak
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
# \*/CVS/\* excludes all files in CVS directories
# \*/.\* excludes all hidden Unix files (i.e. those that start with a period)
zip -9 -ur personas.jar content locale skin -x \*/CVS/\* -x \*/.\*
cd ..

# components/\*.idl excludes IDL files, which are unnecessary in the XPI
#   since the info in them has been compiled to an XPT file
# components/xptgen excludes that script, which is part of the build process
zip -9 -ur personas.xpi chrome/personas.jar components defaults install.rdf chrome.manifest -x \*/CVS/\* -x \*/.\* -x components/\*.idl -x components/xptgen

# Restore the backed up chrome.manifest file.
cp .chrome.manifest.bak chrome.manifest
