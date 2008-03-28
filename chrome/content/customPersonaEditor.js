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
 * Portions created by the Initial Developer are Copyright (C) 2008
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
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

  get _useDefaultTextColorCheckbox() {
    let useDefaultTextColorCheckbox = document.getElementById("useDefaultTextColorCheckbox");
    delete this._useDefaultTextColorCheckbox;
    this._useDefaultTextColorCheckbox = useDefaultTextColorCheckbox;
    return this._useDefaultTextColorCheckbox;
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

  get _prefCache() {
    let prefCache = new PersonasPrefCache("", this);
    delete this._prefCache;
    this._prefCache = prefCache;
    return this._prefCache;
  },

  _getPref: function(aPrefName, aDefaultValue) {
    return this._prefCache.getPref(aPrefName, aDefaultValue);
  },

  init: function() {
    this._prefSvc.setCharPref("extensions.personas.selected", "manual");

    // XXX I wonder if it's really necessary to reset the category at this point.
    this._prefSvc.setCharPref("extensions.personas.category", "");

    this._headerURL.value = this._getPref("extensions.personas.custom.headerURL", "");
    this._footerURL.value = this._getPref("extensions.personas.custom.footerURL", "");
    this._textColorPicker.color = this._getPref("extensions.personas.custom.textColor", "#000000");
    this._applyPrefUseDefaultTextColor();
  },

  // Apply pref changes to the controls.
  observe: function(aSubject, aTopic, aData) {
    switch(aTopic) {
      case "nsPref:changed":
        switch (aData) {
          case "extensions.personas.custom.headerURL":
            this._headerURL.value = this._getPref("extensions.personas.custom.headerURL");
            break;
          case "extensions.personas.custom.footerURL":
            this._footerURL.value = this._getPref("extensions.personas.custom.footerURL");
            break;
          case "extensions.personas.custom.textColor":
            this._textColorPicker.color = this._getPref("extensions.personas.custom.textColor");
            break;
          case "extensions.personas.custom.useDefaultTextColor":
            this._applyPrefUseDefaultTextColor();
            break;
        }
        break;
    }
  },

  _applyPrefUseDefaultTextColor: function() {
    let useDefaultTextColor =
      this._getPref("extensions.personas.custom.useDefaultTextColor");
    // Disable the disabling of the colorpicker since it horks keyboard
    // navigation.
    //if (useDefaultTextColor)
    //  this._textColorPicker.setAttribute("disabled", "true");
    //else
    //  this._textColorPicker.removeAttribute("disabled");
    this._useDefaultTextColorCheckbox.checked = useDefaultTextColor;
  },

  // Apply header and footer control changes to the prefs.
  onChangeBackground: function(aEvent) {
    let control = aEvent.target;
    let pref = control.parentNode.getAttribute("pref");
    let value = control.value;
    this._prefSvc.setCharPref(pref, value);
  },

  onSelectBackground: function(aEvent) {
    let control = aEvent.target;
    let pref = control.parentNode.getAttribute("pref");
    let fp = Cc["@mozilla.org/filepicker;1"].createInstance(Ci.nsIFilePicker);
    fp.init(window,
            this._stringBundle.getString("backgroundPickerDialogTitle"),
            Ci.nsIFilePicker.modeOpen);
    let result = fp.show();
    if (result == Ci.nsIFilePicker.returnOK) {
      this._prefSvc.setCharPref(pref, fp.fileURL.spec);
      control.value = fp.fileURL.spec;
    }
  },

  onChangeColor: function(aEvent) {
    this._prefSvc.setCharPref("extensions.personas.custom.textColor",
                              this._textColorPicker.color);
  },

  onChangeUseDefaultTextColor: function(aEvent) {
    // Setting the pref will trigger our pref observer, which will call
    // _applyPrefUseDefaultTextColor to update the UI accordingly.
    this._prefSvc.setBoolPref("extensions.personas.custom.useDefaultTextColor",
                              this._useDefaultTextColorCheckbox.checked);
  }
}

window.addEventListener("load", function() { CustomPersonaEditor.init() }, false);
