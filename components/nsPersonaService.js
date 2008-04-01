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

const PERSONAS_EXTENSION_ID = "personas@christopher.beard";

const LOAD_STATE_EMPTY = 0;
const LOAD_STATE_LOADING = 1;
const LOAD_STATE_LOADED = 2;

// In Firefox 3 we import modules using Cu.import, but in Firefox 2, which does
// not support modules, we use the subscript loader to load them as subscripts.
if ("import" in Cu) {
  Cu.import("resource://gre/modules/XPCOMUtils.jsm");
  Cu.import("resource://gre/modules/JSON.jsm");
}
else {
  let subscriptLoader = Cc["@mozilla.org/moz/jssubscript-loader;1"].
                        getService(Ci.mozIJSSubScriptLoader);
  // These have to be loaded using chrome: URLs to files inside one of the
  // chrome directories because the "personas" resource: alias isn't available
  // yet and can't be registered until later in the process, but we need the
  // XPCOMUtils module immediately.
  subscriptLoader.loadSubScript("chrome://personas/content/modules/XPCOMUtils.jsm");
  subscriptLoader.loadSubScript("chrome://personas/content/modules/JSON.jsm");
  subscriptLoader.loadSubScript("chrome://personas/content/modules/PrefCache.js");
}


//****************************************************************************//
// Helper Utilities

