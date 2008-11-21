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

  get _customName() {
    let customName = document.getElementById("customName");
    delete this._customName;
    this._customName = customName;
    return this._customName;
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

  get _accentColorPicker() {
    let accentColorPicker = document.getElementById("accentColorPicker");
    delete this._accentColorPicker;
    this._accentColorPicker = accentColorPicker;
    return this._accentColorPicker;
  },

  get _personaSvc() {
    let personaSvc = Cc["@mozilla.org/personas/persona-service;1"].
                     getService(Ci.nsIPersonaService);
    delete this._personaSvc;
    this._personaSvc = personaSvc;
    return this._personaSvc;
  },

  _getPersona: function(aPersonaID) {
    for each (let persona in this._personaSvc.personas.wrappedJSObject)
      if (persona.id == aPersonaID)
        return persona;

    return null;
  },

  get _selectedPersona() {
    return this._getPref("selected", "default");
  },

  _getCategory: function(aCategoryID) {
    for each (let category in this._personaSvc.categories.wrappedJSObject)
      if (category.id == aCategoryID)
        return category;

    return null;
  },

  // Preference Service
  get _prefSvc() {
    let prefSvc = Cc["@mozilla.org/preferences-service;1"].
                  getService(Ci.nsIPrefService).
                  getBranch("extensions.personas.");
    delete this._prefSvc;
    this._prefSvc = prefSvc;
    return this._prefSvc;
  },

  get _prefCache() {
    let prefCache = new PersonasPrefCache("extensions.personas.", this);
    delete this._prefCache;
    this._prefCache = prefCache;
    return this._prefCache;
  },

  _getPref: function(aPrefName, aDefaultValue) {
    return this._prefCache.getPref(aPrefName, aDefaultValue);
  },


  //**************************************************************************//
  // Initialization

  init: function() {
    this._headerURL.value = this._getPref("custom.headerURL", "");
    this._footerURL.value = this._getPref("custom.footerURL", "");
    this._customName.value = this._getPref("custom.customName", "");
    this._textColorPicker.color = this._getPref("custom.textColor", "#000000");
    this._accentColorPicker.color = this._getPref("custom.accentColor", "#C9C9C9");

    this._updatePreview();
  },


  //**************************************************************************//
  // XPCOM Interfaces

  // nsIObserver

  observe: function(aSubject, aTopic, aData) {
    switch(aTopic) {
      case "nsPref:changed":
        switch (aData) {
          case "custom.headerURL":
            this._headerURL.value = this._getPref("custom.headerURL", "");
            break;
          case "custom.footerURL":
            this._footerURL.value = this._getPref("custom.footerURL", "");
            break;
          case "custom.textColor":
            this._textColorPicker.color = this._getPref("custom.textColor", "#000000");
            break;
          case "custom.accentColor":
            this._accentColorPicker.color = this._getPref("custom.accentColor", "#C9C9C9");
            break;
        }
        break;
    }
  },

  //**************************************************************************//

  _updatePreview: function() {
      this._personaSvc.previewPersona("manual");
  },

  onChangeName: function(aEvent) {
      let control = aEvent.target;
      let pref = control.parentNode.getAttribute("pref");
      // Trim leading and trailing whitespace.
      let value = control.value.replace(/^\s*|\s*$/g, "");
      if (value == "")
	  this._prefSvc.setCharPref(pref, "Custom Persona");
      else
          this._prefSvc.setCharPref(pref, value);
  },

  // Apply header and footer control changes to the prefs.
  onChangeBackground: function(aEvent) {
    let control = aEvent.target;
    let pref = control.parentNode.getAttribute("pref");
    // Trim leading and trailing whitespace.
    let value = control.value.replace(/^\s*|\s*$/g, "");
    if (value == "")
      this._prefSvc.clearUserPref(pref);
    else
      this._prefSvc.setCharPref(pref, value);
    this._updatePreview();
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
      this._updatePreview();
    }
  },

  onChangeTextColor: function(aEvent) {
    this._prefSvc.setCharPref("custom.textColor", this._textColorPicker.color);
    this._personaSvc.resetPersona();
    this._updatePreview();
  },

  onSetDefaultTextColor: function(aEvent) {
    this._prefSvc.setCharPref("custom.textColor", "#000000");
    this.onChangeTextColor();
  },

  onChangeAccentColor: function(aEvent) {
    this._prefSvc.setCharPref("custom.accentColor", this._accentColorPicker.color);
    this._personaSvc.resetPersona();
    this._updatePreview();
  },

  onSetDefaultAccentColor: function(aEvent) {
    this._prefSvc.setCharPref("custom.accentColor", "#C9C9C9");
    this.onChangeAccentColor();
  },

  onClose: function() {
    this._personaSvc.resetPersona();
    window.close();
  },

  onApply: function() {
    this._prefSvc.setCharPref("selected", "manual");
    window.close();
  }
};

window.addEventListener("load", function() { CustomPersonaEditor.init() }, false);
