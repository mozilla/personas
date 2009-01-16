// The type of persona the user selected.  Valid values:
//   default: the default Firefox skin
//    random: a random persona from extensions.personas.category
//   current: the persona in extensions.personas.current
pref("extensions.personas.selected", "default");

// The current persona.  Generally, this is the persona that the user selected
// from a menu in the extension or from the web directory of personas.  But if
// the user selected "random persona from [category]", then this is the persona
// we randomly selected from the category.  And if the user selected a custom
// persona, then this is that persona.
pref("extensions.personas.current", "");

// The category from which we pick a random persona, when the user selects
// "random persona from [category]".
pref("extensions.personas.category", "");

pref("extensions.personas.lastversion", "firstrun");

// The URL from which we load the data.
pref("extensions.personas.url", "http://getpersonas.com/store/");

// The location of the web directory.
// We load a variety of URLs relative to this one.
pref("extensions.personas.siteURL", "http://getpersonas.com/store/");

// The authorizedHosts preference is a comma and/or space-separated list
// of rightmost substrings matching domains allowed to set and preview personas.
// At a minimum, it must contain a value matching the domain at which the web
// directory is located in order for the directory to work.
pref("extensions.personas.authorizedHosts", "getpersonas.com");

pref("extensions.personas.lastselected0", "");
pref("extensions.personas.lastselected1", "");
pref("extensions.personas.lastselected2", "");
pref("extensions.personas.lastcategoryupdate", "");
pref("extensions.personas.lastlistupdate", "");

// The interval between consecutive persona reloads.  Measured in minutes,
// with a default of 24 hours and a minimum of one minute.
pref("extensions.personas.reloadInterval", 1440); 

// The interval between consecutive persona snapshots.  Measured in seconds,
// with a default of 1 hour and a minimum of one second.
pref("extensions.personas.snapshotInterval", 3600);

// How long to wait onmouseover/out before triggering a persona preview
// or reset, in milliseconds.  It's not particularly useful for users to be able
// to set this pref, but it's useful for developers and testers to experiment
// with it to figure out what is the best value for it.
pref("extensions.personas.previewTimeout", 200);

// User preference to enable/disable preview when hovering over popup items.
pref("extensions.personas.previewEnabled", true);

// User preference to enable/disable to pinging of the persona service
// on the selection of a new persona. Used to update the Popularity
// fields in the directory.
pref("extensions.personas.reportSelection", true);

// User preference to enable/disable use of the accent color provided
// by Persona in the feed.
pref("extensions.personas.useAccentColor", true);

// User preference to enable/disable use of the text color provided 
// by Persona in the feed.
pref("extensions.personas.useTextColor", true);
pref("extensions.personas.showCustomMenu", false);

// The version of the JSON data feed that this extension expects.
pref("extensions.personas.data.version", 1);