// Escape CSS special characters in unquoted URLs
// per http://www.w3.org/TR/CSS21/syndata.html#uri
function escapeCSSURL(aURLSpec) {
  return aURLSpec.replace(/[(),\s'"]/g, "\$&");
}

// Escape XML special characters.
function escapeXML(aString) {
  aString = aString.replace(/\&/g, "&amp;");
  aString = aString.replace(/</g, "&lt;");
  aString = aString.replace(/>/g, "&gt;");
  return aString;
}


//****************************************************************************//
// The Persona Service

function PersonaService() {
  this._init();
}

PersonaService.prototype = {
  //**************************************************************************//
  // XPCOM Plumbing

  classDescription:   "Persona Service",
  classID:            Components.ID("{efdd655c-51ac-4e5c-aa61-888b270436b8}"),
  contractID:         "@mozilla.org/personas/persona-service;1",
  // See note in PersonaController::startUp for why this is commented out.
  //_xpcom_categories:  [{ category: "app-startup", service: true }],
  QueryInterface:     XPCOMUtils.generateQI([Ci.nsIPersonaService,
                                             Ci.nsIObserver,
                                             Ci.nsIDOMEventListener,
                                             Ci.nsITimerCallback]),


  //**************************************************************************//
  // Convenience Getters

  // Observer Service
  get _obsSvc() {
    let obsSvc = Cc["@mozilla.org/observer-service;1"].
                 getService(Ci.nsIObserverService);
    this.__defineGetter__("_obsSvc", function() { return obsSvc });
    return this._obsSvc;
  },

  // Preference Service
  get _prefSvc() {
    // Enable both the nsIPrefBranch and the nsIPrefBranch2 interfaces
    // so we can both retrieve preferences and add observers.
    let prefSvc = Cc["@mozilla.org/preferences-service;1"].
                  getService(Ci.nsIPrefBranch).
                  QueryInterface(Ci.nsIPrefBranch2);
    this.__defineGetter__("_prefSvc", function() { return prefSvc });
    return this._prefSvc;
  },

  get _prefCache() {
    let prefCache = new PersonasPrefCache("");
    this.__defineGetter__("_prefCache", function() { return prefCache });
    return this._prefCache;
  },

  _getPref: function(aPrefName, aDefaultValue) {
    return this._prefCache.getPref(aPrefName, aDefaultValue);
  },

  get _hiddenWindow() {
    let hiddenWindow = Cc["@mozilla.org/appshell/appShellService;1"].
                       getService(Ci.nsIAppShellService).hiddenDOMWindow;
    this.__defineGetter__("_hiddenWindow", function() { return hiddenWindow });
    return this._hiddenWindow;
  },

  // The interval between consecutive persona reloads.  Measured in minutes,
  // with a default of 60 minutes (defined in defaults/preferences/personas.js)
  // and a minimum of one minute.
  get _reloadInterval() {
    let val = this._getPref("extensions.personas.reloadInterval");
    return val < 1 ? 1 : val;
  },

  // The interval between consecutive persona snapshots.  Measured in seconds,
  // with a default of 60 seconds (defined in defaults/preferences/personas.js)
  // and a minimum of one second.
  get _snapshotInterval() {
    let val = this._getPref("extensions.personas.snapshotInterval");
    return val < 1 ? 1 : val;
  },

  get _baseURL() {
    return this._getPref("extensions.personas.url");
  },

  get _locale() {
    switch(this._getPref("general.useragent.locale", "en-US")) {
      case 'ja':
      case 'ja-JP-mac':
        return "ja";
      default:
        return "en-US";
    }
  },


  //**************************************************************************//
  // Private Properties

  _delayedInitTimer: null,

  // The iframe that we add to the hidden window and into which we load a XUL
  // document that helps us to load the header and footer backgrounds.
  // Defined when the persona service is initialized.
  _personaLoader: null,

  // Objects that are responsible for loading the header and footer backgrounds
  // and providing them to application windows. Defined when the persona loader
  // is loaded.
  _headerLoader: null,
  _footerLoader: null,

  // The ID of the persona currently loaded into the persona loader.  This is
  // not necessarily the selected persona.  During persona preview, it is the
  // persona being previewed; if the selected persona is "random", then it is
  // the ID of the persona that was randomly selected; and if the selected
  // persona is "default", then it is null, since we don't use the loader to
  // load the default persona.
  _activePersona: null,

  // A timer that periodically reloads the lists of categories and personas
  // to incorporate updates to those lists.
  _reloadDataTimer: null,

  // A timer that periodically reloads the selected persona to incorporate
  // server-side changes to static and dynamic personas.  Defined when the
  // persona loader is loaded.
  _reloadPersonaTimer: null,

  // A timer that periodically snapshots the loaded persona so it incorporates
  // client-side changes to dynamic personas.  Instantiated once the persona
  // loader is ready to load the current persona, and reinitialized each time
  // we load the persona.
  _snapshotPersonaTimer: null,


  //**************************************************************************//
  // XPCOM Interfaces

  // nsIPersonaService

  // The lists of categories and personas retrieved from the server via JSON,
  // as nsISupports objects whose wrappedJSObject property contains the data.
  // Loaded upon service initialization and reloaded periodically thereafter.
  categories: null,
  personas: null,

  // The latest header and footer URLs and text color.
  headerURL: null,
  footerURL: null,
  firstrunURL: null,
  textColor: null,
  accentColor: null,

  /**
   * Display the given persona without making it the selected persona.  Useful
   * for showing users who are browsing a directory of personas what a given
   * persona will look like when selected, f.e. on mouseover.  Consumers who
   * call this method should call resetPersona when the user stops previewing
   * the persona, f.e. on mouseout.  Otherwise the displayed persona will revert
   * to the selected persona when it is reloaded, the browser is restarted,
   * or the user selects another persona.
   */
  previewPersona: function(aPersonaID) {
    this._switchToPersona(aPersonaID);
  },

  /**
   * Reset the displayed persona to the selected persona.  Useful for returning
   * to the selected persona after previewing personas.  Also called by the pref
   * observer when the selected persona changes.
   */
  resetPersona: function() {
    let personaID = this._getPref("extensions.personas.selected", "default");
    this._switchToPersona(personaID);
  },

  // nsIObserver

  observe: function(subject, topic, data) {
    switch (topic) {
      // See note in PersonaController::startUp for why these are commented out.
      //case "app-startup":
      //  this._obsSvc.addObserver(this, "final-ui-startup", false);
      //  break;
      //
      //case "final-ui-startup":
      //  this._obsSvc.removeObserver(this, "final-ui-startup");
      //  this._init();
      //  break;

      case "quit-application":
        this._obsSvc.removeObserver(this, "quit-application");
        this._destroy();
        break;

      case "nsPref:changed":
        switch (data) {
          // If any of the prefs that determine which persona is selected
          // have changed, then switch to the selected persona.
          // FIXME: figure out how to call resetPersona only once when both
          // "selected" and "category" preferences are set one after the other
          // by PersonaController._setPersona.  Maybe, when we're setting both
          // the selected persona and some other preferences, we could first
          // set the selected persona to "disabled", then we could make
          // the other necessary changes (which this observer ignores while
          // the selected persona is "disabled"), and finally we could set
          // the selected persona to the new value.
          case "extensions.personas.selected":
          case "extensions.personas.custom.headerURL":
          case "extensions.personas.custom.footerURL":
            this.resetPersona();
            break;

          case "extensions.personas.category":
            if (this._getPref("extensions.personas.selected") == "random")
              this.resetPersona();
            break;

          case "extensions.personas.custom.textColor":
          case "extensions.personas.custom.useDefaultTextColor":
            this._onChangeCustomTextColor();
            break;

          case "extensions.personas.custom.accentColor":
          case "extensions.personas.custom.useDefaultAccentColor":
            this._onChangeCustomAccentColor();
            break;

        }
        break;
    }
  },

  // nsIDOMEventListener

  handleEvent: function(aEvent) {
    // The iframes inside the persona loader document also generate pageshow
    // events, but the persona loader isn't loaded until we get the pageshow
    // event for the persona loader itself, so ignore the events on the iframes
    // inside it.
    if (aEvent.target != this._personaLoader.contentDocument)
    // Another way to do this (not sure which is better):
    //if (aEvent.target.documentURI != "chrome://personas/content/personaLoader.xul")
      return;

    try {
      this.onPersonaLoaderLoad(aEvent);
    }
    finally {
      this._personaLoader.removeEventListener("pageshow", this, false);
    }
  },

  // nsITimerCallback

  notify: function(aTimer) {
    switch(aTimer) {
      case this._delayedInitTimer:
        this._delayedInit();
        break;
      case this._reloadDataTimer:
        this._loadData();
        break;
      case this._reloadPersonaTimer:
        let personaID = this._getPref("extensions.personas.selected", "default");
        this._loadPersona(personaID);
        break;
      case this._snapshotPersonaTimer:
        this._snapshotPersona();
        break;
      case this._onLoadDelayedTimer:
        this._onLoadDelayed();
        break;
    }
  },


  //**************************************************************************//
  // Initialization & Destruction

  _init: function() {
    // Observe quit so we can destroy ourselves.
    this._obsSvc.addObserver(this, "quit-application", false);

    // Set up a resource alias to the extension directory and then import our
    // own modules, which we couldn't import earlier at parse time because the
    // components we needed to set up the alias were not yet available.
    this._registerResourceAlias();
    if ("import" in Cu)
      Cu.import("resource://personas/chrome/content/modules/PrefCache.js");

    // For backwards compatibility, migrate the old manualPath preference
    // to the new custom.headerURL preference.
    // FIXME: remove this once enough users have updated to a version newer
    // than 0.9.2.
    if (this._prefSvc.prefHasUserValue("extensions.personas.manualPath")) {
      let path = this._getPref("extensions.personas.manualPath");
      this._prefSvc.setCharPref("extensions.personas.custom.headerURL", "file://" + path);
      this._prefSvc.clearUserPref("extensions.personas.manualPath");
    }

    // For backwards compatibility, migrate the old custom.toolbarURL preference
    // to the new custom.headerURL preference.
    // FIXME: remove this once enough users have updated to a version newer
    // than 0.9.4.
    if (this._prefSvc.prefHasUserValue("extensions.personas.custom.toolbarURL")) {
      let url = this._getPref("extensions.personas.custom.toolbarURL");
      this._prefSvc.setCharPref("extensions.personas.custom.headerURL", url);
      this._prefSvc.clearUserPref("extensions.personas.custom.toolbarURL");
    }

    // For backwards compatibility, migrate the old custom.statusbarURL preference
    // to the new custom.footerURL preference.
    // FIXME: remove this once enough users have updated to a version newer
    // than 0.9.4.
    if (this._prefSvc.prefHasUserValue("extensions.personas.custom.statusbarURL")) {
      let url = this._getPref("extensions.personas.custom.statusbarURL");
      this._prefSvc.setCharPref("extensions.personas.custom.footerURL", url);
      this._prefSvc.clearUserPref("extensions.personas.custom.statusbarURL");
    }

    // Observe changes to the selected persona that happen in other windows
    // or by users twiddling the preferences directly.
    this._prefSvc.addObserver("extensions.personas.", this, false);

    // Delay initialization of the persona loader to give the application
    // time to finish loading the hidden window, which at this point still has
    // about:blank loaded in it.
    // XXX Now that we delay initialization until PersonaController:startUp,
    // do we still need to delay initialization of the persona loader?
    this._delayedInitTimer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);
    this._delayedInitTimer.initWithCallback(this, 0, Ci.nsITimer.TYPE_ONE_SHOT);
  },

  _delayedInit: function() {
    this._delayedInitTimer.cancel();
    this._delayedInitTimer = null;

    // Load the persona loader.
    this._loadPersonaLoader();

    // Load the lists of categories and personas, and define a timer
    // that periodically reloads them.
    this._loadData();
    this._reloadDataTimer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);
    this._reloadDataTimer.initWithCallback(this,
                                           30 * 60 * 1000, // 30 minutes
                                           Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  _destroy: function() {
    if (this._headerLoader)
      this._headerLoader.reset();
    this._headerLoader = null;

    if (this._footerLoader)
      this._footerLoader.reset();
    this._footerLoader = null;

    if (this._reloadPersonaTimer)
      this._reloadPersonaTimer.cancel();
    this._reloadPersonaTimer = null;

    if (this._snapshotPersonaTimer)
      this._snapshotPersonaTimer.cancel();
    this._snapshotPersonaTimer = null;

    this._reloadDataTimer.cancel();
    this._reloadDataTimer = null;

    this._personaLoader = null;

    this._prefSvc.removeObserver("extensions.personas.", this);
  },

  /**
   * Register the resource://personas/ alias if it isn't already registered.
   * We make it point to the top-level directory for the extension, so we can
   * access files anywhere in the extension.
   */
  _registerResourceAlias: function() {
    let ioSvc = Cc["@mozilla.org/network/io-service;1"].
                getService(Ci.nsIIOService);
    let resProt = ioSvc.getProtocolHandler("resource").
                  QueryInterface(Ci.nsIResProtocolHandler);
    if (!resProt.hasSubstitution("personas")) {
      let extMgr = Cc["@mozilla.org/extensions/manager;1"].
                   getService(Ci.nsIExtensionManager);
      let loc = extMgr.getInstallLocation(PERSONAS_EXTENSION_ID);
      let extD = loc.getItemLocation(PERSONAS_EXTENSION_ID);
      resProt.setSubstitution("personas", ioSvc.newFileURI(extD));
    }
  },


  //**************************************************************************//
  // Data and Persona Loader Loading

  _personaLoaderLoaded: false,
  _personasLoaded: false,

  _loadPersonaLoader: function() {
    // Create the persona loader and attach it to the hidden window.
    this._personaLoader = this._hiddenWindow.document.createElement("iframe");
    this._personaLoader.setAttribute("id", "personaLoader");
    this._personaLoader.setAttribute("src", "chrome://personas/content/personaLoader.xul");
    this._personaLoader.addEventListener("pageshow", this, false);
    this._hiddenWindow.document.documentElement.appendChild(this._personaLoader);
  },

  onPersonaLoaderLoad: function() {
    // Define the reload and snapshot timers.  We only do this once per session,
    // after which we reuse the same timers for performance, reinitializing them
    // as needed.
    this._reloadPersonaTimer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);
    this._snapshotPersonaTimer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);

    // Initialize the header and footer background loaders.
    let t = this;
    let loadHeaderCallback = function() { t.onLoadedHeader() };
    let loadFooterCallback = function() { t.onLoadedFooter() };
    this._headerLoader = new HeaderLoader(loadHeaderCallback);
    this._footerLoader = new FooterLoader(loadFooterCallback);

    // We need both the JSON feed of personas and the persona loader
    // to be loaded before we can load the persona.
    this._personaLoaderLoaded = true;
    if (this._personasLoaded)
      this._onLoaderAndPersonasLoaded();
  },

  _loadData: function() {
    let t = this;
    this._makeRequest(this._baseURL + this._locale + "/personas_categories.dat",
                      function(evt) { t.onCategoriesLoad(evt) });
    this._makeRequest(this._baseURL + this._locale + "/personas_all.dat",
                      function(evt) { t.onPersonasLoad(evt) });
  },

  _makeRequest: function(aURL, aLoadCallback) {
    let request = Cc["@mozilla.org/xmlextras/xmlhttprequest;1"].createInstance();

    request = request.QueryInterface(Ci.nsIDOMEventTarget);
    request.addEventListener("load", aLoadCallback, false);

    request = request.QueryInterface(Ci.nsIXMLHttpRequest);
    request.open("GET", aURL, true);
    request.send(null);
  },

  onCategoriesLoad: function(aEvent) {
    let request = aEvent.target;

    // XXX Try to reload again sooner?
    if (request.status != 200)
      throw("problem loading categories: " + request.status + " - " + request.statusText);

    let categories = JSON.fromString(request.responseText).categories;
    this.categories = { wrappedJSObject: categories };

    this._prefSvc.setCharPref("extensions.personas.lastcategoryupdate",
                              new Date().getTime());
  },

  onPersonasLoad: function(aEvent) {
    let request = aEvent.target;

    // XXX Try to reload again sooner?
    if (request.status != 200)
      throw("problem loading personas: " + request.status + " - " + request.statusText);

    // The "personas" member of the JSON response object is an array of hashes
    // where each hash represents one persona.
    let personas = JSON.fromString(request.responseText).personas;

    // To share this with (JavaScript) XPCOM consumers without having to create
    // an complex XPCOM interface to it, we just pass it as a wrapped JS object.
    this.personas = { wrappedJSObject: personas };

    this._prefSvc.setCharPref("extensions.personas.lastlistupdate",
                              new Date().getTime());

    // We need both the JSON feed of personas and the persona loader
    // to be loaded before we can load the selected persona and apply it
    // to the browser windows.
    this._personasLoaded = true;
    if (this._personaLoaderLoaded)
      this._onLoaderAndPersonasLoaded();
  },

  _onLoaderAndPersonasLoaded: function() {
    // FIXME: store all relevant information about the persona in preferences
    // so we don't need the personas data to load the persona.

    // Now that the persona loader and the data is loaded, we load the persona.
    let personaID = this._getPref("extensions.personas.selected", "default");
    this._switchToPersona(personaID);
  },


  //**************************************************************************//
  // Persona Loading

  /**
   * Switch to the specified persona.  This happens on startup, when the user
   * selects a persona, and when the user previews a persona or resets to the
   * elected persona.
   */
  _switchToPersona: function(aPersonaID) {
    this._reloadPersonaTimer.cancel();
    this._snapshotPersonaTimer.cancel();
    this._headerLoader.reset();
    this._footerLoader.reset();
    this.textColor = null;
    this.accentColor = null;

    if (this._loadState == LOAD_STATE_LOADING) {
      // FIXME: cancel the requests currently in process in the header
      // and footer iframes.
      this._obsSvc.notifyObservers(null,
                                   "personas:personaLoadFinished",
                                   this._activePersona);
    }
    this._activePersona = null;

    if (aPersonaID == "default") {
      this._obsSvc.notifyObservers(null, "personas:defaultPersonaSelected", null);
      return;
    }

    this._loadPersona(aPersonaID);
    this._reloadPersonaTimer.initWithCallback(this,
                                              this._reloadInterval * 60 * 1000,
                                              Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  /**
   * Load the given persona.  This happens on startup, every time the user
   * switches personas, and periodically at the reload interval to incorporate
   * server-side changes to dynamic personas.  This also happens when the user
   * previews a persona or resets their browser to the selected persona.
   */
  _loadPersona: function(aPersonaID) {
    // Cancel the snapshot timer.
    this._snapshotPersonaTimer.cancel();

    // If we're loading the "random" persona, pick a persona at random
    // from the selected category.
    if (aPersonaID == "random")
      aPersonaID = this._getRandomPersona();

    this._activePersona = aPersonaID;

    this._obsSvc.notifyObservers(null, "personas:personaLoadStarted", aPersonaID);
    this._loadState = LOAD_STATE_LOADING;
    this._headerLoader.load(aPersonaID, this._getHeaderURL(aPersonaID));
    this._footerLoader.load(aPersonaID, this._getFooterURL(aPersonaID));
  },

  onLoadedHeader: function() {
    if (this._footerLoader.loadState == LOAD_STATE_LOADED)
      this._onLoadedPersona();
  },

  onLoadedFooter: function() {
    if (this._headerLoader.loadState == LOAD_STATE_LOADED)
      this._onLoadedPersona();
  },

  _onLoadedPersona: function() {
    this._loadState = LOAD_STATE_LOADED;
    this._obsSvc.notifyObservers(null,
                                 "personas:personaLoadFinished",
                                 this._activePersona);

    this._snapshotPersona();

    // Start the snapshot timer.
    this._snapshotPersonaTimer.initWithCallback(this,
                                                this._snapshotInterval * 1000,
                                                Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  _snapshotPersona: function() {
    this.headerURL = this._headerLoader.getSnapshotURL();
    this.footerURL = this._footerLoader.getSnapshotURL();
    this.textColor = this._getTextColor(this._activePersona);
    this.accentColor = this._getAccentColor(this._activePersona);

    // Notify application windows so they update their appearance to reflect
    // the new versions of the background images.
    this._obsSvc.notifyObservers(null, "personas:activePersonaUpdated", null);
  },

  _getRandomPersona: function() {
    let personaID;

    let lastRandomID = this._getPref("extensions.personas.lastrandom");

    // If we have the list of personas, use it to pick a random persona
    // from the selected category.
    if (this.personas) {
      let categoryID = this._getPref("extensions.personas.category");
      personaID = this._getRandomPersonaByCategory(categoryID, lastRandomID);
    }

    // If we were able to pick a random persona from the selected category,
    // then save that to preferences as the last random persona.  Otherwise
    // just reuse the last random persona.
    if (personaID)
      this._prefSvc.setCharPref("extensions.personas.lastrandom", personaID);
    else
      personaID = lastRandomID;

    return personaID;
  },

  _getRandomPersonaByCategory: function(categoryID, lastRandomID) {
    let personas = this.personas.wrappedJSObject;
    let subList = new Array();
    let k = 0;

    // Build the list of possible personas to select from
    for each (let persona in personas) {
      let needle = categoryID;
      let haystack = persona.menu;

      if (haystack.search(needle) == -1)
        continue;

      subList[k++] = persona;
    }

    // Get a random item, trying up to five times to get one that is different
    // from the currently-selected item in the category (if any).
    // We use Math.floor instead of Math.round to pick a random number because
    // the JS reference says Math.round returns a non-uniform distribution
    // <http://developer.mozilla.org/en/docs/Core_JavaScript_1.5_Reference:Global_Objects:Math:random#Examples>.
    let randomIndex, randomItem;
    for (let i = 0; i < 5; i++) {
      randomIndex = Math.floor(Math.random() * subList.length);
      randomItem = subList[randomIndex];
      if (randomItem.id != lastRandomID)
        break;
    }

    return randomItem.id; 
  },

  // FIXME: index personas after retrieving them and make the index (or a method
  // for accessing it) available to chrome JS in addition to this service's code
  // so we don't have to iterate through personas all the time.
  _getPersona: function(aPersonaID) {
    for each (let persona in this.personas.wrappedJSObject)
      if (persona.id == aPersonaID)
        return persona;

    return null;
  },

  _getHeaderURL: function(aPersonaID) {
    // Custom persona whose header and footer are in local files specified by
    // the user in preferences.
    if (aPersonaID == "manual")
      return this._getPref("extensions.personas.custom.headerURL",
                           "chrome://personas/content/header-default.jpg");

    let persona = this._getPersona(aPersonaID);

    if (persona.baseURL)
      return persona.baseURL + "?action=header";

    return "chrome://personas/content/header-default.jpg";
  },

  _getFooterURL: function(aPersonaID) {
    // Custom persona whose header and footer are in local files specified by
    // the user in preferences.
    if (aPersonaID == "manual")
      return this._getPref("extensions.personas.custom.footerURL",
                           "chrome://personas/content/footer-default.jpg");

    let persona = this._getPersona(aPersonaID);

    if (persona.baseURL)
      return persona.baseURL + "?action=footer";

    return "chrome://personas/content/footer-default.jpg";
  },

  _getTextColor: function(aPersonaID) {
    // Custom persona whose text color is specified by the user in a preference.
    if (aPersonaID == "manual" &&
        !this._getPref("extensions.personas.custom.useDefaultTextColor"))
      return this._getPref("extensions.personas.custom.textColor");

    // Persona whose JSON record specifies a text color or a "dark" property.
    let persona = this._getPersona(aPersonaID);
    if (persona) {
      if (persona.textColor)
        return persona.textColor;
  
      if (typeof persona.dark != "undefined" && persona.dark == "true")
        return "#ffffff";
    }

    // Dynamic HTML/XML persona whose root element has a computed color.
    // XXX Should we only use a color dynamically set via JS  (i.e. the value
    // of docElement.style.color)?
    let headerDoc = this._headerLoader._iframe.contentDocument;
    if (headerDoc) {
      let docElement = headerDoc.documentElement;
      if (docElement) {
        let style = headerDoc.defaultView.getComputedStyle(docElement, null);
        let color = style.getPropertyValue("color");
        if (color)
          return color;
      }
    }

    // The default text color: black.
    return "#000000";
  },

  _onChangeCustomTextColor: function() {
    if (this._activePersona != "manual")
      return;

    this.textColor = this._getTextColor(this._activePersona);
    this._obsSvc.notifyObservers(null, "personas:activePersonaUpdated", null);
  },

  _getAccentColor: function(aPersonaID) {
    // Custom persona whose accent color is specified by the user in a preference.
    if (aPersonaID == "manual" &&
        !this._getPref("extensions.personas.custom.useDefaultAccentColor"))
      return this._getPref("extensions.personas.custom.accentColor");

    let persona = this._getPersona(aPersonaID);
    if(persona) {
      if (persona.accentColor) 
        return persona.accentColor;
    }

    // The default accent color: gray.
    return "#C9C9C9";
  },

  _onChangeCustomAccentColor: function() {
    if (this._activePersona != "manual")
      return;

    this.accentColor = this._getAccentColor(this._activePersona);
    this._obsSvc.notifyObservers(null, "personas:activePersonaUpdated", null);
  }

};


function BackgroundLoader(aLoadCallback) {
  this._loadCallback = aLoadCallback;
}

BackgroundLoader.prototype = {
  //**************************************************************************//
  // Convenience Getters

  get _hiddenWindow() {
    let hiddenWindow = Cc["@mozilla.org/appshell/appShellService;1"].
                       getService(Ci.nsIAppShellService).hiddenDOMWindow;
    this.__defineGetter__("_hiddenWindow", function() { return hiddenWindow });
    return this._hiddenWindow;
  },

  get _personaLoader() {
    return this._hiddenWindow.document.getElementById("personaLoader");
  },


  //**************************************************************************//
  // XPCOM Interfaces

  // nsISupports

  QueryInterface: XPCOMUtils.generateQI([Ci.nsIDOMEventListener,
                                         Ci.nsITimerCallback]),

  // nsIDOMEventListener

  handleEvent: function(aEvent) {
    // We only care about events that happen to the top-level document
    // loaded into the iframe, not to any documents loaded inside that
    // document (i.e. images, frames, etc.).
    if (aEvent.target != this._iframe.contentDocument)
      return;

    try {
      this._onLoad();
    }
    finally {
      this._iframe.removeEventListener("pageshow", this, false);
    }
  },

  // nsITimerCallback

  notify: function(aTimer) {
    switch(aTimer) {
      case this._onLoadDelayedTimer:
        this._onLoadDelayed();
        break;
    }
  },


  //**************************************************************************//
  // Public Interface

  loadState: LOAD_STATE_EMPTY,

  load: function(aPersonaID, aURL) {
    // If the URL is to an image file, then load it as the background image
    // of a XUL window so it appears at its original size instead of being
    // resized to fit the visible portion of the iframe.  We should figure out
    // a better way to do this, since detecting images via file extensions
    // isn't particularly robust.  Ideally we should be able to turn off image
    // resizing for the iframe just as we can turn off JavaScript parsing
    // or image loading for it.
    // FIXME: file a bug on the ability to turn off image resizing on an
    // iframe-specific basis.
    let url = aURL;
    if (/\.(jpg|jpeg|png|gif)$/i.test(url)) {
      // If this is a custom persona using a local image file, then load it
      // using the chrome-privileged image loader, which we need to use in order
      // to be able to load the local file.
      if (aPersonaID == "manual" && /^file:/.test(url))
        url = "chrome://personas/content/imageLoader.xul?" +
              "url=" + encodeURIComponent(url) + "&" +
              "position=" + encodeURIComponent(this._position);

      // Otherwise, load it using an unprivileged image loader constructed
      // inside a data: URL so it can't do anything malicious.  This protects
      // us against issues like personas providing javascript: URLs that could
      // take advantage of a chrome-privileged loader to access local files.
      // Because CSS image loads don't block the load/pageshow events in Firefox 2,
      // we load the image in an image tag, which does block those events, and then
      // set the background-image property once the image has finished loading.
      // FIXME: use a template to simplify this code?
      else
        url = 'data:application/vnd.mozilla.xul+xml,' +
              '<window id="window" xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"\n' +
              '        onload="document.documentElement.style.backgroundImage = \'url(' + escapeXML(escapeCSSURL(url)) + ')\'"\n' +
              '        style="background-repeat: no-repeat;\n' +
              '               background-position: ' + this._position + ';" flex="1">\n' +
              '  <image collapsed="true" src="' + escapeXML(url) + '"/>\n' +
              '</window>\n';
    }

    // Listen for pageshow on the iframe so we know when it finishes loading
    // the background.
    this._iframe.addEventListener("pageshow", this, false);

    // Note: we use loadURI instead of just setting the src attribute
    // since setting the attribute doesn't reload the page if we set it
    // to its current value, and we always want to reload the page
    // at this point so we can periodically update a dynamic persona.
    this._iframe.webNavigation.loadURI(url,
                                       Ci.nsIWebNavigation.LOAD_FLAGS_NONE,
                                       null,
                                       null,
                                       null);
  },

  reset: function() {
    this.loadState = LOAD_STATE_EMPTY;
  },

  /**
   * Take a snapshot of the iframe by drawing its contents onto the canvas,
   * then convert the snapshot into a data: URL containing an image.
   * 
   * We set the starting point for the snapshot to the top/left corner
   * of the visible portion of the page so that we show what the URL intends
   * to show when it contains an anchor (#something) that scrolls the page
   * to a particular point on the page while it's being loaded.
   * 
   * We specify an alpha channel in the background color to preserve
   * transparency in images.
   */
  getSnapshotURL: function() {
    let context = this._canvas.getContext("2d");
    let window = this._iframe.contentWindow;
    context.drawWindow(window,
                       window.pageXOffset,
                       window.pageYOffset,
                       window.pageXOffset + 3000,
                       window.pageYOffset + 200,
                       "rgba(0,0,0,0)");
    let url = this._canvas.toDataURL();

    // Clear the canvas so it's ready for the next snapshot.
    context.clearRect(0, 0, 3000, 200);

    return url;
  },


  //**************************************************************************//
  // Private Properties & Methods

  _loadCallback: null,

  _onLoad: function() {
    // Delay calling the load callback to give the rendering engine time
    // to finish displaying the content in the iframe.  I'm not sure why this
    // is necessary, since the content should be rendered by the time we get
    // the pageshow event, but for some reason that doesn't work (the snapshot
    // turns out blank).
    this._onLoadDelayedTimer = Cc["@mozilla.org/timer;1"].
                               createInstance(Ci.nsITimer);
    this._onLoadDelayedTimer.initWithCallback(this,
                                              0,
                                              Ci.nsITimer.TYPE_ONE_SHOT);

    return;
  },

  _onLoadDelayed: function() {
    this.loadState = LOAD_STATE_LOADED;
    try {
      this._loadCallback(this);
    }
    finally {
      this._onLoadDelayedTimer.cancel();
      this._onLoadDelayedTimer = null;
    }
  }

};


// HeaderLoader and FooterLoader subclass BackgroundLoader to define properties
// specific to each area.

function HeaderLoader(aLoadCallback) {
  BackgroundLoader.call(this, aLoadCallback);
}

HeaderLoader.prototype = {
  __proto__: BackgroundLoader.prototype,

  _position: "top right",

  get _iframe() {
    return this._personaLoader.contentDocument.getElementById("headerIframe");
  },

  get _canvas() {
    return this._personaLoader.contentDocument.getElementById("headerCanvas");
  }
};

function FooterLoader(aLoadCallback) {
  BackgroundLoader.call(this, aLoadCallback);
}

FooterLoader.prototype = {
  __proto__: BackgroundLoader.prototype,

  _position: "bottom left",

  get _iframe() {
    return this._personaLoader.contentDocument.getElementById("footerIframe");
  },

  get _canvas() {
    return this._personaLoader.contentDocument.getElementById("footerCanvas");
  }
};


//****************************************************************************//
// More XPCOM Plumbing

function NSGetModule(compMgr, fileSpec) {
  return XPCOMUtils.generateModule([PersonaService]);
}
