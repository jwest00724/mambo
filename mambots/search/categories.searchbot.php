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

$_MAMBOTS->registerFunction( 'onSearch', 'botSearchCategories' );

/**
* Categories Search method
*
* The sql must return the following fields that are used in a common display
* routine: href, title, section, created, text, browsernav
* @param string Target search string
* @param string mathcing option, exact|any|all
* @param string ordering option, newest|oldest|popular|alpha|category
*/
function botSearchCategories( $text, $phrase='', $ordering='' ) {
	global $database, $my;

	$text = trim( $text );
	if ( $text == '' ) {
		return array();
	}

	switch ( $ordering ) {
		case 'alpha':
			$order = 'a.name ASC';
			break;
			
		case 'category':
		case 'popular':
		case 'newest':
		case 'oldest':
		default:
			$order = 'a.name DESC';
	}
	
	$query = "SELECT a.name AS title,"
	. "\n a.description AS text,"
	. "\n '' AS created,"
	. "\n '2' AS browsernav,"
	. "\n s.id AS secid, a.id AS catid,"
	. "\n m.id AS menuid, m.type AS menutype"
	. "\n FROM #__categories AS a"
	. "\n INNER JOIN #__sections AS s ON s.id = a.section"
	. "\n LEFT JOIN #__menu AS m ON m.componentid = a.id"
	. "\n WHERE ( a.name LIKE '%$text%'"
	. "\n OR a.title LIKE '%$text%'"
	. "\n OR a.description LIKE '%$text%' )"
	. "\n AND a.published = '1'"
	. "\n AND a.access <= '$my->gid'"
	. "\n AND ( m.type = 'content_section' OR m.type = 'content_blog_section'"
	. "\n OR m.type = 'content_category' OR m.type = 'content_blog_category')"
	. "\n ORDER BY $order"
	;
	$database->setQuery( $query );
	$rows = $database->loadObjectList();

	$count = count( $rows );
	for ( $i = 0; $i < $count; $i++ ) {
		if ( $rows[$i]->menutype == 'content_category' ) {
			$rows[$i]->href = 'index.php?option=com_content&task=category&sectionid='. $rows[$i]->secid .'&id='. $rows[$i]->catid .'&Itemid='. $rows[$i]->menuid;
			$rows[$i]->section 	= T_('Category List');
		}
		if ( $rows[$i]->menutype == 'content_blog_category' ) {
			$rows[$i]->href = 'index.php?option=com_content&task=blogcategory&id='. $rows[$i]->catid .'&Itemid='. $rows[$i]->menuid;
			$rows[$i]->section 	= T_('Category Blog');
		}
	}

	return $rows;
}
?>