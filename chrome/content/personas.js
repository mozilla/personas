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

const PERSONAS_VERSION = "0.9";

var PersonaController = {
  //**************************************************************************//
  // Shortcuts

  // Preference Service
  get _prefSvc() {
    var prefSvc = Cc["@mozilla.org/preferences-service;1"].
                  getService(Ci.nsIPrefBranch);
    delete this._prefSvc;
    this._prefSvc = prefSvc;
    return this._prefSvc;
  },

  // Persona Service
  get _personaSvc() {
    var personaSvc = Cc["@mozilla.org/personas/persona-service;1"].
             getService(Ci.nsIPersonaService);
    delete this._personaSvc;
    this._personaSvc = personaSvc;
    return this._personaSvc;
  },

  get _stringBundle() {
    var stringBundle = document.getElementById("personasStringBundle");
    delete this._stringBundle;
    this._stringBundle = stringBundle;
    return this._stringBundle;
  },

  get _menu() {
    var menu = document.getElementById("personas-selector-menu");
    delete this._menu;
    this._menu = menu;
    return this._menu;
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

  // FIXME: for performance, make this a memoizing getter with a pref listener
  // that updates it as the pref changes.
  get _currentPersona() {
    return this._getPref("extensions.personas.selected", "default");
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
  // Initialization

  startUp: function() {
    // Get the persona service to ensure it gets initialized and starts updating
    // the lists of categories and personas on a regular basis.
    Cc["@mozilla.org/personas/persona-service;1"].getService();

    // Check for a first run or updated extension and display some additional
    // information to users.
    var firstRun = this._getPref("extensions.personas.lastversion"); 
    if (firstRun == "firstrun") {
      let firstRunURL = this._baseURL + this._locale + "/firstrun/?version=" + PERSONAS_VERSION;
      setTimeout(function() { window.openUILinkIn(firstRunURL, "tab") }, 500);
      this._prefSvc.setCharPref("extensions.personas.lastversion", PERSONAS_VERSION);
    }
    else if (firstRun != PERSONAS_VERSION) {
      let updatedURL = this._baseURL + this._locale + "/updated/?version=" + PERSONAS_VERSION;
      setTimeout(function() { window.openUILinkIn(updatedURL, "tab") }, 500);
      this._prefSvc.setCharPref("extensions.personas.lastversion", PERSONAS_VERSION);
    }

    // Apply the current persona to the browser theme.
    this._updateTheme();

    // Observe changes to the selected persona that happen in other windows
    // or by users twiddling the preferences directly.
    this._prefSvc.QueryInterface(Ci.nsIPrefBranch2).
                  addObserver("extensions.personas.selected", this, false);
  },

  shutDown: function() {
    this._prefSvc.QueryInterface(Ci.nsIPrefBranch2).
                  removeObserver("extensions.personas.selected", this);
  },

  // nsISupports
  QueryInterface: function(aIID) {
    if (aIID == Ci.nsIObserver || aIID == Ci.nsISupports)
      return this;

    throw Cr.NS_ERROR_NO_INTERFACE;
  },

  // nsIObserver
  observe: function(subject, topic, data) {
    switch (topic) {
      case "nsPref:changed":
        this._updateTheme();
        break;
    }
  },

  /**
   * Set the current persona to the one with the specified ID.
   *
   * @param personaID the ID of the persona to set as the current one.
   */
  _setPersona: function(personaID, dark) {
    // Update the list of recent personas.
    if (personaID != this._currentPersona) {
      this._prefSvc.setCharPref("extensions.personas.lastselected2",
                                this._getPref("extensions.personas.lastselected1"));
      this._prefSvc.setCharPref("extensions.personas.lastselected1",
                                this._getPref("extensions.personas.lastselected0"));
      this._prefSvc.setCharPref("extensions.personas.lastselected0", this._currentPersona);
    }

    // Save the new selection to prefs.
    this._prefSvc.setBoolPref("extensions.personas.selectedIsDark", dark);
    this._prefSvc.setCharPref("extensions.personas.selected", personaID);
  },
  
  // FIXME: update the menu item to display the persona name as its label.
  _updateTheme: function() {
    let personaID = this._getPref("extensions.personas.selected");
    let isDark = this._getPref("extensions.personas.selectedIsDark");

    // Style the primary toolbar box, adding the new background image.
    var toolbar = document.getElementById("main-window");
    toolbar.style.MozAppearance = "none";
    toolbar.style.backgroundImage = "url('" + this._getToolbarURL(personaID) + "')";
    toolbar.style.backgroundRepeat = "no-repeat";
    toolbar.style.backgroundPosition = "top right";
    // Change text color to reflect dark vs. light personas as advertised by feed.
    toolbar.setAttribute("_personas-dark-style", isDark ? "true" : "");

    statusbar = document.getElementById("status-bar");
    if (statusbar) {
      statusbar.style.MozAppearance = "none";
      if (personaID != "manual")
        statusbar.style.backgroundImage = "url('" + this._getStatusbarURL(personaID) + "')";
      statusbar.style.backgroundRepeat = "no-repeat";
      statusbar.style.backgroundPosition = "top left";
      statusbar.style.backgroundColor = "transparent";
    }

    // Mac-specific code to style the tabbox.
    if (navigator.platform.toLowerCase().indexOf("mac") != -1) {
      var tabbrowser = document.getElementById("content");
      if(tabbrowser.mTabContainer)
        tabbrowser.mTabContainer.style.background = "transparent";
      if(tabbrowser.mStrip)
        tabbrowser.mStrip.style.MozAppearance = "none";
    }

    var urlbar = document.getElementById("urlbar");
    if (urlbar)
      urlbar.style.opacity = "0.8";

    var searchbar = document.getElementById("searchbar");
    if (searchbar)
      searchbar.style.opacity = "0.8";
  },

  _getToolbarURL: function(personaID) {
    switch (personaID) {
      case "default":
        return "chrome://personas/skin/default/tbox-default.jpg";
      case "manual":
        return "file:///" + this.getManualPersona();
      default:
        return this._baseURL + "skins/" + personaID + "/tbox-" + personaID + ".jpg";
    }
  },

  _getStatusbarURL: function(personaID) {
    if (personaID == "default")
      return "chrome://personas/skin/default/stbar-default.jpg";

    return this._baseURL + "skins/" + personaID + "/stbar-" + personaID + ".jpg";
  },

  onPersonaPopupShowing: function(event) {
    if (event.target != this._menu)
      return;

    var categories = this._personaSvc.categories.wrappedJSObject;
    var personas = this._personaSvc.personas.wrappedJSObject;

    this._rebuildMenu(categories, personas);
  },

  _rebuildMenu: function(categories, personas) {
    var openingSeparator = document.getElementById("personasOpeningSeparator");
    var closingSeparator = document.getElementById("personasClosingSeparator");

    // Remove everything between the two separators.
    while (openingSeparator.nextSibling && openingSeparator.nextSibling != closingSeparator)
      this._menu.removeChild(openingSeparator.nextSibling);

    document.getElementById("personas-default").disabled = (this.currentPersona == "default");

    for (let i = 0; i < categories.length; i++) {
      let category = categories[i];

      let menu = document.createElement("menu");
      menu.setAttribute("label", category.label);

      let popupmenu = document.createElement("menupopup");
      popupmenu.setAttribute("id", category.id);

      switch(category.type) {
        case "list":
          for (let j = 0; j < personas.length; j++) {
            let persona = personas[j];

            let needle = category.id;
            let haystack = persona.menu;
            if (haystack.search(needle) == -1)
              continue;

            let item = this._createPersonaItem(persona);
            popupmenu.appendChild(item);
          }
          break;

        case "recent":
          for (let k = 0; k < 3; k++) {
            let recentID = this._getPref("extensions.personas.lastselected" + k);
            if (!recentID)
              continue;

            // Find the persona whose ID matches the one in the preference.
            let persona = personas.filter(function(val) { return val.id == recentID })[0];
            if (!persona)
              continue;

            let item = this._createPersonaItem(persona);
            popupmenu.appendChild(item);
          }
          break;
      }

      menu.appendChild(popupmenu);

      if (categories[i].parent == "top")
        this._menu.insertBefore(menu, closingSeparator);
      else {
        var categoryMenu = document.getElementById(categories[i].parent);
        categoryMenu.insertBefore(menu, categoryMenu.firstChild);
      }
    }
  },

  _createPersonaItem: function(persona) {
    let item = document.createElement("menuitem");

    // We store the ID of the persona in the "personaid" attribute instead of
    // the "id" attribute because "id" has to be unique, and personas sometimes
    // are associated with multiple menuitems (f.e. one in the Recent menu
    // and another in a category menu).
    item.setAttribute("personaid", persona.id);
    item.setAttribute("label", persona.label);
    item.setAttribute("type", "checkbox");
    item.setAttribute("checked", (persona.id == this._currentPersona));
    item.setAttribute("dark", persona.dark);
    item.setAttribute("autocheck", "false");
    item.setAttribute("oncommand", "PersonaController.onSelectPersona(event);");

    return item;
  },

  onSelectPersona: function(event) {
    let personaID = event.target.getAttribute("personaid");
    let dark = (event.target.getAttribute("dark") == "true");
    this._setPersona(personaID, dark);
  },

  onSelectManual: function(event) {
    var fp = Cc["@mozilla.org/filepicker;1"].createInstance(Ci.nsIFilePicker);
    fp.init(window, "Select a File", Ci.nsIFilePicker.modeOpen);
    var result = fp.show();
    if (result == Ci.nsIFilePicker.returnOK) {
      this._prefSvc.setCharPref("extensions.personas.manualPath", fp.file.path);
      this._setPersona("manual", false);
    }
  },

  onSelectAbout: function(event) {
    window.openUILinkIn(this._baseURL + this._locale + "/about/?persona=" + this._currentPersona, "tab");
  }

};

window.addEventListener("load", function(e) { PersonaController.startUp(e); }, false);
window.addEventListener("unload", function(e) { PersonaController.shutDown(e); }, false);
