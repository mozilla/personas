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
 *   Chris <kidkog@gmail.com>
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
    delete this._menu;
    return this._menu = document.getElementById("personas-menu");
  },

  get _menuButton() {
    delete this._menuButton;
    return this._menuButton = document.getElementById("personas-selector-button");
  },

  get _menuPopup() {
    delete this._menuPopup;
    return this._menuPopup = document.getElementById("personas-selector-menu");
  },

  get _siteURL() {
    return this._prefs.get("siteURL");
  },

  get _previewTimeout() {
    return this._prefs.get("previewTimeout");
  },

  // XXX We used to use this to direct users to locale-specific directories
  // on the personas server, but we're not using it anymore, as we no longer
  // have locale-specific pages on the server.  And once we get them back,
  // it'll probably make more sense for the browser and server to do locale
  // negotiation using the standard mechanisms anyway, so this is no longer
  // needed.
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
    switch (topic) {
      case "personas:persona:changed":
        if (PersonaService.previewingPersona)
          this._applyPersona(PersonaService.previewingPersona);
        else if (PersonaService.selected == "default")
          this._applyDefault();
        else
          this._applyPersona(PersonaService.currentPersona);
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
      case "CheckPersonas":
        this.onCheckPersonasFromContent(aEvent);
        break;
    }
  },


  //**************************************************************************//
  // Initialization & Destruction

  startUp: function() {
    // Set the label for the tooltip that informs users when personas data
    // is unavailable.
    // FIXME: make this a DTD entity rather than a properties string.
    document.getElementById("personasDataUnavailableTooltip").label =
      this._strings.get("dataUnavailable");

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
    this.Observers.add("personas:persona:changed", this);

    // Listen for various persona-related events that can bubble up from content.
    document.addEventListener("SelectPersona", this, false, true);
    document.addEventListener("PreviewPersona", this, false, true);
    document.addEventListener("ResetPersona", this, false, true);
    document.addEventListener("CheckPersonas", this, false, true);

    // Check for a first-run or updated extension and display some additional
    // information to users.
    let lastVersion = this._prefs.get("lastversion");
    let thisVersion = Cc["@mozilla.org/extensions/manager;1"].
                      getService(Ci.nsIExtensionManager).
                      getItemForID(PERSONAS_EXTENSION_ID).version;
    if (lastVersion == "firstrun") {
      // Change to the initial persona (the one we show users when they first
      // install the extension) and show the first run page.
      PersonaService.changeToPersona(this.JSON.parse(this._prefs.get("initial")));
      let firstRunURL = this._siteURL + "firstrun/?version=" + thisVersion;
      setTimeout(function() { window.openUILinkIn(firstRunURL, "tab") }, 500);
      this._prefs.set("lastversion", thisVersion);
    }
    else if (lastVersion != thisVersion) {
      let updatedURL = this._siteURL + "updated/?version=" + thisVersion;
      setTimeout(function() { window.openUILinkIn(updatedURL, "tab") }, 500);
      this._prefs.set("lastversion", thisVersion);
    }

    // Apply the current persona to the window.
    // We don't apply the default persona because Firefox starts with that.
    if (PersonaService.selected != "default")
      this._applyPersona(PersonaService.currentPersona);
  },

  shutDown: function() {
    document.removeEventListener("SelectPersona", this, false);
    document.removeEventListener("PreviewPersona", this, false);
    document.removeEventListener("ResetPersona", this, false);
    document.removeEventListener("CheckPersonas", this, false);

    this.Observers.remove("personas:persona:changed", this);
  },


  //**************************************************************************//
  // Appearance Updates

  _applyPersona: function(persona) {
    // Style the header.
    let header = document.getElementById("main-window");
    header.setAttribute("persona", persona.id);
    // Use the URI module to resolve the possibly relative URI to an absolute one.
    let headerURI = this.URI.get(persona.header,
                                 null,
                                 this.URI.get(PersonaService.baseURI));
    header.style.backgroundImage = "url(" + this._escapeURLForCSS(headerURI.spec) + ")";

    // Style the footer.
    let footer = document.getElementById("browser-bottombox");
    footer.setAttribute("persona", persona.id);
    // Use the URI module to resolve the possibly relative URI to an absolute one.
    let footerURI = this.URI.get(persona.footer,
                                 null,
                                 this.URI.get(PersonaService.baseURI));
    footer.style.backgroundImage = "url(" + this._escapeURLForCSS(footerURI.spec) + ")";

    let os = Cc["@mozilla.org/xre/app-info;1"].getService(Ci.nsIXULRuntime).OS;

    // Style the text color.
    if (this._prefs.get("useTextColor")) {
      // FIXME: fall back on the default text color instead of "black".
      let textColor = persona.textcolor || "black";
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
        let titlebarColor = persona.accentcolor || this._defaultTitlebarColor;
        if (titlebarColor != header.getAttribute("titlebarcolor")) {
          header.setAttribute("activetitlebarcolor", titlebarColor);
          header.setAttribute("inactivetitlebarcolor", titlebarColor);
          header.setAttribute("titlebarcolor", titlebarColor);

          // FIXME: Incredibly gross hack in order to force a window redraw
          // event that ensures that the titlebar color change is applied.
          //
          // This will unmaximize a maximized window on Windows and Linux,
          // but we only do this on Mac (which is the only place
          // the "titlebarcolor" attribute has any effect anyway at the moment),
          // so that's ok for now.
          //
          // This will unminimize a minimized window on Mac, so we can't do it
          // if the window is minimized.
          if (window.windowState != Ci.nsIDOMChromeWindow.STATE_MINIMIZED) {
            window.resizeTo(parseInt(window.outerWidth)+1, window.outerHeight);
            window.resizeTo(parseInt(window.outerWidth)-1, window.outerHeight);
          }
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

  /**
   * Confirm that Firefox has this Personas extension when requested by
   * a web page via a CheckPersonas event.  Checks to ensure the page is hosted
   * on a host in the whitelist before responding to the event, so only
   * whitelisted pages can find out if Personas is installed.
   * 
   * @param event   {Event}
   *        the CheckPersonas DOM event
   */
  onCheckPersonasFromContent: function(event) {
    this._authorizeHost(event);
    event.target.setAttribute("personas", "true");
  },

  onSelectPreferences: function() {
    window.openDialog('chrome://personas/content/preferences.xul', '', 
                      'chrome,titlebar,toolbar,centerscreen');
  },

  onViewDirectory: function() {
    window.openUILinkIn(this._siteURL + "gallery/All/Recent", "tab");
  },

  onEditCustomPersona: function() {
    let editorUrl = "chrome://personas/content/customPersonaEditor.xul";
    let found = false;
    let tabbrowser = window.getBrowser();

    // Check each tab of this browser for the editor XUL file
    let numTabs = tabbrowser.browsers.length;
    for (let index = 0; index < numTabs; index++) {
      let currentBrowser = tabbrowser.getBrowserAtIndex(index);
      if (editorUrl == currentBrowser.currentURI.spec) {
        // The editor is already opened. Select this tab.
        tabbrowser.selectedTab = tabbrowser.mTabs[index];
        found = true;
        break;
      }
    }

    // If the editor's not open...
    if (!found)
      window.openUILinkIn(editorUrl, "tab");
  },

  onSelectAbout: function(event) {
    if (PersonaService.currentPersona && PersonaService.currentPersona.id)
      window.openUILinkIn(this._siteURL + "about/?persona=" + PersonaService.currentPersona.id, "tab");
  },

  /**
   * Ensure the host that loaded the document from which the given DOM event
   * came matches an entry in the personas whitelist.  The host matches if it
   * equals one of the entries in the whitelist.  For example, if
   * www.mozilla.com is an entry in the whitelist, then www.mozilla.com matches,
   * but labs.mozilla.com, mozilla.com, and evil.com do not.
   * 
   * @param aEvent {Event} the DOM event
   */
  _authorizeHost: function(aEvent) {
    let host = aEvent.target.ownerDocument.location.hostname;
    let authorizedHosts = this._prefs.get("authorizedHosts").split(/[, ]+/);
    if (!authorizedHosts.some(function(v) v == host))
      throw host + " not authorized to modify personas";
  },


  //**************************************************************************//
  // Popup Construction

  onMenuButtonMouseDown: function(event) {
    // If the menu popup isn't on the menu button, then move the popup
    // onto the button so the popup appears when the user clicks it.
    // We'll move the popup back onto the Personas menu in the Tools menu
    // when the popup hides.
    // FIXME: remove this workaround once bug 461899 is fixed.
    if (this._menuPopup.parentNode != this._menuButton)
      this._menuButton.appendChild(this._menuPopup);
  },

  onPopupShowing: function(event) {
    if (event.target == this._menuPopup)
      this._rebuildMenu();

    return true;
  },

  onPopupHiding: function(event) {
    if (event.target == this._menuPopup) {
      // If the menu popup isn't on the Personas menu in the Tools menu,
      // then move the popup back onto that menu so the popup appears when
      // the user selects it.  We'll move the popup back onto the menu button
      // in onMenuButtonMouseDown when the user clicks on the menu button.
      if (this._menuPopup.parentNode != this._menu)
        this._menu.appendChild(this._menuPopup);
    }
  },

  _rebuildMenu: function() {
    // If we don't have personas data, we won't be able to fully build the menu,
    // and we'll display a message to that effect in tooltips over the parts
    // of the menu that are data-dependent (the Most Popular, New, and
    // By Category submenus).  The message also suggests that the user try again
    // in a few minutes, so here we immediately try to refresh data so it will
    // be available when the user tries again.
    if (!PersonaService.personas)
      PersonaService.refreshData();

    let openingSeparator = document.getElementById("personasOpeningSeparator");
    let closingSeparator = document.getElementById("personasClosingSeparator");

    // Remove everything between the two separators.
    while (openingSeparator.nextSibling && openingSeparator.nextSibling != closingSeparator)
      this._menuPopup.removeChild(openingSeparator.nextSibling);

    // Update the item that identifies the current persona.
    let personaStatus = document.getElementById("persona-current");
    let name = PersonaService.currentPersona ? PersonaService.currentPersona.name
                                             : this._strings.get("unnamedPersona");
    if (PersonaService.selected == "random") {
      personaStatus.setAttribute("class", "menuitem-iconic");
      personaStatus.setAttribute("image", "chrome://personas/content/random-feed-16x16.png");
      personaStatus.setAttribute("label", this._strings.get("randomPersona",
                                                            [PersonaService.category,
                                                             name]));
    }
    else {
      personaStatus.removeAttribute("class");
      personaStatus.removeAttribute("image");
      if (PersonaService.selected == "default")
        personaStatus.setAttribute("label", this._strings.get("Default"));
      else
        personaStatus.setAttribute("label", name);
    }

    // Update the checkmark on the Default menu item.
    document.getElementById("defaultPersona").setAttribute("checked", (PersonaService.selected == "default" ? "true" : "false"));

    // FIXME: factor out the duplicate code below.

    // Create the Most Popular menu.
    {
      let menu = document.createElement("menu");
      menu.setAttribute("label", this._strings.get("popular"));

      if (PersonaService.personas) {
        let popupmenu = document.createElement("menupopup");
        for each (let persona in PersonaService.personas.popular)
          popupmenu.appendChild(this._createPersonaItem(persona));
        menu.appendChild(popupmenu);
      }
      else {
        menu.setAttribute("disabled", "true");
        menu.setAttribute("tooltip", "personasDataUnavailableTooltip");
      }

      this._menuPopup.insertBefore(menu, closingSeparator);
    }

    // Create the New menu.
    {
      let menu = document.createElement("menu");
      menu.setAttribute("label", this._strings.get("new"));

      if (PersonaService.personas) {
        let popupmenu = document.createElement("menupopup");
        for each (let persona in PersonaService.personas.recent)
          popupmenu.appendChild(this._createPersonaItem(persona));
        menu.appendChild(popupmenu);
      }
      else {
        menu.setAttribute("disabled", "true");
        menu.setAttribute("tooltip", "personasDataUnavailableTooltip");
      }

      this._menuPopup.insertBefore(menu, closingSeparator);
    }

    // Create the "Recently Selected" menu.
    {
      let menu = document.createElement("menu");
      menu.setAttribute("label", this._strings.get("recent"));
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
      this._menuPopup.insertBefore(menu, closingSeparator);
    }

    // Create the Categories menu.
    let categoriesMenu = document.createElement("menu");
    categoriesMenu.setAttribute("label", this._strings.get("categories"));

    if (PersonaService.personas) {
      let categoriesPopup = document.createElement("menupopup");

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

      categoriesMenu.appendChild(categoriesPopup);
    }
    else {
      categoriesMenu.setAttribute("disabled", "true");
      categoriesMenu.setAttribute("tooltip", "personasDataUnavailableTooltip");
    }

    this._menuPopup.insertBefore(categoriesMenu, closingSeparator);

    // Update the Custom menu.
    let customMenu = document.getElementById("custom-menu");
    if (this._prefs.get("showCustomMenu")) {
      let name = PersonaService.customPersona &&
                 PersonaService.customPersona.name ? PersonaService.customPersona.name
                                                   : this._strings.get("customPersona");
      customMenu.setAttribute("label", name);
      customMenu.hidden = false;
    }
    else
      customMenu.hidden = true;
  },

  _createPersonaItem: function(persona) {
    let item = document.createElement("menuitem");

    item.setAttribute("class", "menuitem-iconic");
    item.setAttribute("label", persona.name);
    item.setAttribute("type", "checkbox");
    item.setAttribute("checked", (PersonaService.selected != "default" &&
                                  PersonaService.currentPersona &&
                                  PersonaService.currentPersona.id == persona.id));
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
    item.setAttribute("label", this._strings.get("useRandomPersona", [category]));
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
