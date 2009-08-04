l10n files copied from AMO/site/app/locale.

We are using English Strings for gettext message ids, instead of Remora style, per L10n-drivers.

Instructions:
1) Run extract-po.sh from this directory. This will create application/messages.po
2) merge-po.sh ../messages.po en_US/LC_MESSAGES/
3) merge-enus2all.sh
This will push new strings into all other locales

Optional:
4) compile-mo.sh .
5) Restart apache

New Locales:
msginit --locale=fr -i en_US/LC_MESSAGES/messages.po

Make sure you system has that locale installed...
sudo locale-gen fr

Auto compile:
Mozilla Service Week has a cron setup on khan which automatically
compiles and commits changes to po files.