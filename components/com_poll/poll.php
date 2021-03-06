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

require_once( $mainframe->getPath( 'front_html' ) );
require_once( $mainframe->getPath( 'class' ) );

$tabclass 			= 'sectiontableentry2,sectiontableentry1';
$polls_graphwidth 	= 200;
$polls_barheight 	= 2;
$polls_maxcolors 	= 5;
$polls_barcolor 	= 0;

$poll = new mosPoll( $database );

$id 	= intval( mosGetParam( $_REQUEST, 'id', 0 ) );
$task 	= mosGetParam( $_REQUEST, 'task', '' );

switch ($task) {

	case 'vote':
		pollAddVote( $id );
		break;

	default:
		pollresult( $id );
		break;
}

function pollAddVote( $uid ) {
	global $database, $mosConfig_offset, $Itemid;

	$redirect = 1;

	$sessionCookieName = md5( 'site'.$GLOBALS['mosConfig_live_site'] );
	$sessioncookie = mosGetParam( $_REQUEST, $sessionCookieName, '' );

	if (!$sessioncookie) {
		echo '<h3>'. T_('Cookies must be enabled!') .'"</h3>';
		echo '<input class="button" type="button" value="'. T_('Continue') .'" onClick="window.history.go(-1);">';
		return;
	}

	$poll = new mosPoll( $database );
	if (!$poll->load( $uid )) {
		echo '<h3>'. T_('You are not authorized to view this resource.') .'</h3>';
		echo '<input class="button" type="button" value="'. T_('Continue') .'" onClick="window.history.go(-1);">';
		return;
	}

	$cookiename = "voted$poll->id";
	$voted = mosGetParam( $_COOKIE, $cookiename, '0' );

	if ($voted) {
		echo "<h3>".T_('You already voted for this poll today!')."</h3>";
		echo "<input class=\"button\" type=\"button\" value=\"".T_('Continue')."\" onClick=\"window.history.go(-1);\">";
		return;
	}

	$voteid = mosGetParam( $_POST, 'voteid', 0 );
	if (!$voteid) {
		echo "<h3>".T_('No selection has been made, please try again')."</h3>";
		echo '<input class="button" type="button" value="'. T_('Continue') .'" onClick="window.history.go(-1);">';
		return;
	}

	setcookie( $cookiename, '1', time()+$poll->lag );

	$database->setQuery( "UPDATE #__poll_data SET hits=hits + 1"
		."\n WHERE pollid='$poll->id' AND id='$voteid'");

	$database->query();

	$database->setQuery( "UPDATE #__polls SET voters=voters + 1"
		."\n WHERE id='$poll->id'");

	$database->query();

	$now = date("Y-m-d G:i:s");
	$database->setQuery( "INSERT INTO #__poll_date SET date='$now', vote_id='$voteid',	poll_id='$poll->id'");

	$database->query();
	if ( $redirect ) {
		mosRedirect( sefRelToAbs( 'index.php?option=com_poll&task=results&id='. $uid ), T_('Thanks for your vote!'));
	} else {
		echo '<h3>'. T_('Thanks for your vote!') .'</h3>';
		echo '<form action="" method="GET">';
		echo '<input class="button" type="button" value="'. T_('Results') .'" onClick="window.location=\''. sefRelToAbs( 'index.php?option=com_poll&task=results&id='. $uid ) .'\'">';
		echo '</form>';
	}
}


function pollresult( $uid ) {
	global $database, $mosConfig_offset, $mosConfig_live_site, $Itemid;
	global $mainframe;

	$poll = new mosPoll( $database );
	$poll->load( $uid );

	if (empty($poll->title)) {
		$poll->id = '';
		$poll->title = T_('Select Poll from the list');
	}

	$first_vote = '';
	$last_vote = '';

	if (isset($poll->id) && $poll->id != "") {
		$query = "SELECT MIN(date) AS mindate, MAX(date) AS maxdate"
		."\n FROM #__poll_date"
		."\n WHERE poll_id='$poll->id'"
		;
		$database->setQuery( $query );

		$dates = $database->loadObjectList();

		if (isset($dates[0]->mindate)) {
			$first_vote = mosFormatDate( $dates[0]->mindate, _DATE_FORMAT_LC2 );
			$last_vote = mosFormatDate( $dates[0]->maxdate, _DATE_FORMAT_LC2 );
		}
	}

	$query = "SELECT a.id, a.text, count( DISTINCT b.id ) AS hits, count( DISTINCT b.id )/COUNT( DISTINCT a.id )*100.0 AS percent"
	. "\n FROM #__poll_data AS a"
	. "\n LEFT JOIN #__poll_date AS b ON b.vote_id = a.id"
	. "\n WHERE a.pollid='$poll->id' AND a.text <> ''"
	. "\n GROUP BY a.id"
	. "\n ORDER BY a.id"
	;
	$database->setQuery( $query );
	$votes = $database->loadObjectList();

	$query = "SELECT id, title"
	. "\n FROM #__polls"
	. "\n WHERE published=1"
	. "\n ORDER BY id"
	;
	$database->setQuery( $query );
	$polls = $database->loadObjectList();

	reset( $polls );
	$link = sefRelToAbs( 'index.php?option=com_poll&amp;task=results&amp;id=\' + this.options[selectedIndex].value + \'&amp;Itemid='. $Itemid .'\' + \'' );
	$pollist = '<select name="id" class="inputbox" size="1" style="width:200px" onchange="if (this.options[selectedIndex].value != \'\') {document.location.href=\''. $link .'\'}">';
	$pollist .= '<option value="">'. T_('Select Poll from the list') .'</option>';
	for ($i=0, $n=count( $polls ); $i < $n; $i++ ) {
		$k = $polls[$i]->id;
		$t = $polls[$i]->title;

		$sel = ($k == intval( $poll->id ) ? " selected=\"selected\"" : '');
		$pollist .= "\n\t<option value=\"".$k."\"$sel>" . $t . "</option>";
	}
	$pollist .= '</select>';

	// Adds parameter handling
	$menu =& new mosMenu( $database );
	$menu->load( $Itemid );

	$params =& new mosParameters( $menu->params );
	$params->def( 'page_title', 1 );
	$params->def( 'pageclass_sfx', '' );
	$params->def( 'back_button', $mainframe->getCfg( 'back_button' ) );
	$params->def( 'header', $menu->name );

	$mainframe->SetPageTitle($poll->title);

	poll_html::showResults( $poll, $votes, $first_vote, $last_vote, $pollist, $params );
}
?>
