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

class content_blog_category {

	/**
	* @param database A database connector object
	* @param integer The unique id of the category to edit (0 if new)
	*/
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

		if ($uid) {
			$menu->checkout( $my->id );
			// get previously selected Categories
			$params =& new mosParameters( $menu->params );
			$catids 				= $params->def( 'categoryid', '' );
			if ( $catids ) {
				$query = "SELECT c.id AS `value`, c.section AS `id`, CONCAT_WS( ' / ', s.title, c.title) AS `text`"
				. "\n FROM #__sections AS s"
				. "\n INNER JOIN #__categories AS c ON c.section = s.id"
				. "\n WHERE s.scope = 'content'"
				. "\n AND c.id IN ( ". $catids . ")"
				. "\n ORDER BY s.name,c.name"
				;
				$database->setQuery( $query );
				$lookup = $database->loadObjectList();
			} else {
				$lookup 			= '';
			}
		} else {
			$menu->type 			= 'content_blog_category';
			$menu->menutype 		= $menutype;
			$menu->ordering 		= 9999;
			$menu->parent 			= intval( mosGetParam( $_POST, 'parent', 0 ) );
			$menu->published 		= 1;
			$lookup 				= '';
		}

		// build the html select list for category
		$rows[] = mosHTML::makeOption( '', 'All Categories' );
		$query = "SELECT c.id AS `value`, c.section AS `id`, CONCAT_WS( ' / ', s.title, c.title) AS `text`"
		. "\n FROM #__sections AS s"
		. "\n INNER JOIN #__categories AS c ON c.section = s.id"
		. "\n WHERE s.scope = 'content'"
		. "\n ORDER BY s.name,c.name"
		;
		$database->setQuery( $query );
		$rows = array_merge( $rows, $database->loadObjectList() );
		$category = mosHTML::selectList( $rows, 'catid[]', 'class="inputbox" size="10" multiple="multiple"', 'value', 'text', $lookup );
		$lists['categoryid']	= $category;

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

		/* chipjack: passing $sectCatList (categories) instead of $slist (sections) */
		content_blog_category_html::edit( $menu, $lists, $params, $option );
	}

	function saveMenu( $option, $task ) {
		global $database;

		$params = mosGetParam( $_POST, 'params', '' );
		$catids	= mosGetParam( $_POST, 'catid', array() );
		$catid	= implode( ',', $catids );
		
		$params[categoryid]	= $catid;
		if (is_array( $params )) {
		    $txt = array();
		    foreach ($params as $k=>$v) {
			   $txt[] = "$k=$v";
			}
			$_POST['params'] = mosParameters::textareaHandling( $txt );
		}

		$row = new mosMenu( $database );

		if (!$row->bind( $_POST )) {
			echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
			exit();
		}
		
		if (count($catids)==1 && $catids[0]!="") {
			$row->link = str_replace("id=0","id=".$catids[0],$row->link);
			$row->componentid = $catids[0];
		}
		
		if (!$row->check()) {
			echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
			exit();
		}
		if (!$row->store()) {
			echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>\n";
			exit();
		}
		$row->checkin();
		$row->updateOrder( "menutype='$row->menutype' AND parent='$row->parent'" );
		
		$msg = 'Menu item Saved';
		switch ( $task ) {
			case 'apply':
				mosRedirect( 'index2.php?option='. $option .'&menutype='. $row->menutype .'&task=edit&id='. $row->id, $msg );
				break;
		
			case 'save':
			default:
				mosRedirect( 'index2.php?option='. $option .'&menutype='. $row->menutype, $msg );
			break;
		}
	}

}
?>
