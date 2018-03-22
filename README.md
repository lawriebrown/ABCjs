# ABCjs - Mediawiki extension to support abc music rendering using abcjs

Provide support for rendering abc music notation on mediawiki pages using
the abcjs javascipt package, and for setting tune related categories
when editing pages.

For more information on abcjs see: http://abcjs.net/

To use this extension, unzip the extension code from ABCjs.zip 
in your wiki extensions dir, creating directory extensions/ABCjs

Then download the latest abcjs plugin & editor javascript modules
from http://abcjs.net/ into this extension dir, and change the
relevant paths in extension.json and sandbox.html.

To enable this extension, include in your LocalSettings.php file:
wfLoadExtension('ABCjs');

You can then redefine any of the following globals as needed:
+ $wgABCjs_plugin - name of abcjs plugin file (in this extension dir) to use
+ $wgABCjs_abcTag - string marking usage of abc notation in wiki page
+ $wgABCjs_abcTagEnd - string marking end of abc notation in wiki page
+ $wgABCjs_abcCat - Category tag used to indicate abc notation used on page
      after which additional key, meter & rhythm category tags are added
+ $wgABCjs_abcHide - Namespace where abc notation hidden, just score shows
+ $wgABCjs_abcMax - max count of abc tunes to render when notation hidden
+ $wgABCjs_debug - set true for debug diagnostic display when running
+ $wgABCjs_UserDebug - set true for abcjs debug diagnostic on User pages

@license Creative Commons Attribution-NonCommercial-ShareAlike
Copyright 2018 Lawrie Brown <Lawrie.Brown@canb.auug.org.au>

