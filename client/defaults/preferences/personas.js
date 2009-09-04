// The type of persona the user selected.  Valid values:
//   default: the default Firefox theme (i.e. no persona)
//    random: a persona randomly selected from extensions.personas.category
//   current: a persona selected by the user
// When this preference is set to "random" or "current", the current persona
// is stored in extensions.personas.current.
pref("extensions.personas.selected", "current");

// The initial persona that should get set on first run for a BYOB/bundle/
// distribution build.
//
// We don't specify this preference by default, as it should only be specified
// by a distribution.ini file. If you want to change the initial persona
// that gets set for other builds, change the hardcoded value in service.js.
//
// pref("extensions.personas.initial", ...);

// The current persona that is displayed in Firefox browser windows.
// Generally, this is the persona that the user selected from a menu
// in the extension or from the web gallery of personas.  But if the user
// selected "random persona from [category]", then this is the persona
// we randomly selected from the category.  And if the user selected
// a custom persona, then this is that persona.
//
// We don't set this preference by default, as it gets set on first run
// by the service and subsequently by the user.
//
// pref("extensions.personas.current", ...);

// The category from which we pick a random persona, when the user selects
// "random persona from [category]".
pref("extensions.personas.category", "");

pref("extensions.personas.lastversion", "firstrun");

// The URL from which we load the data.
// Note: this should be the canonical URL, not one that redirects us
// to another, since we set the If-Modified-Since header so we can find out
// when a persona record has changed, and that header doesn't get preserved
// across redirects because of bug 401564.
pref("extensions.personas.url", "http://www.getpersonas.com/static/");

// The location of the web directory.
// We load a variety of URLs relative to this one.
pref("extensions.personas.siteURL", "http://www.getpersonas.com/");

// The host which creates cookies relevant to this add-on.
pref("extensions.personas.host", "www.getpersonas.com");

// The authorizedHosts preference is a comma and/or space-separated list
// of domains allowed to set and preview personas.
// At a minimum, it must contain a value matching the domain at which the web
// directory is located in order for the directory to work.
pref("extensions.personas.authorizedHosts", "getpersonas.com, www.getpersonas.com, personas.services.mozilla.com");

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

// User preference to enable/disable use of the accent color provided
// by Persona in the feed.
pref("extensions.personas.useAccentColor", true);

// User preference to enable/disable use of the text color provided
// by Persona in the feed.
pref("extensions.personas.useTextColor", true);
pref("extensions.personas.showCustomMenu", false);

// The version of the JSON data feed that this extension expects.
pref("extensions.personas.data.version", 1);

// User preference to specify the rotation interval of personas (in seconds)
// while in "random" or "randomFavorite" mode.
pref("extensions.personas.rotationInterval", 3600); // in seconds == 1 hour
