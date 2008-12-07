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

let EXPORTED_SYMBOLS = ["PersonaService", "PERSONAS_EXTENSION_ID"];

const Cc = Components.classes;
const Ci = Components.interfaces;
const Cr = Components.results;
const Cu = Components.utils;

// modules that come with Firefox
Cu.import("resource://gre/modules/XPCOMUtils.jsm");

// modules that are generic
Cu.import("resource://personas/modules/JSON.js");
Cu.import("resource://personas/modules/Observers.js");
Cu.import("resource://personas/modules/Preferences.js");
Cu.import("resource://personas/modules/URI.js");

const PERSONAS_EXTENSION_ID = "personas@christopher.beard";

let PersonaService = {
  //**************************************************************************//
  // Initialization & Destruction

  _init: function() {
    // Observe quit so we can destroy ourselves.
    Observers.add(this, "quit-application");

    // Observe changes to personas preferences.
    this._observePrefChanges = true;

    this._loadData();

    // Initialize the persona loader.
    // XXX Commented out because dynamic personas have been disabled.
    //this._initPersonaLoader();
  },

  _destroy: function() {
    //this._destroyPersonaLoader();
    this._observePrefChanges = false;
    Observers.remove(this, "quit-application");
  },


  //**************************************************************************//
  // XPCOM Plumbing

  QueryInterface: XPCOMUtils.generateQI([Ci.nsIObserver,
                                         Ci.nsIDOMEventListener,
                                         Ci.nsITimerCallback]),


  //**************************************************************************//
  // Shortcuts

  // Access to extensions.personas.* preferences.  To access other preferences,
  // call the Preferences module directly.
  get _prefs() {
    delete this._prefs;
    return this._prefs = new Preferences("extensions.personas.");
  },


  //**************************************************************************//
  // Data Retrieval

  _makeRequest: function(aURL, aLoadCallback) {
    let request = Cc["@mozilla.org/xmlextras/xmlhttprequest;1"].createInstance();

    request = request.QueryInterface(Ci.nsIDOMEventTarget);
    request.addEventListener("load", aLoadCallback, false);

    request = request.QueryInterface(Ci.nsIXMLHttpRequest);
    request.open("GET", aURL, true);
    request.send(null);
  },

  _loadData: function() {
dump("_loadData\n");
    let t = this;
    this._makeRequest(this.baseURI + "index.json",
                      function(evt) { t.onDataLoadComplete(evt) });
  },

  onDataLoadComplete: function(aEvent) {
dump("onDataLoadComplete\n");
    let request = aEvent.target;

    // XXX Try to reload again sooner?
    if (request.status != 200)
      throw("problem loading data: " + request.status + " - " + request.statusText);

    this.personas = JSON.parse(request.responseText);

    // Now that we have data, pick a new random persona.  Currently, this is
    // the only time we pick a random persona besides when the user selects
    // the "Random From [category]" menuitem, which means the user gets a new
    // random persona each time they start the browser.
    // FIXME: depending on how long it takes data to load, this might cause
    // the old randomly selected persona to appear briefly before the new one
    // gets selected, which is ugly, so delay displaying the old random persona
    // until we reach this point; or cache the feed across sessions, so we have
    // at least an older version of the data available to us the moment
    // the browser starts, and pick a new random persona from the cached data.
    if (this.selected == "random")
      this.changeToRandomPersona(this.category);
  },


  //**************************************************************************//
  // Implementation

  // The JSON feed of personas retrieved from the server.
  // Loaded upon service initialization and reloaded periodically thereafter.
  personas: null,

  /**
   * extensions.personas.selected
   */
  get selected()        { return this._prefs.get("selected") },
  set selected(newVal)  {        this._prefs.set("selected", newVal) },

  /**
   * extensions.personas.current
   */
  get currentPersona() {
    let current = this._prefs.get("current");
    if (current) {
      try       { return JSON.parse(current) }
      catch(ex) { Cu.reportError("error getting current persona: " + ex) }
    }
    return null;
  },
  set currentPersona(newVal) {
    try       { this._prefs.set("current", JSON.stringify(newVal)) }
    catch(ex) { Cu.reportError("error setting current persona: " + ex) }
  },

  /**
   * extensions.personas.category
   */
  get category()        { return this._prefs.get("category") },
  set category(newVal)  {        this._prefs.set("category", newVal) },

  /**
   * extensions.personas.url
   */
  get baseURI() {
    return this._prefs.get("url");
  },

  /**
   * extensions.personas.custom
   */
  get customPersona() {
    let custom = this._prefs.get("custom");
    if (custom) {
      try       { return JSON.parse(custom) }
      catch(ex) { Cu.reportError("error getting custom persona: " + ex) }
    }
    return null;
  },
  set customPersona(newVal) {
    try       { this._prefs.set("custom", JSON.stringify(newVal)) }
    catch(ex) { Cu.reportError("error setting custom persona: " + ex) }
  },

  /**
   * The active persona.  Normally this is the same as the current persona,
   * but it is the persona being previewed while the user is previewing one.
   */
  get activePersona() {
    return this._previewingPersona || this.currentPersona;
  },

  changeToDefaultPersona: function() {
    this._observePrefChanges = false;
    try {
      this.selected = "default";
    }
    finally {
      this._observePrefChanges = true;
    }

    this._onChangeToDefaultPersona();
  },

  changeToRandomPersona: function(category) {
    this._observePrefChanges = false;
    try {
      this.category = category;
      let persona = this._getRandomPersona(this.category);
      if (persona)
        this.currentPersona = persona;
      this.selected = "random";
    }
    finally {
      this._observePrefChanges = true;
    }

    this._onChangeToPersona();
  },

  changeToPersona: function(persona) {
    this._observePrefChanges = false;
    try {
      // Update the list of recent personas.
      if (this.currentPersona && this.currentPersona.name &&
          persona.id != this.currentPersona.id) {
        this._prefs.set("lastselected3", this._prefs.get("lastselected2"));
        this._prefs.set("lastselected2", this._prefs.get("lastselected1"));
        this._prefs.set("lastselected1", this._prefs.get("lastselected0"));
        this._prefs.set("lastselected0", JSON.stringify(this.currentPersona));
      }
  
      this.currentPersona = persona;
      this.selected = "current";
    }
    finally {
      this._observePrefChanges = true;
    }

    this.reportSelection(persona);
    this._onChangeToPersona();
  },

  _onPersonaChanged: function() {
dump("this.selected" + this.selected + "\n");
    switch (this.selected) {
      case "default":
        this._onChangeToDefaultPersona();
        break;
      case "random":
      case "current":
      default:
        this._onChangeToPersona();
        break;
    }
  },

  _onChangeToDefaultPersona: function() {
    Observers.notify(null, "personas:persona:disabled", null);
  },

  _onChangeToPersona: function() {
    Observers.notify(null, "personas:persona:changed", null);
  },

  _previewingPersona: null,

  /**
   * Display the given persona temporarily.  Useful for showing users who are
   * browsing the directory of personas what a given persona will look like
   * when selected, f.e. on mouseover.  Consumers who call this method should
   * call resetPersona when the preview ends, f.e. on mouseout.
   */
  previewPersona: function(persona) {
dump("previewPersona: " + JSON.stringify(persona) + "\n");
    this._previewingPersona = persona;
    Observers.notify(null, "personas:persona:changed", null);
  },

  /**
   * Stop previewing a persona.
   */
  resetPersona: function() {
dump("resetPersona\n");
    this._previewingPersona = null;
    Observers.notify(null, "personas:persona:changed", null);
  },

  /**
   * Report which persona was selected.  This only gets called when the user
   * selects a persona from the menu or the web directory, and it doesn't report
   * about custom personas.  It can be disabled by setting the preference
   * extensions.personas.reportSelection to false.
   */
  reportSelection: function(persona) {
    if (persona.custom || !this._prefs.get("reportSelection"))
      return;
    this._makeRequest(this.baseURI + persona.id + "/report_selection/",
                      function(evt) {});
  },

  /**
   * Whether or not to ignore changes to personas preferences.
   * Sometimes changing the persona requires changing more than one pref
   * (or making a pref change that triggers another pref change), and we don't
   * want the service to act on the pref change until the last pref is changed
   * (nor to recursively act on the pref change, creating an endless loop).
   * In those situations, we make the service ignore pref changes temporarily
   * by setting this to false.
   */
  set _observePrefChanges(newVal) {
    if (newVal)
      this._prefs.addObserver(this);
    else
      this._prefs.removeObserver(this);
  },

  // nsIObserver

  observe: function(subject, topic, data) {
    switch (topic) {
      case "quit-application":
        Observers.remove(this, "quit-application");
        this._destroy();
        break;

      case "nsPref:changed":
        switch (data) {
          case "extensions.personas.persona":
          case "extensions.personas.selected":
            this._onPersonaChanged();
            break;

          // If the user has enabled/disabled the text or accent color,
          // pretend the selected persona has changed so observers reapply
          // the current persona, updating the use of text and accent colors
          // in the process.
          case "extensions.personas.useTextColor":
	  case "extensions.personas.useAccentColor":
            this._onPersonaChanged();
            break;
        }
        break;
    }
  },



  //**************************************************************************//
  // Dynamic Personas

  // The rest of this object definition contains the old dynamic personas
  // implementation.  We aren't current using any of it, and it has become
  // out-of-date, so it would need to be updated in order for us to use it
  // again.  However, I am leaving it here for now in case we decide to use it
  // in the future.  If we decide that we aren't going to use it again, though,
  // then we should remove it from this file.  We can always recover it later
  // from version control if we change our minds.

  _initPersonaLoader: function() {
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
    // Disabled because we currently don't support dynamic personas,
    // and we load static personas via a much simpler technique.
    //this._loadPersonaLoader();

    // Load the lists of categories and personas, and define a timer
    // that periodically reloads them.
    this._loadData();
    this._reloadDataTimer = Cc["@mozilla.org/timer;1"].createInstance(Ci.nsITimer);
    this._reloadDataTimer.initWithCallback(this,
                                           30 * 60 * 1000, // 30 minutes
                                           Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  _destroyPersonaLoader: function() {
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
        let personaID = this._prefs.get("selected", "default");
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
  // Shortcuts

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
    let val = this._prefs.get("reloadInterval");
    return val < 1 ? 1 : val;
  },

  // The interval between consecutive persona snapshots.  Measured in seconds,
  // with a default of 60 seconds (defined in defaults/preferences/personas.js)
  // and a minimum of one second.
  get _snapshotInterval() {
    let val = this._prefs.get("snapshotInterval");
    return val < 1 ? 1 : val;
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

  _onLoaderAndPersonasLoaded: function() {
    // FIXME: store all relevant information about the persona in preferences
    // so we don't need the personas data to load the persona.

    // Now that the persona loader and the data is loaded, we load the persona.
    this._switchToPersona(this._currentPersona);
  },


  //**************************************************************************//
  // Persona Loading

  /**
   * Switch to the specified persona.  This happens on startup, when the user
   * selects a persona, and when the user previews a persona or resets to the
   * selected persona.
   * Note: this is overridden by the version of this method below it.
   * XXX This implementation hasn't been updated to work with persona objects.
   */
  _switchToPersona: function(aPersonaID) {
    this._reloadPersonaTimer.cancel();
    this._snapshotPersonaTimer.cancel();
    this._headerLoader.reset();
    this._footerLoader.reset();
    this.textColor = null;
    this.accentColor = null;
    this.type = null;

    if (this._loadState == LOAD_STATE_LOADING) {
      // FIXME: cancel the requests currently in process in the header
      // and footer iframes.
      Observers.notify(null,
                       "personas:personaLoadFinished",
                       this._activePersona);
    }
    this._activePersona = null;

    if (aPersonaID == "default") {
      Observers.notify(null, "personas:defaultPersonaSelected", null);
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

    // If the persona we selected is no longer available, set back to default.
    let persona = this._getPersona(aPersonaID);
    if (!aPersonaID)
      aPersonaID = "default";

    // If we're loading the "random" persona, pick a persona at random
    // from the selected category.
    if (aPersonaID == "random")
      aPersonaID = this._getRandomPersona();

    this._activePersona = aPersonaID;

    if(aPersonaID != "default") {
      Observers.notify(null, "personas:personaLoadStarted", aPersonaID);
      this._loadState = LOAD_STATE_LOADING;
      this._headerLoader.load(aPersonaID, this._getHeaderURL(aPersonaID));
      this._footerLoader.load(aPersonaID, this._getFooterURL(aPersonaID));
    }

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
    Observers.notify(null,
                     "personas:personaLoadFinished",
                     this._activePersona);

    this._snapshotPersona();

    // Start the snapshot timer.
    this._snapshotPersonaTimer.initWithCallback(this,
                                                this._snapshotInterval * 1000,
                                                Ci.nsITimer.TYPE_REPEATING_SLACK);
  },

  _snapshotPersona: function() {   
   let personaType = this._getPersonaType(this._activePersona);
   //if (personaType == "dynamic") {
      this.headerURL = this._headerLoader.getSnapshotURL();
      this.footerURL = this._footerLoader.getSnapshotURL();
   //} else {
      //this.headerURL = this._getHeaderURL(this._activePersona);
      //this.footerURL = this._getFooterURL(this._activePersona);
   //}
   this.textColor = this._getTextColor(this._activePersona);
   this.accentColor = this._getAccentColor(this._activePersona);
   Observers.notify(null, "personas:activePersonaUpdated", null);
  },

  _getRandomPersona: function(categoryName) {
    let persona;

    // If we have the list of categories, use it to pick a random persona
    // from the selected category.
    if (this.personas && this.personas.categories) {
      let personas;
      for each (let category in this.personas.categories) {
        if (categoryName == category.name) {
          personas = category.personas;
          break;
        }
      }

      // Get a random item from the list, trying up to five times to get one
      // that is different from the currently-selected item in the category
      // (if any).  We use Math.floor instead of Math.round to pick a random
      // number because the JS reference says Math.round returns a non-uniform
      // distribution
      // <http://developer.mozilla.org/en/docs/Core_JavaScript_1.5_Reference:Global_Objects:Math:random#Examples>.
      if (personas && personas.length > 0) {
        let randomIndex, randomItem;
        for (let i = 0; i < 5; i++) {
          randomIndex = Math.floor(Math.random() * personas.length);
          randomItem = personas[randomIndex];
          if (randomItem.id != this.selectedPersona.id)
            break;
        }

        persona = randomItem;
      }
    }

    return persona;
  },

  // FIXME: index personas after retrieving them and make the index (or a method
  // for accessing it) available to chrome JS in addition to this service's code
  // so we don't have to iterate through personas all the time.
  _getPersona: function(aPersonaID) {
    for each (let persona in this.selectedPersonas.wrappedJSObject)
      if (persona.id == aPersonaID)
        return persona;

    return null;
  },

  _getHeaderURL: function(persona) {
    // Custom persona whose header and footer are in local files specified by
    // the user in preferences.
    if (persona.id == "custom") {
      return this._prefs.get("custom.headerURL", "chrome://personas/content/header-default.jpg");
    }

    return this.baseURI + persona.header;
  },

  _getFooterURL: function(persona) {
    // Custom persona whose header and footer are in local files specified by
    // the user in preferences.
    if (persona.id == "manual") {
      return this._prefs.get("custom.headerURL", "chrome://personas/content/footer-default.jpg");
    }

    return this.baseURI + persona.footer;
  },

  _getPersonaType: function(aPersonaID) {
    if (aPersonaID == "manual")
      return "manual";

    let persona = this._getPersona(aPersonaID);
    if (persona) {
      if (persona.type)
        return persona.type;
    }

    return "unknown";
  },

  _getTextColor: function(persona) {
    // Custom persona whose text color is specified by the user in a preference.
    if (persona == "manual" &&
        !this._prefs.get("custom.useDefaultTextColor"))
      return this._prefs.get("custom.textColor");

    // Persona whose JSON record specifies a text color or a "dark" property.
    if (persona.textColor)
      return persona.textColor;

    // Dynamic HTML/XML persona whose root element has a computed color.
    // XXX Should we only use a color dynamically set via JS  (i.e. the value
    // of docElement.style.color)?
    // Note: disabled because we don't support dynamic personas anymore.
    //let headerDoc = this._headerLoader._iframe.contentDocument;
    //if (headerDoc) {
    //  let docElement = headerDoc.documentElement;
    //  if (docElement) {
    //    let style = headerDoc.defaultView.getComputedStyle(docElement, null);
    //    let color = style.getPropertyValue("color");
    //    if (color)
    //      return color;
    //  }
    //}

    // The default text color: black.
    return "#000000";
  },

  _onChangeCustomTextColor: function() {
    if (this._activePersona != "manual")
      return;

    this.textColor = this._getTextColor(this._activePersona);
    Observers.notify(null, "personas:activePersonaUpdated", null);
  },

  _getAccentColor: function(persona) {
    // Custom persona whose accent color is specified by the user in a preference.
    if (persona.id == "manual" &&
        !this._prefs.get("custom.useDefaultAccentColor"))
      return this._prefs.get("custom.accentColor");

    if (persona.accentColor) 
      return persona.accentColor;

    // The default accent color: gray.
    return "#C9C9C9";
  },

  _onChangeCustomAccentColor: function() {
    if (this._activePersona != "manual")
      return;

    this.accentColor = this._getAccentColor(this._activePersona);
    Observers.notify(null, "personas:activePersonaUpdated", null);
  }

};

PersonaService._init();



const LOAD_STATE_EMPTY = 0;
const LOAD_STATE_LOADING = 1;
const LOAD_STATE_LOADED = 2;

//****************************************************************************//
// Helper Utilities for the Persona Loader

// Escape CSS special characters in unquoted URLs
// per http://www.w3.org/TR/CSS21/syndata.html#uri.
function escapeURLForCSS(url) {
  return url.replace(/[(),\s'"]/g, "\$&");
}

// Escape XML special characters.
function escapeXML(aString) {
  aString = aString.replace(/\&/g, "&amp;");
  aString = aString.replace(/</g, "&lt;");
  aString = aString.replace(/>/g, "&gt;");
  return aString;
}


//****************************************************************************//
// Background, Header, and Footer Loaders

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
              '        onload="document.documentElement.style.backgroundImage = \'url(' + escapeXML(escapeURLForCSS(url)) + ')\'"\n' +
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
