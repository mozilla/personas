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

// FIXME: use these where appropriate or remove them.
const DEFAULT_HEADER = new URI("chrome://personas/content/header-default.jpg");
const DEFAULT_FOOTER = new URI("chrome://personas/content/footer-default.jpg");

let PersonaService = {
  //**************************************************************************//
  // Shortcuts

  // Access to extensions.personas.* preferences.  To access other preferences,
  // call the Preferences module directly.
  get _prefs() {
    delete this._prefs;
    return this._prefs = new Preferences("extensions.personas.");
  },


  //**************************************************************************//
  // Initialization & Destruction

  _init: function() {
    // Observe quit so we can destroy ourselves.
    Observers.add("quit-application", this.onQuitApplication, this);

    this._prefs.observe("useTextColor",   this.onUseColorChanged, this);
    this._prefs.observe("useAccentColor", this.onUseColorChanged, this);

    let timerManager = Cc["@mozilla.org/updates/timer-manager;1"].
                       getService(Ci.nsIUpdateTimerManager);

    // Refresh data, then set a timer to refresh it periodically.
    // This isn't quite right, since we always load data on startup, even if
    // we've recently refreshed it.  And the timer that refreshes data ignores
    // the data load on startup, so if it's been more than the timer interval
    // since a user last started her browser, we load the data twice:
    // once because the browser starts and once because the refresh timer fires.
    // FIXME: save the data to disk when we retrieve it and then retrieve it
    // from there on startup instead of loading it over the network.
    this.refreshData();
    let dataRefreshCallback = {
      _svc: this,
      notify: function(timer) { this._svc._refreshDataWithMetrics() }
    };
    timerManager.registerTimer("personas-data-refresh-timer",
                               dataRefreshCallback,
                               86400 /* in seconds == one day */);

    // Refresh the current persona once per day.
    let personaRefreshCallback = {
      _svc: this,
      notify: function(timer) { this._svc._refreshPersona() }
    };
    timerManager.registerTimer("personas-persona-refresh-timer",
                               personaRefreshCallback,
                               86400 /* in seconds == one day */);

    // Initialize the persona loader.
    // XXX Commented out because dynamic personas have been disabled.
    //this._initPersonaLoader();
  },

  _destroy: function() {
    //this._destroyPersonaLoader();

    this._prefs.ignore("useTextColor",   this.onUseColorChanged, this);
    this._prefs.ignore("useAccentColor", this.onUseColorChanged, this);
  },


  //**************************************************************************//
  // XPCOM Plumbing

  QueryInterface: XPCOMUtils.generateQI([Ci.nsIObserver,
                                         Ci.nsIDOMEventListener,
                                         Ci.nsITimerCallback]),


  //**************************************************************************//
  // Data Retrieval

  _makeRequest: function(url, loadCallback, headers) {
    let request = Cc["@mozilla.org/xmlextras/xmlhttprequest;1"].createInstance();

    request = request.QueryInterface(Ci.nsIDOMEventTarget);
    request.addEventListener("load", loadCallback, false);

    request = request.QueryInterface(Ci.nsIXMLHttpRequest);
    request.open("GET", url, true);

    if (headers)
      for (let header in headers)
        request.setRequestHeader(header, headers[header]);

    request.send(null);
  },

  /**
   * Refresh data. This method gets called on demand (including on startup)
   * and retrieves data without passing any additional information about
   * the selected persona and the application (that information is only included
   * in the daily retrieval so we can get consistent daily statistics from it
   * no matter how many times a user starts the application in a given day).
   */
  refreshData: function() {
    let url = this.baseURI + "index_" + this._prefs.get("data.version") + ".json";
    let t = this;
    this._makeRequest(url, function(evt) { t.onDataLoadComplete(evt) });
  },

  /**
   * Refresh data, providing metrics on persona usage in the process.
   * This method gets called approximately once per day on a cross-session timer
   * (provided Firefox is run every day), updates the version of the data
   * that is currently in memory, and passes information about the selected
   * persona and the host application to the server for statistical analysis
   * (f.e. figuring out which personas are the most popular).
   */
  _refreshDataWithMetrics: function() {
    let appInfo     = Cc["@mozilla.org/xre/app-info;1"].
                      getService(Ci.nsIXULAppInfo);
    let xulRuntime  = Cc["@mozilla.org/xre/app-info;1"].
                      getService(Ci.nsIXULRuntime);

    // Calculate the amount of time (in hours) since the persona was last changed.
    let duration = "";
    if (this._prefs.has("persona.lastChanged"))
      duration = Math.round((new Date() - new Date(parseInt(this._prefs.get("persona.lastChanged")))) / 1000 / 60 / 60);

    // This logic is based on ExtensionManager::_updateLocale.
    let locale;
    try {
      if (Preferences.get("intl.locale.matchOS")) {
        let localeSvc = Cc["@mozilla.org/intl/nslocaleservice;1"].
                        getService(Ci.nsILocaleService);
        locale = localeSvc.getLocaleComponentForUserAgent();
      }
      else
        throw "set locale in the catch block";
    }
    catch (ex) {
      locale = Preferences.get("general.useragent.locale");
    }

    let params = {
      type:       this.selected,
      id:         this.currentPersona ? this.currentPersona.id : "",
      duration:   duration,
      appID:      appInfo.ID,
      appVersion: appInfo.version,
      appLocale:  locale,
      appOS:      xulRuntime.OS,
      appABI:     xulRuntime.XPCOMABI
    };

    //dump("params: " + [name + "=" + encodeURIComponent(params[name]) for (name in params)].join("&") + "\n");

    let url = this.baseURI + "index_" + this._prefs.get("data.version") + ".json?" +
              [name + "=" + encodeURIComponent(params[name]) for (name in params)].join("&");
    let t = this;
    this._makeRequest(url, function(evt) { t.onDataLoadComplete(evt) });
  },

  onDataLoadComplete: function(aEvent) {
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
    if (this.selected == "random") {
      this.currentPersona = this._getRandomPersona(this.category);
      this._prefs.reset("persona.lastRefreshed");
      Observers.notify("personas:persona:changed");
    }
  },

  _refreshPersona: function() {
    // Only refresh the persona if the user selected a specific persona.
    // If the user selected a random persona, we'll change it the next time
    // we refresh the directory, and if the user selected the default persona,
    // we don't need to refresh it, as it doesn't change.
    if (this.selected != "current" || !this.currentPersona)
      return;

    let lastTwoDigits = new String(this.currentPersona.id).substr(-2).split("");
    if (lastTwoDigits.length == 1)
      lastTwoDigits.unshift("0");

    let url = this.baseURI + lastTwoDigits.join("/") + "/" +
              this.currentPersona.id + "/" +
              "index_" + this._prefs.get("data.version") + ".json";

    let headers = {};

    if (this._prefs.has("persona.lastRefreshed")) {
      let date = new Date(parseInt(this._prefs.get("persona.lastRefreshed")));
      headers["If-Modified-Since"] = DateUtils.toRFC1123(date);
    }

    let t = this;
    this._makeRequest(url, function(evt) { t.onPersonaLoadComplete(evt) }, headers);
  },

  onPersonaLoadComplete: function(event) {
    let request = event.target;

    // 304 means the file we requested has not been modified since the
    // If-Modified-Since date we specified, so there's nothing to do.
    if (request.status == 304) {
      //dump("304 - the persona has not been modified\n");
      return;
    }

    // 404 means the persona wasn't found, which means we need to unselect it.
    // FIXME: be kinder to the user and inform them about what we're doing
    // and why.
    if (request.status == 404) {
      //dump("persona " + persona.id + "(" + persona.name + ") no longer exists; unselecting\n");
      this.changeToDefaultPersona();
      return;
    }

    if (request.status != 200)
      throw("problem refreshing persona: " + request.status + " - " + request.statusText);

    let persona = JSON.parse(request.responseText);

    // If the persona we're refreshing is no longer the selected persona,
    // then cancel the refresh (otherwise we'd undo whatever changes the user
    // has just made).
    if (this.selected != "current" || !this.currentPersona ||
        this.currentPersona.id != persona.id) {
      //dump("persona " + persona.id + "(" + persona.name + ") no longer the current persona; ignoring refresh\n");
      return;
    }

    // Set the current persona to the updated version we got from the server,
    // and notify observers about the change.
    this.currentPersona = persona;
    Observers.notify("personas:persona:changed");

    // Record when this refresh took place so the next refresh only looks
    // for changes since this refresh.
    // Note: we set the preference to a string value because preferences
    // can't hold large enough integer values.
    this._prefs.set("persona.lastRefreshed", new Date().getTime().toString());
  },


  //**************************************************************************//
  // Implementation

  // The JSON feed of personas retrieved from the server.
  // Loaded upon service initialization and reloaded periodically thereafter.
  personas: null,

  /**
   * extensions.personas.selected: the type of persona that the user selected;
   * possible values are default (the default Firefox theme), random (a random
   * persona from a category), and current (the value of this.currentPersona).
   */
  get selected()        { return this._prefs.get("selected") },
  set selected(newVal)  {        this._prefs.set("selected", newVal) },

  /**
   * extensions.personas.current: the current persona
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
   * extensions.personas.category: the category from which to pick a random
   * persona.
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
   * extensions.personas.custom: the custom persona.
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

  changeToDefaultPersona: function() {
    this.selected = "default";
    this._prefs.set("persona.lastChanged", new Date().getTime().toString());
    Observers.notify("personas:persona:changed");
  },

  changeToRandomPersona: function(category) {
    this.category = category;
    this.currentPersona = this._getRandomPersona(category);
    this.selected = "random";
    this._prefs.set("persona.lastChanged", new Date().getTime().toString());
    Observers.notify("personas:persona:changed");
  },

  changeToPersona: function(persona) {
    this.currentPersona = persona;
    this._addPersonaToRecent(persona);
    this.selected = "current";
    this._prefs.reset("persona.lastRefreshed");
    this._prefs.set("persona.lastChanged", new Date().getTime().toString());
    Observers.notify("personas:persona:changed");
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
          if (!this.currentPersona || randomItem.id != this.currentPersona.id)
            break;
        }

        persona = randomItem;
      }
    }

    return persona || this.currentPersona;
  },

  _addPersonaToRecent: function(persona) {
    // Parse the list of recent personas.
    let personas = [];
    for (let i = 0; i < 4; i++) {
      if (this._prefs.has("lastselected" + i)) {
        try {
          personas.push(JSON.parse(this._prefs.get("lastselected" + i)));
        }
        catch(ex) {}
      }
    }

    // Remove personas with the same ID (i.e. don't allow the recent persona
    // to appear twice on the list).  Afterwards, we'll add the recent persona
    // to the list in a way that makes it the most recent one.
    if (persona.id)
      personas = personas.filter(function(v) !v.id || v.id != persona.id);

    // Make the new persona the most recent one.
    personas.unshift(persona);

    // Note: at this point, there might be five personas on the list, four
    // that we parsed from preferences and the one we're now adding. But we
    // only serialize the first four back to preferences, so the oldest one
    // drops off the end of the list.

    // Serialize the list of recent personas.
    for (let i = 0; i < 4; i++) {
      if (i < personas.length)
        this._prefs.set("lastselected" + i, JSON.stringify(personas[i]));
      else
        this._prefs.reset("lastselected" + i);
    }
  },

  onUseColorChanged: function() {
    // Notify observers that the persona has changed so the change in whether
    // or not to use the text or accent color will get applied.  The persona
    // hasn't really changed, but doing this has the desired effect without any
    // known unwanted side effects.
    Observers.notify("personas:persona:changed");
  },

  previewingPersona: null,

  /**
   * Display the given persona temporarily.  Useful for showing users who are
   * browsing the directory of personas what a given persona will look like
   * when selected, f.e. on mouseover.  Consumers who call this method should
   * call resetPersona when the preview ends, f.e. on mouseout.
   */
  previewPersona: function(persona) {
    this.previewingPersona = persona;
    Observers.notify("personas:persona:changed");
  },

  /**
   * Stop previewing a persona.
   */
  resetPersona: function() {
    this.previewingPersona = null;
    Observers.notify("personas:persona:changed");
  },

  onQuitApplication: function() {
    Observers.remove("quit-application", this.onQuitApplication, this);
    this._destroy();
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
    this.refreshData();
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
        this.refreshData();
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
      Observers.notify("personas:personaLoadFinished", null, this._activePersona);
    }
    this._activePersona = null;

    if (aPersonaID == "default") {
      Observers.notify("personas:defaultPersonaSelected");
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
      Observers.notify("personas:personaLoadStarted", null, aPersonaID);
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
    Observers.notify("personas:personaLoadFinished", null, this._activePersona);

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
   Observers.notify("personas:activePersonaUpdated");
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
    Observers.notify("personas:activePersonaUpdated");
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
    Observers.notify("personas:activePersonaUpdated");
  }

};


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

