<?php
/**
* @package Mambo
* @author Mambo Foundation Inc see README.php
* @copyright Mambo Foundation Inc.
* See COPYRIGHT.php for copyright notices and details.
* @license GNU/GPL Version 2, see LICENSE.php
* Mambo is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; version 2 of the License.
*/

/** ensure this file is being included by a parent file */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

$_MAMBOTS->registerFunction( 'onPrepareContent', 'botMosCode' );

/**
* Code Highlighting Mambot
*
* <strong>Usage:</strong>
* <code>{moscode}...some code...{/moscode}</code>
*/
function botMosCode( $published, &$row, &$params, $page=0 ) {
	// define the regular expression for the bot
	$regex = "#{moscode}(.*?){/moscode}#s";

	if (is_callable(array($row, 'getText'))) $localtext = $row->getText();
	else $localtext = $row->text;
	if (!$published) {
		$localtext = preg_replace( $regex, '', $localtext );
		if (is_callable(array($row, 'saveText'))) $row->saveText($localtext);
		else $row->text = $localtext;
		return;
	}


	// perform the replacement
	$localtext = preg_replace_callback( $regex, 'botMosCode_replacer', $localtext );
	if (is_callable(array($row, 'saveText'))) $row->saveText($localtext);
	else $row->text = $localtext;

	return true;
}
/**
* Replaces the matched tags an image
* @param array An array of matches (see preg_match_all)
* @return string
*/
function botMosCode_replacer( &$matches ) {
	$html_entities_match = array("#<#", "#>#");
	$html_entities_replace = array("&lt;", "&gt;");

	$text = $matches[1];

	$text = preg_replace($html_entities_match, $html_entities_replace, $text );

	// Replace 2 spaces with "&nbsp; " so non-tabbed code indents without making huge long lines.
	$text = str_replace("  ", "&nbsp; ", $text);
	// now Replace 2 spaces with " &nbsp;" to catch odd #s of spaces.
	$text = str_replace("  ", " &nbsp;", $text);

	// Replace tabs with "&nbsp; &nbsp;" so tabbed code indents sorta right without making huge long lines.
	$text = str_replace("\t", "&nbsp; &nbsp;", $text);

	$text = str_replace('&lt;', '<', $text);
	$text = str_replace('&gt;', '>', $text);

	$text = highlight_string( $text, 1 );

	$text = str_replace('&amp;nbsp;', '&nbsp;', $text);
	$text = str_replace('&lt;br/&gt;', '<br />', $text);
	$text = str_replace('<font color="#007700">&lt;</font><font color="#0000BB">br</font><font color="#007700">/&gt;','<br />', $text);
	$text = str_replace('&amp;</font><font color="#0000CC">nbsp</font><font color="#006600">;', '&nbsp;', $text);
	$text = str_replace('&amp;</font><font color="#0000BB">nbsp</font><font color="#007700">;', '&nbsp;', $text);
	$text = str_replace('<font color="#007700">;&lt;</font><font color="#0000BB">br</font><font color="#007700">/&gt;','<br />', $text);

	return $text;
}
?>
