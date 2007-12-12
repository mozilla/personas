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


// Load the JavaScript code that will generate the XPCOM plumbing and eval
// the JSON data we download.  If we're in Firefox 3, we import the code as
// JavaScript modules; in Firefox 2 we use the subscript loader to load it
// as subscripts.
if ("import" in Cu) {
  Cu.import("resource://gre/modules/XPCOMUtils.jsm");
  Cu.import("resource://gre/modules/JSON.jsm");
}
else {
  var subscriptLoader = Cc["@mozilla.org/moz/jssubscript-loader;1"].
                        getService(Ci.mozIJSSubScriptLoader);
  subscriptLoader.loadSubScript("chrome://personas/content/XPCOMUtils.jsm");
  subscriptLoader.loadSubScript("chrome://personas/content/JSON.jsm");
}


const PERSONAS_UPDATE_INTERVAL = (30 * 60 * 1000); // 30 minutes


function PersonaService() {
  this._init();
}

PersonaService.prototype = {
  //**************************************************************************//
  // XPCOM Plumbing

  classDescription: "Persona Service",
  classID:          Components.ID("{efdd655c-51ac-4e5c-aa61-888b270436b8}"),
  contractID:       "@mozilla.org/personas/persona-service;1",
  QueryInterface:   XPCOMUtils.generateQI([Ci.nsIPersonaService]),


  // Observer Service
  get _obsSvc() {
    var obsSvc = Cc["@mozilla.org/observer-service;1"].
                 getService(Ci.nsIObserverService);
    this.__defineGetter__("_obsSvc", function() { return obsSvc });
    return this._obsSvc;
  },

  // Preference Service
  get _prefSvc() {
    var prefSvc = Cc["@mozilla.org/preferences-service;1"].
                  getService(Ci.nsIPrefBranch);
    this.__defineGetter__("_prefSvc", function() { return prefSvc });
    return this._prefSvc;
  },

  /**
   * Get the value of a pref, if any; otherwise, get the default value.
   *
   * @param   prefName
   * @param   defaultValue
   * @returns the value of the pref, if any; otherwise, the default value
   */
  _getPref: function(prefName, defaultValue) {
    var prefSvc = this._prefSvc;

    try {
      switch (prefSvc.getPrefType(prefName)) {
        case Ci.nsIPrefBranch.PREF_STRING:
          return prefSvc.getCharPref(prefName);
        case Ci.nsIPrefBranch.PREF_INT:
          return prefSvc.getIntPref(prefName);
        case Ci.nsIPrefBranch.PREF_BOOL:
          return prefSvc.getBoolPref(prefName);
      }
    }
    catch (ex) {}

    return defaultValue;
  },

  _init: function() {
    this._obsSvc.addObserver(this, "xpcom-shutdown", false);

    // Update the category/persona lists immediately.
    this.updateData();

    // Create a timer that updates the category/persona lists periodically.
    this._timer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);
    var callback = {
      _personaSvc: this,
      notify: function() { this._personaSvc.updateData() }
    };
    this._timer.initWithCallback(callback,
                                 PERSONAS_UPDATE_INTERVAL,
                                 Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  _destroy: function() {
    this._obsSvc.removeObserver(this, "xpcom-shutdown");
    this._timer.cancel();
    this._timer = null;
  },

  // nsIObserver
  observe: function(subject, topic, data) {
    switch (topic) {
      case "xpcom-shutdown":
        this._destroy();
        break;
    }
  },

  getLocale: function() {
    switch(this._getPref("general.useragent.locale", "en-US")) {
      case 'ja':
      case 'ja-JP-mac':
        return "ja";
      default:
        return "en-US";
    }
  },

  updateData: function() {
    var baseURL = this._getPref("extensions.personas.url");
    var locale = this.getLocale();

    var t = this;
    this._makeRequest(baseURL + locale + "/personas_categories.dat",
                      function(evt) { t.onCategoriesLoad(evt) });
    this._makeRequest(baseURL + locale + "/personas_all.dat",
                      function(evt) { t.onPersonasLoad(evt) });
  },

  _makeRequest: function(aURL, aLoadCallback) {
    var request = Cc["@mozilla.org/xmlextras/xmlhttprequest;1"].createInstance();

    request = request.QueryInterface(Ci.nsIDOMEventTarget);
    request.addEventListener("load", aLoadCallback, false);

    request = request.QueryInterface(Ci.nsIXMLHttpRequest);
    request.open("GET", aURL, true);
    request.send(null);
  },

  onCategoriesLoad: function(aEvent) {
    var request = aEvent.target;

    // XXX Try to reload again sooner?
    if (request.status != 200)
      throw("problem loading categories: " + request.status + " - " + request.statusText);

    var categories = JSON.fromString(request.responseText).categories;
    this.categories = { wrappedJSObject: categories };

    this._prefSvc.setCharPref("extensions.personas.lastcategoryupdate",
                              new Date().getTime());
  },

  onPersonasLoad: function(aEvent) {
    var request = aEvent.target;

    // XXX Try to reload again sooner?
    if (request.status != 200)
      throw("problem loading personas: " + request.status + " - " + request.statusText);

    var personas = JSON.fromString(request.responseText).personas;
    this.personas = { wrappedJSObject: personas };

    this._prefSvc.setCharPref("extensions.personas.lastlistupdate",
                              new Date().getTime());
  }
}

//****************************************************************************//
// More XPCOM Plumbing

function NSGetModule(compMgr, fileSpec) {
  return XPCOMUtils.generateModule([PersonaService]);
}

/*
var PersonaController = {
	init: function() {
		this._observerSvc.addObserver();
		118     // Observe profile-before-change so we can switch to the datasource
119     // in the new profile when the user changes profiles.
120     this._observerSvc.addObserver(this, "profile-before-change", false);

	}
}
*/
