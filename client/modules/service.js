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
  THUNDERBIRD_ID: "{3550f703-e582-4d05-9a08-453d09bdfdc6}",
  FIREFOX_ID:     "{ec8030f7-c20a-464f-9b0e-13a3a9e97384}",

  //**************************************************************************//
  // Shortcuts

  // Access to extensions.personas.* preferences.  To access other preferences,
  // call the Preferences module directly.
  get _prefs() {
    delete this._prefs;
    return this._prefs = new Preferences("extensions.personas.");
  },

  get appInfo() {
    delete this.appInfo;
    return this.appInfo = Cc["@mozilla.org/xre/app-info;1"].
                           getService(Ci.nsIXULAppInfo).
                           QueryInterface(Ci.nsIXULRuntime);
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
  },

  _destroy: function() {
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
    // Only refresh the persona if the user selected a specific persona
    // from the gallery.  If the user selected a random persona, we'll change it
    // the next time we refresh the directory; if the user selected
    // the default persona, we don't need to refresh it, as it doesn't change;
    // and if the user selected a custom persona (which doesn't have an ID),
    // it's not clear what refreshing it would mean.
    if (this.selected != "current" || !this.currentPersona || !this.currentPersona.id)
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
