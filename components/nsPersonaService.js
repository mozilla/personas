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
  let subscriptLoader = Cc["@mozilla.org/moz/jssubscript-loader;1"].
                        getService(Ci.mozIJSSubScriptLoader);
  subscriptLoader.loadSubScript("chrome://personas/content/XPCOMUtils.jsm");
  subscriptLoader.loadSubScript("chrome://personas/content/JSON.jsm");
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

  classDescription: "Persona Service",
  classID:          Components.ID("{efdd655c-51ac-4e5c-aa61-888b270436b8}"),
  contractID:       "@mozilla.org/personas/persona-service;1",
  QueryInterface:   XPCOMUtils.generateQI([Ci.nsIPersonaService,
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

  /**
   * Get the value of a pref, if any; otherwise, get the default value.
   *
   * @param   prefName
   * @param   defaultValue
   * @returns the value of the pref, if any; otherwise, the default value
   */
  _getPref: function(prefName, defaultValue) {
    let prefSvc = this._prefSvc;

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

  get _hiddenWindow() {
    let hiddenWindow = Cc["@mozilla.org/appshell/appShellService;1"].
                       getService(Ci.nsIAppShellService).
                       hiddenDOMWindow;
    this.__defineGetter__("_hiddenWindow", function() { return hiddenWindow });
    return this._hiddenWindow;
  },

  // The interval between consecutive persona reloads.  Measured in minutes,
  // with a default of 30 minutes and a minimum of one minute.
  get _reloadInterval() {
    let val = this._getPref("extensions.personas.reloadInterval");
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

  get _toolbarIframe() {
    return this._personaLoader.contentDocument.getElementById("toolbarIframe");
  },

  get _toolbarCanvas() {
    return this._personaLoader.contentDocument.getElementById("toolbarCanvas");
  },

  get _statusbarIframe() {
    return this._personaLoader.contentDocument.getElementById("statusbarIframe");
  },

  get _statusbarCanvas() {
    return this._personaLoader.contentDocument.getElementById("statusbarCanvas");
  },


  //**************************************************************************//
  // Internal Properties

  // The iframe that we add to the hidden window and into which we load a XUL
  // document that helps us to load the toolbar and statusbar backgrounds.
  // Defined when the persona service is initialized.
  _personaLoader: null,

  // Objects that are responsible for loading the toolbar and statusbar
  // backgrounds and providing them to application windows. Defined when
  // the persona loader is loaded.
  _toolbarLoader: null,
  _statusbarLoader: null,

  // A timer that periodically reloads the lists of categories and personas
  // to incorporate updates to those lists.
  _dataReloadTimer: null,

  // A timer that periodically reloads the selected persona to incorporate
  // server-side changes to static and dynamic personas.  Defined when the
  // persona loader is loaded.
  _personaReloadTimer: null,


  //**************************************************************************//
  // XPCOM Interface Implementations

  // nsIPersonaService

  // The lists of categories and personas retrieved from the server via JSON,
  // as nsISupports objects whose wrappedJSObject property contains the data.
  // Loaded upon service initialization and reloaded periodically thereafter.
  categories: null,
  personas: null,

  // nsIObserver

  observe: function(subject, topic, data) {
    switch (topic) {
      case "xpcom-shutdown":
        this._destroy();
        break;

      case "nsPref:changed":
        switch (data) {
          // If any of the prefs that determine which persona is selected
          // have changed, then reload the persona.
          // FIXME: figure out how to call _displayPersona only once when both
          // "selected" and "category" preferences are set one after the other
          // by PersonaController._setPersona.
          case "extensions.personas.selected":
          case "extensions.personas.manualPath":
          case "extensions.personas.category":
            this._displayPersona();
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
      case this._personaReloadTimer:
        this._reloadPersona();
        break;
      case this._dataReloadTimer:
        this._reloadData();
        break;
    }
  },


  //**************************************************************************//
  // Initialization & Destruction

  _init: function() {
    // Observe application shutdown so we can destroy ourself.
    this._obsSvc.addObserver(this, "xpcom-shutdown", false);

    // Observe profile-before-change so we can switch to the datasource
    // in the new profile when the user changes profiles.
    // FIXME: figure out whether or not we should be doing this.
    // this._obsSvc.addObserver(this, "profile-before-change", false);

    // Observe changes to the selected persona that happen in other windows
    // or by users twiddling the preferences directly.
    this._prefSvc.addObserver("extensions.personas.", this, false);

    // Create the persona loader and attach it to the hidden window.
    this._personaLoader = this._hiddenWindow.document.createElement("iframe");
    this._personaLoader.setAttribute("id", "personaLoader");
    this._personaLoader.setAttribute("src", "chrome://personas/content/personaLoader.xul");
    this._personaLoader.addEventListener("pageshow", this, false);
    this._hiddenWindow.document.documentElement.appendChild(this._personaLoader);

    // Load the lists of categories and personas, and define a timer
    // that periodically reloads them.
    this._reloadData();
    this._dataReloadTimer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);
    this._dataReloadTimer.initWithCallback(this,
                                           30 * 60 * 1000, // 30 minutes
                                           Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  _destroy: function() {
    if (this._toolbarLoader)
      this._toolbarLoader.reset();
    this._toolbarLoader = null;

    if (this._statusbarLoader)
      this._statusbarLoader.reset();
    this._statusbarLoader = null;

    if (this._personaReloadTimer)
      this._personaReloadTimer.cancel();
    this._personaReloadTimer = null;

    this._dataReloadTimer.cancel();
    this._dataReloadTimer = null;

    this._personaLoader = null;

    this._prefSvc.removeObserver("extensions.personas.", this);
    this._obsSvc.removeObserver(this, "xpcom-shutdown");
  },


  //**************************************************************************//
  // Data Loading

  _reloadData: function() {
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
  },


  //**************************************************************************//
  // Persona Loading

  onPersonaLoaderLoad: function() {
    // Define the reload and snapshot timers.  We only do this once per browser
    // session, after which we reuse the same timer for performance, canceling
    // and reinitializing it as needed.
    this._personaReloadTimer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);

    // Initialize the toolbar and statusbar background loaders.
    this._toolbarLoader = new BackgroundLoader(this,
                                               this._toolbarIframe,
                                               this._toolbarCanvas,
                                               "personas:toolbarURLUpdated");

    this._statusbarLoader = new BackgroundLoader(this,
                                                 this._statusbarIframe,
                                                 this._statusbarCanvas,
                                                 "personas:statusbarURLUpdated");

    // Now apply the selected persona to the browser windows.
    this._displayPersona();
  },

  /**
   * Display the selected persona in the application windows.  This happens
   * on startup and every time the user selects a persona.
   */
  _displayPersona: function() {
    // Cancel the reload timer.
    this._personaReloadTimer.cancel();

    let personaID = this._getPref("extensions.personas.selected", "default");

    if (personaID == "default") {
      // Reset the background loaders so they don't keep trying to snapshot
      // the previously-selected persona.
      this._toolbarLoader.reset();
      this._statusbarLoader.reset();

      // Notify the application windows to apply the default persona.
      this._obsSvc.notifyObservers(null, "personas:defaultPersonaSelected", null);

      return;
    }

    // Load the persona, then initialize a timer that reloads it periodically.
    this._reloadPersona();
    this._personaReloadTimer.initWithCallback(this,
                                              this._reloadInterval * 60 * 1000,
                                              Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  /**
   * Reload the currently selected persona.  This happens on startup, every
   * time the user switches personas, and periodically at the interval defined
   * by the _reloadInterval property (the latter to incorporate changes
   * to dynamic personas).
   */
  _reloadPersona: function() {
    let personaID = this._getPref("extensions.personas.selected", "default");

    if (personaID == "random")
      personaID = this._getRandomPersona();

    this._toolbarLoader.reload(personaID, this._getToolbarURL(personaID));
    this._statusbarLoader.reload(personaID, this._getStatusbarURL(personaID));
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

  _getToolbarURL: function(aPersonaID) {
    // Custom persona whose toolbar content is a local file specified by the user
    // and stored in the "manualPath" preference.
    // FIXME: store custom personas as full URLs, since that would enable users
    // to specify remote URLs as custom personas instead of being limited to
    // local files (of course then we'd also have to create some usable UI for
    // specifying that remote URL).
    if (aPersonaID == "manual")
      return "file://" + this._prefSvc.getCharPref("extensions.personas.manualPath");

    let persona = this._getPersona(aPersonaID);

    // Let the persona override the default base URL so it can reference
    // files on other servers.
    let baseURL = (typeof persona.baseURL != "undefined") ? persona.baseURL
                                                          : this._baseURL;

    // New-style persona whose content (which might be dynamic) is located
    // at the URL specified by a property.
    if (persona.toolbarURL)
      return baseURL + persona.toolbarURL;

    // Old-style persona whose content (which must be static) is a JPG image
    // located at a particular place on the personas server.
    return baseURL + "skins/" + aPersonaID + "/tbox-" + aPersonaID + ".jpg";
  },

  _getStatusbarURL: function(aPersonaID) {
    // Custom persona whose content is in local files specified by the user
    // in preferences.
    if (aPersonaID == "manual")
      return this._prefSvc.getCharPref("extensions.personas.custom.statusbarURL");

    let persona = this._getPersona(aPersonaID);

    // Let the persona override the default base URL so it can reference
    // files on other servers.
    let baseURL = (typeof persona.baseURL != "undefined") ? persona.baseURL
                                                          : this._baseURL;

    // New-style persona whose content (which might be dynamic) is located
    // at the URL specified by a property.
    if (persona.statusbarURL)
      return baseURL + persona.statusbarURL;

    // Old-style persona whose content (which must be static) is a JPG image
    // located at a particular place on the personas server.
    return baseURL + "skins/" + aPersonaID + "/stbar-" + aPersonaID + ".jpg";
  }

}


function BackgroundLoader(aPersonaService, aIframe, aCanvas, aNotificationTopic) {
  this._personaSvc = aPersonaService;
  this._iframe = aIframe;
  this._canvas = aCanvas;
  this._notificationTopic = aNotificationTopic;

  // A timer that periodically snapshots the loaded persona so it incorporates
  // client-side changes to dynamic personas.  Instantiated once the persona
  // loader is ready to load the current persona, and reinitialized each time
  // we load the persona.
  this._snapshotTimer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);
}

BackgroundLoader.prototype = {
  //**************************************************************************//
  // Convenience Getters

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

  /**
   * Get the value of a pref, if any; otherwise, get the default value.
   *
   * @param   prefName
   * @param   defaultValue
   * @returns the value of the pref, if any; otherwise, the default value
   */
  _getPref: function(prefName, defaultValue) {
    let prefSvc = this._prefSvc;

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

  // The interval between consecutive persona snapshots.  Measured in seconds,
  // with a default of 60 seconds and a minimum of one second.
  get _snapshotInterval() {
    let val = this._getPref("extensions.personas.snapshotInterval");
    return val < 1 ? 1 : val;
  },


  //**************************************************************************//
  // Internal Properties

  _personaSvc: null,
  _iframe: null,
  _canvas: null,
  _notificationTopic: null,


  //**************************************************************************//
  // XPCOM Interface Implementations

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
      case this._snapshotTimer:
        this._updateAppearance();
        break;
      case this._delayedUpdateTimer:
        try {
          this._updateAppearance();
        }
        finally {
          this._delayedUpdateTimer.cancel();
          this._delayedUpdateTimer = null;
        }
        break;
    }
  },


  reset: function() {
    this._snapshotTimer.cancel();
  },

  reload: function(aPersonaID, aURL) {
    this._snapshotTimer.cancel();

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
    if (/\.(jpg|jpeg|png|gif)$/.test(url)) {
      // If this is a custom persona using a local image file, then load it
      // using the chrome-privileged image loader, which we need to use in order
      // to be able to load the local file.
      if (aPersonaID == "manual" && /^file:/.test(url))
        url = "chrome://personas/content/imageLoader.xul?" + url;

      // Otherwise, load it using an unprivileged image loader constructed
      // inside a data: URL so it can't do anything malicious.  This protects
      // us against issues like personas providing javascript: URLs that could
      // take advantage of a chrome-privileged loader to access local files.
      // FIXME: use a template to simplify this code?
      else
        url = 'data:application/vnd.mozilla.xul+xml,' +
              '<window xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul" ' +
                      'style="background-image: url(' + escapeXML(escapeCSSURL(url)) + '); ' +
                             'background-repeat: no-repeat; ' +
                             'background-position: top right;" flex="1"/>';
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

  _onLoad: function() {
    // Delay updating the loader momentarily to give the rendering engine
    // time to finish displaying the content in the iframe before we take
    // a snapshot of it and turn that into a URL.
    // Note: I'm not sure why this is necessary, since the content should be
    // rendered by the time we get notified about the pageshow event, so we
    // should be able to just call _updateFoo directly, but for some reason
    // that doesn't work (our snapshot turns out blank).
    //this._update();
    this._delayedUpdateTimer = Cc["@mozilla.org/timer;1"].
                               createInstance(Ci.nsITimer);
    this._delayedUpdateTimer.initWithCallback(this,
                                              0,
                                              Ci.nsITimer.TYPE_ONE_SHOT);

    // Restart the snapshot timer.
    this._snapshotTimer.initWithCallback(this,
                                         this._snapshotInterval * 1000,
                                         Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  _updateAppearance: function() {
    // Take a snapshot of the iframe by drawing its contents onto the canvas,
    // then convert the snapshot into a data: URL containing an image and notify
    // application windows of the URL so they update their appearance to reflect
    // the new version of the background image.
    //
    // Note: we set the starting point for the snapshot to the top/left corner
    // of the visible portion of the page so that we show what the URL intends
    // to show when it contains an anchor (#something) that scrolls the page
    // to a particular point on the page while it's being loaded.
    //
    // Note: We specify an alpha channel in the background color to preserve
    // transparency in images.
    //
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

    this._personaSvc._obsSvc.notifyObservers(null, this._notificationTopic, url);
  }

};


//****************************************************************************//
// More XPCOM Plumbing

function NSGetModule(compMgr, fileSpec) {
  return XPCOMUtils.generateModule([PersonaService]);
}
