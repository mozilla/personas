/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Personas.
 *
 * The Initial Developer of the Original Code is Mozilla.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Chris Beard <cbeard@mozilla.org>
 *   Myk Melez <myk@mozilla.org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

const Cc = Components.classes;
const Ci = Components.interfaces;
const Cr = Components.results;
const Cu = Components.utils;

let CustomPersonaEditor = {
  //**************************************************************************//
  // Convenience Getters

  get _stringBundle() {
    let stringBundle = document.getElementById("personasStringBundle");
    delete this._stringBundle;
    this._stringBundle = stringBundle;
    return this._stringBundle;
  },

  get _headerURL() {
    let headerURL = document.getElementById("headerURL");
    delete this._headerURL;
    this._headerURL = headerURL;
    return this._headerURL;
  },

  get _footerURL() {
    let footerURL = document.getElementById("footerURL");
    delete this._footerURL;
    this._footerURL = footerURL;
    return this._footerURL;
  },

  get _textColorPicker() {
    let textColorPicker = document.getElementById("textColorPicker");
    delete this._textColorPicker;
    this._textColorPicker = textColorPicker;
    return this._textColorPicker;
  },

  // Preference Service
  get _prefSvc() {
    let prefSvc = Cc["@mozilla.org/preferences-service;1"].
                  getService(Ci.nsIPrefBranch);
    prefSvc.QueryInterface(Ci.nsIPrefBranch2);
    delete this._prefSvc;
    this._prefSvc = prefSvc;
    return this._prefSvc;
  },

  init: function() {
    this._prefSvc.setCharPref("extensions.personas.selected", "manual");

    // XXX I wonder if it's really necessary to reset the category at this point.
    this._prefSvc.setCharPref("extensions.personas.category", "");

    document.getElementById("headerURL").value = this._prefSvc.getCharPref("extensions.personas.custom.headerURL");
    document.getElementById("footerURL").value = this._prefSvc.getCharPref("extensions.personas.custom.footerURL");
    if (this._prefSvc.prefHasUserValue("extensions.personas.custom.textColor"))
      this._textColorPicker.color = this._prefSvc.getCharPref("extensions.personas.custom.textColor");
    else
      this._textColorPicker.color = "#000000";
  },

  onSelectHeader: function(aEvent) {
    this._solicitBackground("extensions.personas.custom.headerURL", this._headerURL);
  },

  onSelectFooter: function(aEvent) {
    this._solicitBackground("extensions.personas.custom.footerURL", this._footerURL);
  },

  _solicitBackground: function(aPref, aControl) {
    let fp = Cc["@mozilla.org/filepicker;1"].createInstance(Ci.nsIFilePicker);
    fp.init(window,
            this._stringBundle.getString("backgroundPickerDialogTitle"),
            Ci.nsIFilePicker.modeOpen);
    let result = fp.show();
    if (result == Ci.nsIFilePicker.returnOK) {
      this._prefSvc.setCharPref(aPref, fp.fileURL.spec);
      aControl.value = fp.fileURL.spec;
    }
  },

  onChangeColor: function(aEvent) {
    this._prefSvc.setCharPref("extensions.personas.custom.textColor", this._textColorPicker.color);
  }
}

window.addEventListener("load", function() { CustomPersonaEditor.init() }, false);
