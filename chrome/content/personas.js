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

const PERSONAS_EXTENSION_ID = "personas@christopher.beard";

// In Firefox 3 we import modules using Components.utils.import, but in
// Firefox 2, which doesn't support modules, we use the subscript loader
// to load them as subscripts.
if ("import" in Components.utils)
  Components.utils.import("resource://personas/chrome/content/modules/PrefCache.js");
else {
  let subscriptLoader = Cc["@mozilla.org/moz/jssubscript-loader;1"].
                        getService(Ci.mozIJSSubScriptLoader);
  subscriptLoader.loadSubScript("resource://personas/chrome/content/modules/PrefCache.js");
}

let PersonaController = {
  _defaultHeaderBackgroundImage: null,
  _defaultFooterBackgroundImage: null,
  _previewTimeoutID: null,
  _resetTimeoutID: null,


  //**************************************************************************//
  // Convenience Getters

  // Preference Service
  get _prefSvc() {
    let prefSvc = Cc["@mozilla.org/preferences-service;1"].
                  getService(Ci.nsIPrefBranch);
    delete this._prefSvc;
    this._prefSvc = prefSvc;
    return this._prefSvc;
  },

  // Observer Service
  get _obsSvc() {
    let obsSvc = Cc["@mozilla.org/observer-service;1"].
                 getService(Ci.nsIObserverService);
    delete this._obsSvc;
    this._obsSvc = obsSvc;
    return this._obsSvc;
  },

  get _personaSvc() {
    let personaSvc = Cc["@mozilla.org/personas/persona-service;1"].
                     getService(Ci.nsIPersonaService);
    delete this._personaSvc;
    this._personaSvc = personaSvc;
    return this._personaSvc;
  },

  get _stringBundle() {
    let stringBundle = document.getElementById("personasStringBundle");
    delete this._stringBundle;
    this._stringBundle = stringBundle;
    return this._stringBundle;
  },

  get _menu() {
    let menu = document.getElementById("personas-selector-menu");
    delete this._menu;
    this._menu = menu;
    return this._menu;
  },

  get _prefCache() {
    let prefCache = new PersonasPrefCache("");
    delete this._prefCache;
    this._prefCache = prefCache;
    return this._prefCache;
  },

  _getPref: function(aPrefName, aDefaultValue) {
    return this._prefCache.getPref(aPrefName, aDefaultValue);
  },

  get _selectedPersona() {
    return this._getPref("extensions.personas.selected", "default");
  },

  get _baseURL() {
    return this._getPref("extensions.personas.url");
  },

  get _previewTimeout() {
    return this._getPref("extensions.personas.previewTimeout");
  },

  get _locale() {
    switch (this._getPref("general.useragent.locale", "en-US")) {
      case 'ja':
      case 'ja-JP-mac':
        return "ja";
    }

    return "en-US";
  },


  //**************************************************************************//
  // XPCOM Interface Implementations

  // nsISupports
  QueryInterface: function(aIID) {
    if (aIID.equals(Ci.nsIObserver) ||
        aIID.equals(Ci.nsIDOMEventListener) ||
        aIID.equals(Ci.nsISupports))
      return this;
    
    throw Cr.NS_ERROR_NO_INTERFACE;
  },

  // nsIObserver
  observe: function(subject, topic, data) {
    switch (topic) {
      case "personas:selectedPersonaUpdated":
        this._applyPersona();
        break;
      case "personas:defaultPersonaSelected":
        this._applyDefault();
        break;
    }
  },

  // nsIDOMEventListener

  handleEvent: function(aEvent) {
    switch (aEvent.type) {
      case "SelectPersona":
        this.onSelectPersonaFromContent(aEvent);
        break;
      case "PreviewPersona":
        this.onPreviewPersonaFromContent(aEvent);
        break;
      case "ResetPersona":
        this.onResetPersonaFromContent(aEvent);
        break;
    }
  },


  //**************************************************************************//
  // Initialization & Destruction

  startUp: function() {
    // Make sure there's a bottombox element enclosing the items below
    // the browser widget.  Firefox 3 beta 4 and later have one, but earlier
    // releases of the browser don't, and that's what we style.
    if (!document.getElementById("browser-bottombox")) {
      let bottomBox = document.createElement("vbox");
      bottomBox.setAttribute("id", "browser-bottombox");
      let previousNode =
        // #ifdef TOOLBAR_CUSTOMIZATION_SHEET
        document.getElementById("customizeToolbarSheetPopup") ||
        // Firefox 2
        document.getElementById("browser-stack") ||
        // Firefox 3
        document.getElementById("browser");
      let parentNode = document.getElementById("main-window");
      parentNode.insertBefore(bottomBox, previousNode.nextSibling);
      while (bottomBox.nextSibling)
        bottomBox.appendChild(bottomBox.nextSibling);
    }

    // Record the default header and footer background images so we can
    // revert to them if the user selects the default persona.
    let header = document.getElementById("main-window");
    this._defaultHeaderBackgroundImage = header.style.backgroundImage;
    let footer = document.getElementById("browser-bottombox");
    if (footer)
      this._defaultFooterBackgroundImage = footer.style.backgroundImage;

    // Observe various changes that we should apply to the browser window.
    this._obsSvc.addObserver(this, "personas:selectedPersonaUpdated", false);
    this._obsSvc.addObserver(this, "personas:defaultPersonaSelected", false);

    // Listen for various persona-related events that can bubble up from content.
    document.addEventListener("SelectPersona", this, false, true);
    document.addEventListener("PreviewPersona", this, false, true);
    document.addEventListener("ResetPersona", this, false, true);

    // Check for a first-run or updated extension and display some additional
    // information to users.
    let lastVersion = this._getPref("extensions.personas.lastversion"); 
    let thisVersion = Cc["@mozilla.org/extensions/manager;1"].
                      getService(Ci.nsIExtensionManager).
                      getItemForID(PERSONAS_EXTENSION_ID).version;
    if (lastVersion == "firstrun") {
      let firstRunURL = this._baseURL + this._locale + "/firstrun/?version=" + thisVersion;
      setTimeout(function() { window.openUILinkIn(firstRunURL, "tab") }, 500);
      this._prefSvc.setCharPref("extensions.personas.lastversion", thisVersion);
    }
    else if (lastVersion != thisVersion) {
      let updatedURL = this._baseURL + this._locale + "/updated/?version=" + thisVersion;
      setTimeout(function() { window.openUILinkIn(updatedURL, "tab") }, 500);
      this._prefSvc.setCharPref("extensions.personas.lastversion", thisVersion);
    }

    // If the persona is already available, apply it.  Otherwise we'll apply it
    // when notified that it's ready.
    if (this._personaSvc.headerURL && this._personaSvc.footerURL)
      this._applyPersona();
  },

  shutDown: function() {
    document.removeEventListener("SelectPersona", this, false);
    document.removeEventListener("PreviewPersona", this, false);
    document.removeEventListener("ResetPersona", this, false);

    this._obsSvc.removeObserver(this, "personas:selectedPersonaUpdated");
    this._obsSvc.removeObserver(this, "personas:defaultPersonaSelected");
  },


  //**************************************************************************//
  // Appearance Updates

  _applyPersona: function() {
    // FIXME: distinguish between selected and loaded personas so we set
    // the text color correctly when the selected persona is "random" and the
    // loaded persona is some specific persona that could be light or dark.

    let personaID = this._selectedPersona;

    // FIXME: figure out where to locate this function and put it there.
    // Escape CSS special characters in unquoted URLs
    // per http://www.w3.org/TR/CSS21/syndata.html#uri
    function escapeCSSURL(aURLSpec) {
      return aURLSpec.replace(/[(),\s'"]/g, "\$&");
    }

    // Style the header.
    let headerURL = this._personaSvc.headerURL;
    let header = document.getElementById("main-window");
    header.setAttribute("persona", personaID);
    header.style.backgroundImage = "url(" + escapeCSSURL(headerURL) + ")";
    let isDark = this._getDarkPropertyByPersona(personaID);
    header.setAttribute("_personas-dark-style", isDark ? "true" : "");

    // Style the footer.
    let footerURL = this._personaSvc.footerURL;
    let footer = document.getElementById("browser-bottombox");
    footer.setAttribute("persona", personaID);
    footer.style.backgroundImage = "url('" + escapeCSSURL(footerURL) + "')";
  },

  _applyDefault: function() {
    let header = document.getElementById("main-window");
    header.removeAttribute("persona");
    header.style.backgroundImage = this._defaultHeaderBackgroundImage;
    header.removeAttribute("_personas-dark-style");

    let footer = document.getElementById("browser-bottombox");
    footer.removeAttribute("persona");
    footer.style.backgroundImage = this._defaultFooterBackgroundImage;
  },

  _getDarkPropertyByPersona: function(personaID) {

    // FIXME: temporary hack to get around slow loading on initialization     
    if (!this._personaSvc.personas)
      return false;

    let personas = this._personaSvc.personas.wrappedJSObject;

    for each (let persona in personas)
      if (persona.id == personaID)
        return typeof persona.dark != "undefined" && persona.dark == "true";

    return false;
  },


  //**************************************************************************//
  // Persona Selection, Preview, and Reset

  /**
   * Select a persona from content via a SelectPersona event.  Checks to ensure
   * the page is hosted on a server authorized to select personas and the persona
   * is in the list of personas known to the persona service.  Retrieves the ID
   * of the persona from the "persona" attribute on the target of the event.
   *
   * @param aEvent {Event} the SelectPersona DOM event
   */
  onSelectPersonaFromContent: function(aEvent) {
    this._authorizeHost(aEvent);

    if (!aEvent.target.hasAttribute("persona"))
      throw "node does not have 'persona' attribute";

    let personaID = aEvent.target.getAttribute("persona");

    if (!this._getPersona(personaID))
      throw "unknown persona " + personaID;

    let categoryID = aEvent.target.getAttribute("category");

    this._selectPersona(personaID, categoryID);
  },

  onSelectPersona: function(aEvent) {
    let personaID = aEvent.target.getAttribute("personaid");
    let categoryID = aEvent.target.getAttribute("categoryid");
    this._selectPersona(personaID, categoryID);
  },

  /**
   * Select the persona with the specified ID.
   *
   * @param personaID the ID of the persona to select
   * @param categoryID the ID of the category to which persona belongs
   */
  _selectPersona: function(personaID, categoryID) {
    // Update the list of recent personas.
    if (personaID != "default" && personaID != this._selectedPersona && this._selectedPersona != "random") {
      this._prefSvc.setCharPref("extensions.personas.lastselected2",
                                this._getPref("extensions.personas.lastselected1"));
      this._prefSvc.setCharPref("extensions.personas.lastselected1",
                                this._getPref("extensions.personas.lastselected0"));
      this._prefSvc.setCharPref("extensions.personas.lastselected0", this._selectedPersona);
    }

    // Save the new selection to prefs.
    this._prefSvc.setCharPref("extensions.personas.selected", personaID);
    this._prefSvc.setCharPref("extensions.personas.category", categoryID);
  },

  /**
   * Preview the persona specified by a web page via a PreviewPersona event.
   * Checks to ensure the page is hosted on a server authorized to set personas
   * and the persona is in the list of personas known to the persona service.
   * Retrieves the ID of the persona from the "persona" attribute on the target
   * of the event.
   * 
   * @param aEvent {Event} the PreviewPersona DOM event
   */
  onPreviewPersonaFromContent: function(aEvent) {
    this._authorizeHost(aEvent);

    if (!aEvent.target.hasAttribute("persona"))
      throw "node does not have 'persona' attribute";

    let personaID = aEvent.target.getAttribute("persona");

    if (!this._getPersona(personaID))
      throw "unknown persona " + personaID;

    this._previewPersona(personaID);
  },

  onPreviewPersona: function(aEvent) {
    //this._previewPersona(aEvent.target.getAttribute("personaid"));

    if (this._resetTimeoutID) {
      window.clearTimeout(this._resetTimeoutID);
      this._resetTimeoutID = null;
    }

    let t = this;
    let personaID = aEvent.target.getAttribute("personaid");
    let callback = function() { t._previewPersona(personaID) };
    this._previewTimeoutID = window.setTimeout(callback, this._previewTimeout);
  },

  _previewPersona: function(aPersonaID) {
    this._personaSvc.previewPersona(aPersonaID);
  },

  /**
   * Reset the displayed persona to the selected persona via a ResetPersona event.
   * Checks to ensure the page is hosted on a server authorized to modify personas
   * and the persona is in the list of personas known to the persona service.
   * Retrieves the ID of the persona from the "persona" attribute on the target
   * of the event.
   * 
   * @param aEvent {Event} the ResetPersona DOM event
   */
  onResetPersonaFromContent: function(aEvent) {
    this._authorizeHost(aEvent);
    this._resetPersona();
  },

  onResetPersona: function(aEvent) {
    //this._resetPersona();

    if (this._previewTimeoutID) {
      window.clearTimeout(this._previewTimeoutID);
      this._previewTimeoutID = null;
    }

    let t = this;
    let personaID = aEvent.target.getAttribute("personaid");
    let callback = function() { t._resetPersona(personaID) };
    this._resetTimeoutID = window.setTimeout(callback, this._previewTimeout);
  },

  _resetPersona: function() {
    this._personaSvc.resetPersona();
  },

  onSelectDefault: function() {
    this._selectPersona("default", "");
  },

  onSelectManual: function(event) {
    let fp = Cc["@mozilla.org/filepicker;1"].createInstance(Ci.nsIFilePicker);
    fp.init(window, "Select a File", Ci.nsIFilePicker.modeOpen);
    let result = fp.show();
    if (result == Ci.nsIFilePicker.returnOK) {
      this._prefSvc.setCharPref("extensions.personas.custom.headerURL",
                                fp.fileURL.spec);
      this._selectPersona("manual", "");
    }
  },

  onSelectAbout: function(event) {
    window.openUILinkIn(this._baseURL + this._locale + "/about/?persona=" + this._selectedPersona, "tab");
  },

  /**
   * Ensure the host that loaded the document from which the given DOM event
   * came matches an entry in the personas whitelist.  The host matches if it
   * ends with one of the entries in the whitelist.  For example, if
   * .mozilla.com is an entry in the whitelist, then www.mozilla.com matches,
   * as does labs.mozilla.com, but mozilla.com does not, nor does evil.com.
   * 
   * @param aEvent {Event} the DOM event
   */
  _authorizeHost: function(aEvent) {
    let host = aEvent.target.ownerDocument.location.hostname;
    let hostBackwards = host.split('').reverse().join('');
    let authorizedHosts = this._getPref("extensions.personas.authorizedHosts").split(/[, ]+/);
    if (!authorizedHosts.some(function(v) { return hostBackwards.indexOf(v.split('').reverse().join('')) == 0 }))
      throw host + " not authorized to modify personas";
  },

  _getPersona: function(aPersonaID) {
    for each (let persona in this._personaSvc.personas.wrappedJSObject)
      if (persona.id == aPersonaID)
        return persona;

    return null;
  },


  //**************************************************************************//
  // Popup Construction

  onPersonaPopupShowing: function(event) {
    if (event.target != this._menu)
      return;

    // FIXME: make sure we have this data and display something meaningful
    // if we don't have it yet.
    let categories = this._personaSvc.categories.wrappedJSObject;
    let personas = this._personaSvc.personas.wrappedJSObject;

    this._rebuildMenu(categories, personas);
  },

  _rebuildMenu: function(categories, personas) {
    let openingSeparator = document.getElementById("personasOpeningSeparator");
    let closingSeparator = document.getElementById("personasClosingSeparator");

    // Remove everything between the two separators.
    while (openingSeparator.nextSibling && openingSeparator.nextSibling != closingSeparator)
      this._menu.removeChild(openingSeparator.nextSibling);

    //document.getElementById("personas-default").disabled = (this.currentPersona == "default");

    let personaStatus = document.getElementById("persona-current");
    if (this._selectedPersona == "random") {
       personaStatus.setAttribute("class", "menuitem-iconic");
       personaStatus.setAttribute("image", "chrome://personas/skin/random-feed-16x16.png");
       personaStatus.setAttribute("label", this._stringBundle.getString("useRandomPersona.label") + " " +
                                           this._getCategoryName(this._getPref("extensions.personas.category")) + " : " +
                                           this._getPersonaName(this._getPref("extensions.personas.lastrandom")));
    }
    else {
       personaStatus.removeAttribute("class");
       personaStatus.removeAttribute("image");
       personaStatus.setAttribute("label", this._getPersonaName(this._selectedPersona));
    }

    document.getElementById("personas-manual-separator").hidden =
    document.getElementById("personas-manual").hidden =
      (this._getPref("extensions.personas.editor") != "manual");

    for each (let category in categories) {
      let menu = document.createElement("menu");
      menu.setAttribute("label", category.label);

      let popupmenu = document.createElement("menupopup");
      popupmenu.setAttribute("id", category.id);

      switch (category.type) {
        case "list":
          for each (let persona in personas) {
            let needle = category.id;
            let haystack = persona.menu;
            if (haystack.search(needle) == -1)
              continue;

            let item = this._createPersonaItem(persona, category.id);
            popupmenu.appendChild(item);
          }

          // Create an item that picks a random persona from the category.
          popupmenu.appendChild(document.createElement("menuseparator"));

          let (item = document.createElement("menuitem")) {
            item.setAttribute("personaid", "random");
            item.setAttribute("categoryid", category.id);
            item.setAttribute("class", "menuitem-iconic");
            item.setAttribute("image", "chrome://personas/skin/random-feed-16x16.png");
            item.setAttribute("label", this._stringBundle.getString("useRandomPersona.label") + " " + category.label);
            item.setAttribute("oncommand", "PersonaController.onSelectPersona(event.target);");
            popupmenu.appendChild(item);
          }

          break;

        case "recent":
          for (let i = 0; i < 3; i++) {
            let recentID = this._getPref("extensions.personas.lastselected" + i);
            if (!recentID)
              continue;

            // Find the persona whose ID matches the one in the preference.
            for each (let persona in personas) {
              if (persona.id == recentID) {
                let item = this._createPersonaItem(persona, "");
                popupmenu.appendChild(item);
                break;
              }
            }
          }
          break;
      }

      menu.appendChild(popupmenu);

      if (category.parent == "top")
        this._menu.insertBefore(menu, closingSeparator);
      else {
        let categoryMenu = document.getElementById(category.parent);
        categoryMenu.insertBefore(menu, categoryMenu.firstChild);
      }
    }
  },

  _getCategoryName: function(categoryID) {
    let categories = this._personaSvc.categories.wrappedJSObject;

    for each (let category in categories)
      if (category.id == categoryID)
        return category.label;

    return "(unknown)";
  },

  _getPersonaName: function(personaID) {
    let personas = this._personaSvc.personas.wrappedJSObject;
    let defaultString = this._stringBundle.getString("Default");

    if (personaID == "default")
      return defaultString;

    for each (let persona in personas)
      if (persona.id == personaID)
        return persona.label;

    return defaultString;
  },

  _createPersonaItem: function(persona, categoryid) {
    let item = document.createElement("menuitem");

    // We store the ID of the persona in the "personaid" attribute instead of
    // the "id" attribute because "id" has to be unique, and personas sometimes
    // are associated with multiple menuitems (f.e. one in the Recent menu
    // and another in a category menu).
    item.setAttribute("personaid", persona.id);
    item.setAttribute("label", persona.label);
    item.setAttribute("type", "checkbox");
    item.setAttribute("checked", (persona.id == this._selectedPersona));
    item.setAttribute("autocheck", "false");
    item.setAttribute("categoryid", categoryid);
    item.setAttribute("oncommand", "PersonaController.onSelectPersona(event)");
    item.addEventListener("DOMMenuItemActive", function(evt) { PersonaController.onPreviewPersona(evt) }, false);
    item.addEventListener("DOMMenuItemInactive", function(evt) { PersonaController.onResetPersona(evt) }, false);

    return item;
  }

};

window.addEventListener("load", function(e) { PersonaController.startUp(e) }, false);
window.addEventListener("unload", function(e) { PersonaController.shutDown(e) }, false);
