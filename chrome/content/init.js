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
 * Portions created by the Initial Developer are Copyright (C) 2008
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
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
if ("import" in Components.utils) {
  let ioSvc = Components.classes["@mozilla.org/network/io-service;1"].
              getService(Components.interfaces.nsIIOService);
  let resProt = ioSvc.getProtocolHandler("resource").
                QueryInterface(Components.interfaces.nsIResProtocolHandler);
  if (!resProt.hasSubstitution("personas")) {
    let extMgr = Components.classes["@mozilla.org/extensions/manager;1"].
                 getService(Components.interfaces.nsIExtensionManager);
    let loc = extMgr.getInstallLocation(PERSONAS_EXTENSION_ID);
    let extD = loc.getItemLocation(PERSONAS_EXTENSION_ID);
    resProt.setSubstitution("personas", ioSvc.newFileURI(extD));
  }

  Components.utils.import("resource://personas/chrome/content/modules/PrefCache.js");
}
else {
  let subscriptLoader = Components.classes["@mozilla.org/moz/jssubscript-loader;1"].
                        getService(Components.interfaces.mozIJSSubScriptLoader);
  subscriptLoader.loadSubScript("resource://personas/chrome/content/modules/PrefCache.js");
}
