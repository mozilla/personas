# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the "License"); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# The Original Code is Weave code.
#
# The Initial Developer of the Original Code is
# Mozilla Corporation
# Portions created by the Initial Developer are Copyright (C) 2008
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#   Dan Mills <thunder@mozilla.com> (original author)
#   Myk Melez <myk@mozilla.org>
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK *****

# Generic makefile for a Mozilla extension.  This makefile is designed to be
# reusable by multiple extensions.  Extension-specific variables and targets
# should be defined in an extension-specific makefile (f.e. client.mk).

# Include extension-specific makefiles.
include *.mk


################################################################################
# Input Validation

ifeq ($(MAKECMDGOALS),install)
  ifndef profile
    $(error when installing, you must specify the profile into which \
            to install the extension via the "profile" variable)
  endif
  ifndef extension_id
    $(error when installing, you must specify the ID of the extension \
            via the "extension_id" variable)
  endif
endif

ifeq ($(MAKECMDGOALS),uninstall)
  ifndef profile
    $(error when uninstalling, you must specify the profile from which \
            to uninstall the extension via the "profile" variable)
  endif
  ifndef extension_id
    $(error when uninstalling, you must specify the ID of the extension \
            via the "extension_id" variable)
  endif
endif


################################################################################
# Variable Declarations

date              := $(shell date -u +%Y%m%d%H%M)
revision_id       := $(shell hg tip --template '{node|short}')

# Development Channel
ifeq ($(channel),dev)
  # Development build updates are managed by the website, so we construct
  # an update URL that points to the update manifest we are going to create.
  update_name     := update-$(channel).rdf
  update_url      := $(site_url_base)/$(update_name)
  update_url_tag  := <em:updateURL>$(update_url)</em:updateURL>
  package_version := $(version)x0d$(date)
  package_name    := $(name)-$(channel)-$(package_version).xpi
  package_alias   := $(name)-$(channel)-latest.xpi
  package_url     := $(site_url_base)/$(package_name)
  # Automatically archive chrome in JAR archive when building for this channel.
  jar             := 1

# Release Channel
else ifeq ($(channel),rel)
  # Release build updates are managed by AMO, which provides its own update.
  update_name     := 
  update_url      := 
  update_url_tag  := 
  package_version := $(version)
  package_name    := $(name)-$(version).xpi
  package_url     := 
  # Automatically archive chrome in JAR archive when building for this channel.
  jar             := 1

# No Channel
else
  # Builds without a channel don't update.
  update_name     := 
  update_url      := 
  update_url_tag  := 
  package_version := 0
  package_name    := $(name).xpi
  package_url     := 
endif

dotin_files       := $(shell find . -type f -name \*.in)
dotin_files       := $(dotin_files:.in=)

chrome_files      := content/* locale/* skin/*

# FIXME: use a package manifest to determine which files to package.
package_files     := defaults modules chrome.manifest install.rdf

ifdef jar
  chrome_path     := jar:chrome.jar!/
  jar_dependency  := chrome.jar
  package_files   += chrome.jar
else
  chrome_path     :=
  jar_dependency  :=
  package_files   += $(chrome_files)
endif

# OS detection
sys := $(shell uname -s)
ifeq ($(sys), Darwin)
  os = Darwin
else
ifeq ($(sys), Linux)
  os = Linux
else
ifeq ($(sys), MINGW32_NT-6.0)
  os = WINNT
else
ifeq ($(sys), MINGW32_NT-5.1)
  os = WINNT
else
  $(error your os is unknown/unsupported: $(sys))
endif
endif
endif
endif

# The path to the extension, in the native format, as required by the app
# for extensions installed via a file in the $(profile)/extensions/ directory
# that contains the path to the extension (which is how we install it).
ifeq ($(os), WINNT)
  extension_dir = $(subst /,\,$(shell pwd -W))
else
  extension_dir = $(shell pwd)
endif

# A command to substitute @variables@ for their values in .in files.
substitute := perl -p -e 's/@([^@]+)@/defined $$ENV{$$1} ? $$ENV{$$1} : $$&/ge'

# The variables to substitute for their values in .in files.
export package_version update_url_tag package_url revision_id chrome_path \
       channel extension_id \
       fx_min_version fx_max_version tb_min_version tb_max_version


################################################################################
# Make Targets

.PHONY: $(dotin_files) substitute build package publish clean

all: build

$(dotin_files): $(dotin_files:=.in)
	$(substitute) $@.in > $@

substitute: $(dotin_files)

chrome.jar: $(chrome_files)
	zip -ur chrome.jar $(chrome_files)

build: substitute $(jar_dependency)

package: build $(package_files)
	zip -ur $(package_name) $(package_files) -x \*.in
ifneq ($(package_url),)
	mv $(package_name) $(site_path_local)/
	ln -s -f $(package_name) $(site_path_local)/$(package_alias)
	mv update.rdf $(site_path_local)/$(update_name)
endif

publish:
	rsync -av $(site_path_local)/ $(site_path_remote)/

install:
	@if [ ! -e "$(profile)/extensions" ]; then \
	  mkdir -p $(profile)/extensions; \
	fi
	echo "$(extension_dir)" > $(profile)/extensions/$(extension_id)

uninstall:
	rm $(profile)/extensions/$(extension_id)

clean:
	rm -f $(dotin_files) chrome.jar $(package_name)
	@if [ -e "test/unit" ]; then \
	  $(MAKE) -C test/unit clean; \
	fi

help:
	@echo 'Targets:'
	@echo '  build:     process .in files'
	@echo '  package:   bundle the extension into a XPI'
	@echo '  publish:   push package and update manifest to the website'
	@echo '  install:   install extension to profile'
	@echo '  uninstall: uninstall extension from profile'
	@echo '  clean:     remove generated files'
	@echo
	@echo 'Variables:'
	@echo '  channel:   the distribution channel ("rel" or "dev")'
	@echo '  jar:       set to any value to archive chrome in JAR file,'
	@echo '             which improves application startup performance'
	@echo '             but requires you to rebuild after each change'
	@echo '  profile:   the profile directory into which to install'
	@echo '             (or from which to uninstall) the extension'
