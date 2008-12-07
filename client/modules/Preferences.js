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
 * The Original Code is Preferences.
 *
 * The Initial Developer of the Original Code is Mozilla.
 * Portions created by the Initial Developer are Copyright (C) 2008
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Myk Melez <myk@mozilla.org>
 *   Daniel Aquino <mr.danielaquino@gmail.com>
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

let EXPORTED_SYMBOLS = ["Preferences"];

const Cc = Components.classes;
const Ci = Components.interfaces;
const Cr = Components.results;
const Cu = Components.utils;

Cu.import("resource://gre/modules/XPCOMUtils.jsm");

function Preferences(prefBranch) {
  if (prefBranch)
    this._prefBranch = prefBranch;
}

Preferences.prototype = {
  _prefBranch: "",

  // Preferences Service

  get _prefSvc() {
    let prefSvc = Cc["@mozilla.org/preferences-service;1"].
                  getService(Ci.nsIPrefService).
                  getBranch(this._prefBranch).
                  QueryInterface(Ci.nsIPrefBranch2);
    this.__defineGetter__("_prefSvc", function() prefSvc);
    return this._prefSvc;
  },

  /**
    * Get the value of a pref, if any; otherwise return the default value.
    *
    * @param   prefName      the name of the pref to get
    * @param   defaultValue  the default value, if any
    *
    * @returns the value of the pref, if any; otherwise the default value
    */
  get: function(prefName, defaultValue) {
    // We can't check for |prefName.constructor == Array| here, since we have
    // a different global object, so we check the constructor name instead.
    if (typeof prefName == "object" && prefName.constructor.name == Array.name)
      return prefName.map(function(v) this.get(v), this);

    switch (this._prefSvc.getPrefType(prefName)) {
      case Ci.nsIPrefBranch.PREF_STRING:
        return this._prefSvc.getCharPref(prefName);

      case Ci.nsIPrefBranch.PREF_INT:
        return this._prefSvc.getIntPref(prefName);

      case Ci.nsIPrefBranch.PREF_BOOL:
        return this._prefSvc.getBoolPref(prefName);

      case Ci.nsIPrefBranch.PREF_INVALID:
      default:
        return defaultValue;
    }
  },

  set: function(prefName, prefValue) {
    // We can't check for |prefName.constructor == Object| here, since we have
    // a different global object, so we check the constructor name instead.
    if (typeof prefName == "object" && prefName.constructor.name == Object.name)
      for (let [name, value] in Iterator(prefName))
        this.set(name, value);
    else {
      switch (typeof prefValue) {
        case "number":
          this._prefSvc.setIntPref(prefName, prefValue);
          break;

        case "boolean":
          this._prefSvc.setBoolPref(prefName, prefValue);
          break;

        case "string":
        default:
          this._prefSvc.setCharPref(prefName, prefValue);
          break;
      }
    }
  },

  // Observers indexed by pref branch and callback.  This lets us get
  // the observer to remove when a caller calls |remove|, passing it a callback.
  // Note: all Preferences instances share this object, since all of them
  // have the same prototype.  This is intentional, because we want callers
  // to be able to remove an observer using a different Preferences object
  // than the one with which they added it.  But it means we have to index
  // the observers in this object by their complete pref branch, not just
  // the branch relative to the root branch of any given Preferences object.
  _observers: {},

  /**
   * Add an observer to a pref branch.  The callback can be a simple function
   * or any object that implements nsIObserver.  The pref branch can be
   * any string and is appended to the root branch for the Preferences instance
   * on which this method is called.  For example, if the Preferences instance
   * has root branch "foo.", and this method is called with branch "bar.",
   * then the callback will observe the complete branch "foo.bar.".
   *
   * @param   callback    {Object}
   *          the nsIObserver or Function to notify about changes
   * @param   prefBranch  {String}  [optional]
   *          the branch for which to notify the callback
   *
   * @returns the wrapped observer
   */
  addObserver: function(callback, prefBranch) {
    let completePrefBranch = this._prefBranch + (prefBranch || "");
    let observer = new PrefObserver(callback);
    if (!(completePrefBranch in Preferences._observers))
      Preferences._observers[completePrefBranch] = {};
    Preferences._observers[completePrefBranch][callback] = observer;
    Preferences._prefSvc.addObserver(completePrefBranch, observer, true);
    return observer;
  },

  /**
   * Remove an observer from a pref branch.
   *
   * @param   callback    {Object}
   *          the nsIObserver or Function to stop notifying about changes
   * @param   prefBranch  {String}  [optional]
   *          the branch for which to stop notifying the callback
   */
  removeObserver: function(callback, prefBranch) {
    let completePrefBranch = this._prefBranch + (prefBranch || "");
    let observer = Preferences._observers[completePrefBranch][callback];
    if (observer) {
      Preferences._prefSvc.removeObserver(completePrefBranch, observer);
      delete this._observers[completePrefBranch][callback];
    }
  },

  // FIXME: make the methods below accept an array of pref names.

  has: function(prefName) {
    return (this._prefSvc.getPrefType(prefName) != Ci.nsIPrefBranch.PREF_INVALID);
  },

  modified: function(prefName) {
    return (this.has(prefName) && this._prefSvc.prefHasUserValue(prefName));
  },

  locked: function(prefName) {
    return this._prefSvc.isLocked(prefName);
  },

  lock: function(prefName) {
    this._prefSvc.lockPref(prefName);
  },

  unlock: function(prefName) {
    this._prefSvc.unlockPref(prefName);
  },

  reset: function(prefName) {
    this._prefSvc.clearUserPref(prefName);
  },

  resetBranch: function(prefBranch) {
    this._prefSvc.resetBranch(prefBranch);
  }

};

// Give the constructor the same prototype as its instances, so users can access
// preferences directly via the constructor without having to create an instance
// first.
Preferences.__proto__ = Preferences.prototype;

function PrefObserver(callback) {
  this._callback = callback;
}

PrefObserver.prototype = {
  QueryInterface: XPCOMUtils.generateQI([Ci.nsIObserver, Ci.nsISupportsWeakReference]),
  observe: function(subject, topic, data) {
    if (typeof this._callback == "function")
      this._callback(subject, topic, data);
    else
      this._callback.observe(subject, topic, data);
  }
}