let DateUtils = {
  /**
   * Returns the number as a string with a 0 prepended to it if it contains
   * only one digit, for formats like ISO 8601 that require two digit month,
   * day, hour, minute, and second values (f.e. so midnight on January 1, 2009
   * becomes 2009:01:01T00:00:00Z instead of 2009:1:1T0:0:0Z, which would be
   * invalid).
   */
  _pad: function(number) {
    return (number >= 0 && number <= 9) ? "0" + number : "" + number;
  },

  /**
   * Format a date per ISO 8601, in particular the subset described in
   * http://www.w3.org/TR/NOTE-datetime, which is recommended for date
   * interchange on the internet.
   *
   * Example: 1994-11-06T08:49:37Z
   *
   * @param   date  {Date}    the date to format
   * @returns       {String}  the date formatted per ISO 8601
   */
  toISO8601: function(date) {
    let year = date.getUTCFullYear();
    let month = this._pad(date.getUTCMonth() + 1);
    let day = this._pad(date.getUTCDate());
    let hours = this._pad(date.getUTCHours());
    let minutes = this._pad(date.getUTCMinutes());
    let seconds = this._pad(date.getUTCSeconds());
    return year + "-" + month + "-" + day + "T" +
           hours + ":" + minutes + ":" + seconds + "Z";
  },

  /**
   * Format a date per RFC 1123, which is the standard for HTTP headers.
   *
   * Example: Sun, 06 Nov 1994 08:49:37 GMT
   *
   * I'd love to use Datejs here, but its Date::toString formatting method
   * doesn't convert dates to their UTC equivalents before formatting them,
   * resulting in incorrect output (since RFC 1123 requires dates to be
   * in UTC), so instead I roll my own.
   *
   * @param   date  {Date}    the date to format
   * @returns       {String}  the date formatted per RFC 1123
   */
  toRFC1123: function(date) {
    let dayOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"][date.getUTCDay()];
    let day = this._pad(date.getUTCDate());
    let month = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"][date.getUTCMonth()];
    let year = date.getUTCFullYear();
    let hours = this._pad(date.getUTCHours());
    let minutes = this._pad(date.getUTCMinutes());
    let seconds = this._pad(date.getUTCSeconds());
    return dayOfWeek + ", " + day + " " + month + " " + year + " " +
           hours + ":" + minutes + ":" + seconds + " GMT";
  }
};

PersonaService._init();
