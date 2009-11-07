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
# The Original Code is Snowl.
#
# The Initial Developer of the Original Code is Mozilla.
# Portions created by the Initial Developer are Copyright (C) 2009
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
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

# Project-specific makefile.  This gets included by the generic Makefile.

extension_id      := personas@christopher.beard

# The name of the extension for use in filenames (like when packaging XPIs).
name              := personas

# The next release version of the extension.  When building for the rel channel,
# make will use this value verbatim.  But when building for the dev channel,
# it will append a string to this value that makes the version be less than
# the future release version but greater than any previous dev version.
# For example, if the next release version is 1.4, a dev version for it might be
# 1.4x0d200909191530, where "x0d" is required by a peculiarity of Mozilla's
# version comparator (nsIVersionComparator) and the rest of the string is
# a timestamp.
version           := 1.5

# These variables control publication of the extension to a remote website.
site_url_base     := https://people.mozilla.com/~cbeard/personas/dist
site_path_local   := dist
site_path_remote  := people.mozilla.com:/home/cbeard/public_html/personas/dist

# The minimum and maximum versions of Firefox and Thunderbird with which
# the extension is compatible.  These are used in both the install and update
# manifests.
fx_min_version    := 3.0
fx_max_version    := 3.6.*

tb_min_version    := 3.0b1
tb_max_version    := 3.0.*
