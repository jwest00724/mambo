<?php
/**
* @package Mambo
* @subpackage Menus
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

/**
* Newsfeed item link class
*/
class newsfeed_link_menu {

	function edit( &$uid, $menutype, $option ) {
		global $database, $my, $mainframe;
		global $mosConfig_absolute_path;
	
		$menu = new mosMenu( $database );
		$menu->load( $uid );
	
		// fail if checked out not by 'me'
		if ($menu->checked_out && $menu->checked_out <> $my->id) {
			echo "<script>alert('".sprintf(T_('The module % is currently being edited by another administrator'), $menu->title)."'); document.location.href='index2.php?option=$option'</script>\n";
			exit(0);
		}
	
		if ( $uid ) {
			$menu->checkout( $my->id );
		} else {
			// load values for new entry
			$menu->type = 'newsfeed_link';
			$menu->menutype = $menutype;
			$menu->browserNav = 0;
			$menu->ordering = 9999;
			$menu->parent = intval( mosGetParam( $_POST, 'parent', 0 ) );
			$menu->published = 1;
		}
	
		if ( $uid ) {
			$temp = explode( 'feedid=', $menu->link );
			$query = "SELECT *, c.title AS category"
			. "\n FROM #__newsfeeds AS a"
			. "\n INNER JOIN #__categories AS c ON a.catid = c.id"
			. "\n WHERE a.id = '". $temp[1] ."'"
			;
			$database->setQuery( $query );
			$newsfeed = $database->loadObjectlist();
			// outputs item name, category & section instead of the select list
			$lists['newsfeed'] = '
			<table width="100%">
			<tr>
				<td width="10%">
				'.T_('Item:').'
				</td>
				<td>
				'. $newsfeed[0]->name .'
				</td>
			</tr>
			<tr>
				<td width="10%">
				'.T_('Position:').'
				</td>
				<td>
				'. $newsfeed[0]->category .'
				</td>
			</tr>
			</table>';
			$lists['newsfeed'] .= '<input type="hidden" name="newsfeed_link" value="'. $temp[1] .'" />';
			$newsfeeds = '';
		} else {
			$query = "SELECT a.id AS value, CONCAT( c.title, ' - ', a.name ) AS text, a.catid "
			. "\n FROM #__newsfeeds AS a"
			. "\n INNER JOIN #__categories AS c ON a.catid = c.id"
			. "\n WHERE a.published = '1'"
			. "\n ORDER BY a.catid, a.name"
			;
			$database->setQuery( $query );
			$newsfeeds = $database->loadObjectList( );
	
			//	Create a list of links
			$lists['newsfeed'] = mosHTML::selectList( $newsfeeds, 'newsfeed_link', 'class="inputbox" size="10"', 'value', 'text', '' );
		}
		
		// build html select list for target window
		$lists['target'] 		= mosAdminMenus::Target( $menu );
	
		// build the html select list for ordering
		$lists['ordering'] 		= mosAdminMenus::Ordering( $menu, $uid );
		// build the html select list for the group access
		$lists['access'] 		= mosAdminMenus::Access( $menu );
		// build the html select list for paraent item
		$lists['parent'] 		= mosAdminMenus::Parent( $menu );
		// build published button option
		$lists['published'] 	= mosAdminMenus::Published( $menu );
		// build the url link output
		$lists['link'] 		= mosAdminMenus::Link( $menu, $uid );
		
		// get params definitions
		$params =& new mosAdminParameters( $menu->params, $mainframe->getPath( 'menu_xml', $menu->type ), 'menu' );
	
		newsfeed_link_menu_html::edit( $menu, $lists, $params, $option, $newsfeeds );
	}
}
?>
