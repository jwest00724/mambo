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

global $mosConfig_absolute_path, $mosConfig_live_site;

$type 	= $params->get( 'type', 'jpg' );
$folder = $params->get( 'folder' );
$link 	= $params->get( 'link' );
$width 	= $params->get( 'width' );
$height = $params->get( 'height' );
$moduleclass_sfx = $params->get( 'moduleclass_sfx' );

$abspath_folder = $mosConfig_absolute_path .'/'. $folder;
$the_array = array();
$the_image = array();

if (is_dir($abspath_folder)) {
	if ($handle = opendir($abspath_folder)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..' && $file != 'CVS' && $file != 'index.html' ) {
				$the_array[] = $file;
			}
		}
	}
	closedir($handle);

	foreach ($the_array as $img) {
		if (!is_dir($abspath_folder .'/'. $img)) {
			if (eregi($type, $img)) {
				$the_image[] = $img;
			}
		}
	}

	if (!$the_image) {
		echo 'No images';
	} else {

  	$i = count($the_image);
  	$random = mt_rand(0, $i - 1);
  	$image_name = $the_image[$random];

  	$i = $abspath_folder . '/'. $image_name;
  	$size = getimagesize ($i);

  	if ($width == '') {
  		$width = 100;
  	}
  	if ($height == '') {
  		$coeff = $size[0]/$size[1];
  		$height = (int) ($width/$coeff);
  	}

  	$image = $mosConfig_live_site .'/'. $folder .'/'. $image_name;
 	
   	if ($link) {
  		echo "<a href=\"" . $link . "\" target=\"_self\">\n";
  	}

	}
  	?>
 	<div align="center"> 	
 	<?php 
  	if ($link) {
  		?>
  		<a href="<?php echo $link; ?>" target="_self">
  		<?php
  	}
  	?>
 	<img src="<?php echo $image; ?>" border="0" width="<?php echo $width; ?>" height="<?php echo $height; ?>" alt="<?php echo $image_name; ?>" /><br />
 	<?php 
  	if ($link) {
  		?>
  		</a>
  		<?php
  	}
  	?>
 	</div>
  	<?php
}
?>
