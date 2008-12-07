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

// Generic modules get imported into the persona controller rather than
// the global namespace after the controller definition below so they don't
// conflict with modules with the same names imported by other extensions.

// It's OK to import the service module into the global namespace because its
// exported symbols all contain the word "persona" (f.e. PersonaService).
Cu.import("resource://personas/modules/service.js");

let PersonaController = {
  _defaultHeaderBackgroundImage: null,
  _defaultFooterBackgroundImage: null,
  _defaultTitlebarColor: null,
  _previewTimeoutID: null,
  _resetTimeoutID: null,

  //**************************************************************************//
  // Shortcuts

  // Generic modules get imported into these properties rather than
  // the global namespace so they don't conflict with modules with the same
  // names imported by other extensions.
  JSON:         null,
  Observers:    null,
  Preferences:  null,
  StringBundle: null,
  URI:          null,

  // Access to extensions.personas.* preferences.  To access other preferences,
  // call the Preferences module directly.
  get _prefs() {
    delete this._prefs;
    return this._prefs = new this.Preferences("extensions.personas.");
  },

  get _strings() {
    delete this._strings;
    return this._strings = new this.StringBundle("chrome://personas/locale/personas.properties");
  },

  get _menu() {
    let menu = document.getElementById("personas-selector-menu");
    delete this._menu;
    this._menu = menu;
    return this._menu;
  },

  get _baseURI() {
    return this.URI.get(this._prefs.get("url"));
  },

  get _siteURL() {
    return this._prefs.get("siteURL");
  },

  get _previewTimeout() {
    return this._prefs.get("previewTimeout");
  },

  get _locale() {
    switch (this.Preferences.get("general.useragent.locale", "en-US")) {
      case 'ja':
      case 'ja-JP-mac':
        return "ja";
    }
    return "en-US";
  },

  /**
   * Escape CSS special characters in unquoted URLs,
   * per http://www.w3.org/TR/CSS21/syndata.html#uri.
   */
  _escapeURLForCSS: function(url) url.replace(/[(),\s'"]/g, "\$&"),


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
dump("observe: " + topic + "\n");
    switch (topic) {
      case "personas:persona:changed":
        this._applyPersona();
        break;
      case "personas:persona:disabled":
        this._applyDefault();
        break;
      case "personas:personaLoadStarted":
        this.showThrobber(data);
        break;
      case "personas:personaLoadFinished":
        this.hideThrobber(data);
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

    // Save the titlebar color.
    this._defaultTitlebarColor = "#C9C9C9";

    // Observe various changes that we should apply to the browser window.
    this.Observers.add(this, "personas:persona:changed");
    this.Observers.add(this, "personas:persona:disabled");
    this.Observers.add(this, "personas:personaLoadStarted");
    this.Observers.add(this, "personas:personaLoadFinished");

    // Listen for various persona-related events that can bubble up from content.
    document.addEventListener("SelectPersona", this, false, true);
    document.addEventListener("PreviewPersona", this, false, true);
    document.addEventListener("ResetPersona", this, false, true);

    // Check for a first-run or updated extension and display some additional
    // information to users.
    let lastVersion = this._prefs.get("lastversion"); 
    let thisVersion = Cc["@mozilla.org/extensions/manager;1"].
                      getService(Ci.nsIExtensionManager).
                      getItemForID(PERSONAS_EXTENSION_ID).version;
    if (lastVersion == "firstrun") {
      let firstRunURL = this._siteURL + this._locale + "/firstrun/?version=" + thisVersion;
      setTimeout(function() { window.openUILinkIn(firstRunURL, "tab") }, 500);
      this._prefs.set("lastversion", thisVersion);
    }
    else if (lastVersion != thisVersion) {
      let updatedURL = this._siteURL + this._locale + "/updated/?version=" + thisVersion;
      setTimeout(function() { window.openUILinkIn(updatedURL, "tab") }, 500);
      this._prefs.set("lastversion", thisVersion);
    }

    // Apply the selected persona (if any) to the window.
    if (PersonaService.activePersona)
      this._applyPersona();
  },

  shutDown: function() {
    document.removeEventListener("SelectPersona", this, false);
    document.removeEventListener("PreviewPersona", this, false);
    document.removeEventListener("ResetPersona", this, false);

    this.Observers.remove(this, "personas:persona:disabled");
    this.Observers.remove(this, "personas:persona:changed");
    this.Observers.remove(this, "personas:personaLoadFinished");
    this.Observers.remove(this, "personas:personaLoadStarted");
  },


  //**************************************************************************//
  // Appearance Updates

  _applyPersona: function() {
dump("_applyPersona: " + this.JSON.stringify(PersonaService.activePersona) + "\n");

    // Style the header.
    let header = document.getElementById("main-window");
    header.setAttribute("persona", PersonaService.activePersona.id);
    let headerURI = this.URI.get(PersonaService.activePersona.header, null, this._baseURI);
    header.style.backgroundImage = "url(" + this._escapeURLForCSS(headerURI.spec) + ")";

    // Style the footer.
    let footer = document.getElementById("browser-bottombox");
    footer.setAttribute("persona", PersonaService.activePersona.id);
    let footerURI = this.URI.get(PersonaService.activePersona.footer, null, this._baseURI);
    footer.style.backgroundImage = "url(" + this._escapeURLForCSS(footerURI.spec) + ")";

    let os = Cc["@mozilla.org/xre/app-info;1"].getService(Ci.nsIXULRuntime).OS;

    // Style the text color.
    if (this._prefs.get("useTextColor")) {
      // FIXME: fall back on the default text color instead of "black".
      let textColor = PersonaService.activePersona.textcolor || "black";
      for (let i = 0; i < document.styleSheets.length; i++) {
        let styleSheet = document.styleSheets[i];
        if (styleSheet.href == "chrome://personas/content/textColor.css") {
          while (styleSheet.cssRules.length > 0)
            styleSheet.deleteRule(0);

          if (os == "Darwin") {
            styleSheet.insertRule(
              "#main-window[persona] .tabbrowser-tab, " +
              "#navigator-toolbox menubar > menu, " +
              "#navigator-toolbox toolbarbutton, " +
              "#browser-bottombox, " +
              "#browser-bottombox toolbarbutton " +
              "{ color: " + textColor + "; font-weight: normal; }",
              0
            );
          }
          else {
            styleSheet.insertRule(
              "#navigator-toolbox menubar > menu, " +
              "#navigator-toolbox toolbarbutton, " +
              "#browser-bottombox, " +
              "#browser-bottombox toolbarbutton " +
              "{ color: " + textColor + "}",
              0
            );
          }

          // FIXME: figure out what to do about the disabled color.  Maybe we
          // should let personas specify it independently and then apply it via
          // a rule like this:
          // #navigator-toolbox toolbarbutton[disabled="true"],
          // #browser-toolbox toolbarbutton[disabled="true"],
          // #browser-bottombox toolbarbutton[disabled="true"]
          //   { color: #cccccc !important; } 

          // Stop iterating through stylesheets.
          break;
        }
      }
    }

    // Style the titlebar with the accent color.
    // Note: we only do this on Mac, since it's the only OS that supports
    // this capability.  It's also the only OS where our hack for applying
    // the change doesn't cause the window to un-maximize.
    if (this._prefs.get("useAccentColor")) {
      if (os == "Darwin") {
        let titlebarColor = PersonaService.activePersona.accentcolor || this._defaultTitlebarColor;
        if (titlebarColor != header.getAttribute("titlebarcolor")) {
          header.setAttribute("activetitlebarcolor", titlebarColor);
          header.setAttribute("inactivetitlebarcolor", titlebarColor);
          header.setAttribute("titlebarcolor", titlebarColor);
          // FIXME: Incredibly gross hack in order to force a window redraw event
          // that ensures that the titlebar color change is applied.  Note that
          // this will unmaximize a maximized window on Windows and Linux, but
          // we only do this on Mac (which is the only place the "titlebarcolor"
          // attribute has any effect anyway at the moment), so it's ok for now.
          // If we ever make this work on Windows and Linux, we'll have to
          // determine the maximized state of the window beforehand and restore
          // it to that state afterwards.
          window.resizeTo(parseInt(window.outerWidth)+1, window.outerHeight);
          window.resizeTo(parseInt(window.outerWidth)-1, window.outerHeight);
        }
      }
    }

  },

  _applyDefault: function() {
    // Reset the header.
    let header = document.getElementById("main-window");
    header.removeAttribute("persona");
    header.style.backgroundImage = this._defaultHeaderBackgroundImage;

    // Reset the footer.
    let footer = document.getElementById("browser-bottombox");
    footer.removeAttribute("persona");
    footer.style.backgroundImage = this._defaultFooterBackgroundImage;

    // Reset the text color.
    for (let i = 0; i < document.styleSheets.length; i++) {
      let styleSheet = document.styleSheets[i];
      if (styleSheet.href == "chrome://personas/content/textColor.css") {
        while (styleSheet.cssRules.length > 0)
          styleSheet.deleteRule(0);
        break;
      }
    }

    // Reset the titlebar to the default color.
    // Note: we only do this on Mac, since it's the only OS that supports
    // this capability.  It's also the only OS where our hack for applying
    // the change doesn't cause the window to un-maximize.
    let os = Cc["@mozilla.org/xre/app-info;1"].getService(Ci.nsIXULRuntime).OS;
    if (os == "Darwin") {
      if (header.getAttribute("titlebarcolor") != this._defaultTitlebarColor) {
        // FIXME: set the active and inactive titlebar colors back to their
        // original values rather than the original value of the plain titlebar
        // color.
        header.setAttribute("activetitlebarcolor", this._defaultTitlebarColor);
        header.setAttribute("inactivetitlebarcolor", this._defaultTitlebarColor);
        header.setAttribute("titlebarcolor", this._defaultTitlebarColor);
          // FIXME: Incredibly gross hack in order to force a window redraw event
          // that ensures that the titlebar color change is applied.  Note that
          // this will unmaximize a maximized window on Windows and Linux, but
          // we only do this on Mac (which is the only place the "titlebarcolor"
          // attribute has any effect anyway at the moment), so it's ok for now.
          // If we ever make this work on Windows and Linux, we'll have to
          // determine the maximized state of the window beforehand and restore
          // it to that state afterwards.
        window.resizeTo(parseInt(window.outerWidth)+1, window.outerHeight);
        window.resizeTo(parseInt(window.outerWidth)-1, window.outerHeight);
      }
    }
  },


  //**************************************************************************//
  // Persona Selection, Preview, and Reset

  /**
   * Select the persona specified by a web page via a SelectPersona event.
   * Checks to ensure the page is hosted on a server authorized to select personas.
   *
   * @param event   {Event}
   *        the SelectPersona DOM event
   */
  onSelectPersonaFromContent: function(event) {
    this._authorizeHost(event);
    this.onSelectPersona(event);
  },

  /**
   * Select the persona specified by the DOM node target of the given event.
   *
   * @param event   {Event}
   *        the SelectPersona DOM event
   */
  onSelectPersona: function(event) {
    let node = event.target;

    if (!node.hasAttribute("persona"))
      throw "node does not have 'persona' attribute";

    let persona = node.getAttribute("persona");

    // The persona attribute is either a JSON string specifying the persona
    // to apply or a string identifying a special persona (default, random).
    switch (persona) {
      case "default":
        PersonaService.changeToDefaultPersona();
        break;
      case "random":
        PersonaService.changeToRandomPersona(node.getAttribute("category"));
        break;
      case "custom":
        PersonaService.changeToPersona(PersonaService.customPersona);
        break;
      default:
        PersonaService.changeToPersona(this.JSON.parse(persona));
        break;
    }
  },

  /**
   * Preview the persona specified by a web page via a PreviewPersona event.
   * Checks to ensure the page is hosted on a server authorized to set personas.
   * 
   * @param   event   {Event}
   *          the PreviewPersona DOM event
   */
  onPreviewPersonaFromContent: function(event) {
    this._authorizeHost(event);
    this.onPreviewPersona(event);
  },

  onPreviewPersona: function(event) {
    if (!this._prefs.get("previewEnabled"))
      return;

    if (!event.target.hasAttribute("persona"))
      throw "node does not have 'persona' attribute";

    //this._previewPersona(event.target.getAttribute("persona"));

    if (this._resetTimeoutID) {
      window.clearTimeout(this._resetTimeoutID);
      this._resetTimeoutID = null;
    }

    let t = this;
    let persona = this.JSON.parse(event.target.getAttribute("persona"));
    let callback = function() { t._previewPersona(persona) };
    this._previewTimeoutID = window.setTimeout(callback, this._previewTimeout);
  },

  _previewPersona: function(persona) {
    PersonaService.previewPersona(persona);
  },

  /**
   * Reset the persona as specified by a web page via a ResetPersona event.
   * Checks to ensure the page is hosted on a server authorized to reset personas.
   * 
   * @param event   {Event}
   *        the ResetPersona DOM event
   */
  onResetPersonaFromContent: function(event) {
    this._authorizeHost(event);
    this.onResetPersona();
  },

  onResetPersona: function(event) {
    if (!this._prefs.get("previewEnabled"))
      return;

    //this._resetPersona();

    if (this._previewTimeoutID) {
      window.clearTimeout(this._previewTimeoutID);
      this._previewTimeoutID = null;
    }

    let t = this;
    let callback = function() { t._resetPersona() };
    this._resetTimeoutID = window.setTimeout(callback, this._previewTimeout);
  },

  _resetPersona: function() {
    PersonaService.resetPersona();
  },

  onSelectPreferences: function() {
    window.openDialog('chrome://personas/content/preferences.xul', '', 
                      'chrome,titlebar,toolbar,centerscreen');
  },

  onViewDirectory: function() {
    window.openUILinkIn(this._siteURL, "tab");
  },

  onEditCustomPersona: function() {
    window.openUILinkIn("chrome://personas/content/customPersonaEditor.xul", "tab");
  },

  onSelectAbout: function(event) {
    window.openUILinkIn(this._siteURL + this._locale + "/about/?persona=" + PersonaService.currentPersona.id, "tab");
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
    let authorizedHosts = this._prefs.get("authorizedHosts").split(/[, ]+/);
    if (!authorizedHosts.some(function(v) { return hostBackwards.indexOf(v.split('').reverse().join('')) == 0 }))
      throw host + " not authorized to modify personas";
  },

  showThrobber: function(aPersonaID) {
    document.getElementById("personas-selector-button").setAttribute("busy", "true");
    let items = this._menu.getElementsByAttribute("personaid", aPersonaID);
    for (let i = 0; i < items.length; i++)
      items[i].setAttribute("busy", "true");
  },

  hideThrobber: function(aPersonaID) {
    document.getElementById("personas-selector-button").removeAttribute("busy");
    let items = this._menu.getElementsByAttribute("personaid", aPersonaID);
    for (let i = 0; i < items.length; i++)
      items[i].removeAttribute("busy");
  },

  //**************************************************************************//
  // Popup Construction

  onMenuButtonMouseDown: function(event) {
    var menuPopup = document.getElementById('personas-selector-menu');
    var menuButton = document.getElementById("personas-selector-button");
      
    // If the menu popup isn't on the menu button, then move the popup onto
    // the button so the popup appears when the user clicks the button.  We'll
    // move the popup back to the Tools > Sync menu when the popup hides.
    if (menuPopup.parentNode != menuButton)
      menuButton.appendChild(menuPopup);
  },

  onMenuPopupHiding: function() {
    var menuPopup = document.getElementById('personas-selector-menu');
    var menu = document.getElementById('personas-menu');

    // If the menu popup isn't on the Tools > Personas menu, then move the popup
    // back onto that menu so the popup appears when the user selects the menu.
    // We'll move the popup back to the menu button when the user clicks on
    // the menu button.
    if (menuPopup.parentNode != menu)
      menu.appendChild(menuPopup);    
  },

  onPersonaPopupShowing: function(event) {
    if (event.target != this._menu)
      return false;

    // FIXME: make this localizable.
    if (!PersonaService.personas) {
      alert("Personas data is not available yet. Please check your network connection and restart Firefox, or try again in a few minutes.");
      return false;
    }

    this._rebuildMenu();

    if (this._prefs.get("showCustomMenu")) {
      let customMenu = document.getElementById("custom-menu");
      // FIXME: make this localizable.
      let name = PersonaService.customPersona ? PersonaService.customPersona.name
                                              : "Custom Persona";
      customMenu.setAttribute("label", name);
      customMenu.setAttribute("hidden", "false");
    }
    else {
      document.getElementById("custom-menu").setAttribute("hidden", "true");
    }

    return true;
  },

  _rebuildMenu: function() {
    let openingSeparator = document.getElementById("personasOpeningSeparator");
    let closingSeparator = document.getElementById("personasClosingSeparator");

    // Remove everything between the two separators.
    while (openingSeparator.nextSibling && openingSeparator.nextSibling != closingSeparator)
      this._menu.removeChild(openingSeparator.nextSibling);

    // Add the item that identifies the selected persona by name.
    let personaStatus = document.getElementById("persona-current");
    if (PersonaService.selected == "random") {
      personaStatus.setAttribute("class", "menuitem-iconic");
      personaStatus.setAttribute("image", "chrome://personas/content/random-feed-16x16.png");
      // FIXME: make this a formatted string using %S in the properties file
      // so it is localizable.
      personaStatus.setAttribute("label", this._strings.get("useRandomPersona.label") + " " +
                                          PersonaService.category + " > " +
                                          PersonaService.currentPersona.name);
    }
    else {
      personaStatus.removeAttribute("class");
      personaStatus.removeAttribute("image");
      personaStatus.setAttribute("label", PersonaService.currentPersona.name);
    }

    // FIXME: factor out the duplicate code below.

    // Create the Most Popular menu.
    {
      let menu = document.createElement("menu");
      menu.setAttribute("label", this._strings.get("popular.label"));
      let popupmenu = document.createElement("menupopup");
  
      for each (let persona in PersonaService.personas.popular)
        popupmenu.appendChild(this._createPersonaItem(persona));

      menu.appendChild(popupmenu);
      this._menu.insertBefore(menu, closingSeparator);
    }

    // Create the New menu.
    {
      let menu = document.createElement("menu");
      menu.setAttribute("label", this._strings.get("new.label"));
      let popupmenu = document.createElement("menupopup");
  
      for each (let persona in PersonaService.personas.recent)
        popupmenu.appendChild(this._createPersonaItem(persona));

      menu.appendChild(popupmenu);
      this._menu.insertBefore(menu, closingSeparator);
    }

    // Create the "Recently Selected" menu.
    {
      let menu = document.createElement("menu");
      menu.setAttribute("label", this._strings.get("recent.label"));
      let popupmenu = document.createElement("menupopup");

      for (let i = 0; i < 4; i++) {
        let persona = this._prefs.get("lastselected" + i);
        if (!persona)
          continue;

        try { persona = this.JSON.parse(persona) }
        catch(ex) { continue }

        popupmenu.appendChild(this._createPersonaItem(persona));
      }

      menu.appendChild(popupmenu);
      this._menu.insertBefore(menu, closingSeparator);
    }

    // Create the Categories menu.
    let categoriesMenu = document.createElement("menu");
    categoriesMenu.setAttribute("label", this._strings.get("categories.label"));
    let categoriesPopup = document.createElement("menupopup");
    categoriesMenu.appendChild(categoriesPopup);
    this._menu.insertBefore(categoriesMenu, closingSeparator);

    // Create the category-specific submenus.
    for each (let category in PersonaService.personas.categories) {
      let menu = document.createElement("menu");
      menu.setAttribute("label", category.name);
      let popupmenu = document.createElement("menupopup");

      for each (let persona in category.personas)
        popupmenu.appendChild(this._createPersonaItem(persona));

      // Create an item that picks a random persona from the category.
      popupmenu.appendChild(document.createElement("menuseparator"));
      popupmenu.appendChild(this._createRandomItem(category.name));

      menu.appendChild(popupmenu);
      categoriesPopup.appendChild(menu);
    }
  },

  _createPersonaItem: function(persona) {
    let item = document.createElement("menuitem");

    item.setAttribute("class", "menuitem-iconic");
    item.setAttribute("label", persona.name);
    item.setAttribute("type", "checkbox");
    item.setAttribute("checked", (persona.id == PersonaService.currentPersona.id));
    item.setAttribute("autocheck", "false");
    item.setAttribute("oncommand", "PersonaController.onSelectPersona(event)");
    item.setAttribute("recent", persona.recent ? "true" : "false");
    item.setAttribute("persona", this.JSON.stringify(persona));
    item.addEventListener("DOMMenuItemActive", function(evt) { PersonaController.onPreviewPersona(evt) }, false);
    item.addEventListener("DOMMenuItemInactive", function(evt) { PersonaController.onResetPersona(evt) }, false);
    
    return item;
  },

  _createRandomItem: function(category) {
    let item = document.createElement("menuitem");

    item.setAttribute("class", "menuitem-iconic");
    item.setAttribute("image", "chrome://personas/content/random-feed-16x16.png");
    // FIXME: insert the category into the localized string via getFormattedString.
    item.setAttribute("label", this._strings.get("useRandomPersona.label") + " " + category);
    item.setAttribute("oncommand", "PersonaController.onSelectPersona(event)");
    item.setAttribute("persona", "random");
    item.setAttribute("category", category);

    return item;
  }

};

// Import generic modules into the persona controller rather than
// the global namespace so they don't conflict with modules with the same names
// imported by other extensions.
Cu.import("resource://personas/modules/JSON.js",          PersonaController);
Cu.import("resource://personas/modules/Observers.js",     PersonaController);
Cu.import("resource://personas/modules/Preferences.js",   PersonaController);
Cu.import("resource://personas/modules/StringBundle.js",  PersonaController);
Cu.import("resource://personas/modules/URI.js",           PersonaController);


window.addEventListener("load", function(e) { PersonaController.startUp(e) }, false);
window.addEventListener("unload", function(e) { PersonaController.shutDown(e) }, false);
