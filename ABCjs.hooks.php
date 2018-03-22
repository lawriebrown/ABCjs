<?php

if ( !defined( 'MEDIAWIKI' ) ) 
     die();

/**
 * Hooks to include and configure the abcjs javascipt package, and to
 * add/update Key, Meter and Rhythm Category tags at the end of the page,
 * for the ABCjs extension
 *
 * @ingroup Extensions
 */

class ABCjsHooks {

    /*
     * Hook edit save to scan the edit text and modify the Key, Meter 
     * and Rhythm Category tags at the end of the page based on their
     * respective abc notation tags.
     * @param $editPage edited page object
     * @return true/false success status
     */
    public static function onEditSave( $editPage ) {
	# access required globals MW & ours
        global $wgUser, $wgContLang;
        global $wgABCjs_abcCat;
        global $wgABCjs_abcTag, $wgABCjs_abcTagEnd, $wgABCjs_debug;

        # Get localised Category namespace string:
        $s_category = $wgContLang->getNsText(NS_CATEGORY);

        # Check for namespace to progress checks in (eg NS_USER or NS_MAIN)
        $nameSpace = $editPage->mTitle->getNamespace();
        if ( ($nameSpace != NS_MAIN) && ($nameSpace != NS_USER) )
            return true;

        # Get title & page contents
        $title = $editPage->mTitle;
        $pageText = $editPage->textbox1;

        # check if page contains marker strings wgABCjs_abcTag & wgABCjs_abcCat
        if ( (strpos($pageText, $wgABCjs_abcTag) === false) &&
             (strpos($pageText, "[[$s_category:$wgABCjs_abcCat]]") === false) )
                 return true;

        # process pageTest line by line searching for wanted abc notation tags
        # and then finding & replacing/adding desired categories
        $inABC = false;
        $inCat = false;
        $keyVal = '';
        $meterVal = '';
        $rhythmVal = '';
        $pageText = explode("\n", $pageText);
        foreach($pageText as $index => $textLine) {
            # find start & end pre format text tags wrapping abc notation
            if (preg_match("~$wgABCjs_abcTag~", $textLine))     $inABC = true;
            else if (preg_match("~$wgABCjs_abcTagEnd~", $textLine))     $inABC = false;
            # look for first of each desired tag in abc notation & get value
            else if ($inABC && ($keyVal == '') &&
                    preg_match('~^K:\s*([A-G][b#]?)\s*([a-z]*)~',
		    $textLine, $matches)) {
                $key1 = $matches[1];
                $key2 = $matches[2];
                $key1 = str_replace('#', '^', $key1);
                $key2 = preg_replace('~maj$~', '', $key2);
                $key2 = preg_replace('~min$~', 'm', $key2);
                $keyVal = $key1 . $key2;
            }
            else if ($inABC && ($meterVal == '') &&
                    preg_match('~^M:\s*(\d+/\d+)~', $textLine, $matches)) {
                $meterVal = $matches[1];
            }
            else if ($inABC && ($meterVal == '') &&
                    preg_match('~^M:\s*(C)\s*~', $textLine, $matches)) {
                $meterVal = '4/4';
            }
            else if ($inABC &&
	            preg_match('~^R:\s*(.+)~', $textLine, $matches)) {
                $rhythmVal .= str_replace(' ', '_', $matches[1]) . '/';
            }
            # having reached category tags at end so add desired tags
            else if (!$inABC &&
                preg_match("~\[\[$s_category:$wgABCjs_abcCat\]\]~", $textLine)) {
                $inCat = true;
                if ($keyVal != '')
                    $textLine .= "\n[[$s_category:Key_" . $keyVal . "]]";
                if ($meterVal != '')
                    $textLine .= "\n[[$s_category:Meter_" . $meterVal . "]]";
                # handle multiple rhythms separated with /
                $rhythmVal = explode("/", $rhythmVal);
                foreach($rhythmVal as $rindex => $rhythmItem) {
                   if ($rhythmItem != '')
                       $textLine .= "\n[[$s_category:Rhythm_" .
                           ucfirst($rhythmItem) . "]]";
                }
                $pageText[$index] = $textLine;
            }
            # having added new category tags, strip out any existing ones
            else if ($inCat && preg_match("~\[\[$s_category:~", $textLine)) {
                unset($pageText[$index]);
            }
        }
        $pageText = implode("\n", $pageText);

        $editPage->textbox1 = trim($pageText);

        # display debug diagnostics if needed
        if ( $wgABCjs_debug ) {
            $dbg = "\nABCjs Debug: ABCjsHooks::onEditSave -- isTune $inCat " .
	    "-- nameSpace $nameSpace -- $title -- Key $keyVal " .
	    "-- Meter $meterVal -- Rhythm " .
            implode("/", $rhythmVal) . "\n";
            $editPage->textbox1 .= $dbg;
        }

        return true;
    }

    /*
     * hook to include abcjs javascript library & settings in page header
     * by hooking page display to include abcjs code to process any
     * abc music notation on page
     *
     * @param $out page output object
     * @param $skin Skin in use
     * @return true/false success status
     */
    public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
	# access required globals MW & ours
        global $wgScriptPath, $wgContLang;
        global $wgABCjs_plugin, $wgABCjs_abcHide;
	global $wgABCjs_abcMax, $wgABCjs_UserDebug;

        # Get localised Special & User namespace strings:
        $s_special = $wgContLang->getNsText(NS_SPECIAL);
        $s_user = $wgContLang->getNsText(NS_USER);

        $title = $out->getPageTitle();
        if (preg_match("/^$s_special:/",$title))
	    return true;    # not Special:

        # construct additional header info to include & configure abcjs plugin
        $script = "<script type=\"text/javascript\" src=\"$wgABCjs_plugin\"></script>";
        $script .= '<script>';
        $script .= 'ABCJS.plugin.render_before = true;';
        # maybe set abcjs debug on for User pages only (set true if wanted)
        if ($wgABCjs_UserDebug && preg_match("/^$s_user:/",$title))
            $script .= 'ABCJS.plugin.debug = true;';
        # hide abc notation if $wgABCjs_abcHide page, else show it
        if (preg_match("/^$wgABCjs_abcHide:/",$title)) {
            $script .= "ABCJS.plugin.auto_render_threshold = $wgABCjs_abcMax;";
            $script .= "ABCJS.plugin.hide_abc = true;";
        } else {
            $script .= "ABCJS.plugin.hide_abc = false;";
        }
        $script .= "</script>";
        $out->addHeadItem("abcjs script", $script);
        return true;
    }

}
