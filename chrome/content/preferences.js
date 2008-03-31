let PersonasPreferences = {
  onSelectCustom: function(event) {
    window.close();
    opener.window.openUILinkIn("chrome://personas/content/customPersonaEditor.xul", "tab");
  }
};

