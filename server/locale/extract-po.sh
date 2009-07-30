#!/bin/bash
SOURCE_DIRS=".."

cd `dirname $0`/../

rm messages.po
touch messages.po

for sourcedir in $SOURCE_DIRS; do \
    find ./${sourcedir} -name "*thtml" -or -name "*.php" | xgettext \
        --language=PHP \
        --keyword=___ \
        --force-po \
        --omit-header \
        --join-existing \
        --add-comments \
        --copyright-holder="Mozilla Corporation" \
        --files-from=- # Pull from standard input (our find command) \
done
