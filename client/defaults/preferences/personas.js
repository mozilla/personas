pref("extensions.personas.selected", "default");
pref("extensions.personas.category", "");
pref("extensions.personas.lastrandom", "");
pref("extensions.personas.lastversion", "firstrun");
pref("extensions.personas.url", "http://people.mozilla.com/~myk/personas/store2/");
pref("extensions.personas.siteURL", "http://people.mozilla.com/~myk/personas/");
pref("extensions.personas.lastselected0", "(none)");
pref("extensions.personas.lastselected1", "(none)");
pref("extensions.personas.lastselected2", "(none)");
pref("extensions.personas.lastcategoryupdate", "");
pref("extensions.personas.lastlistupdate", "");
pref("extensions.personas.authorizedHosts", ".mozilla.com");

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
pref("extensions.personas.custom.customName", "Custom Persona");

