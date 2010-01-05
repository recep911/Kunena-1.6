<?php
/**
* @version $Id$
* Kunena Component
* @package Kunena
*
* @Copyright (C) 2008 - 2009 Kunena Team All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.kunena.com
*
* Based on FireBoard Component
* @Copyright (C) 2006 - 2007 Best Of Joomla All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.bestofjoomla.com
*
* Based on Joomlaboard Component
* @copyright (C) 2000 - 2004 TSMF / Jan de Graaff / All Rights Reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author TSMF & Jan de Graaff
**/

// Dont allow direct linking
defined( '_JEXEC' ) or die('Restricted access');

require_once(KUNENA_PATH_LIB .DS. 'kunena.file.class.php');

$kunena_config =& CKunenaConfig::getInstance();
$attachimage = JRequest::getVar('attachimage', NULL, 'FILES', 'array');

global $message, $imageLocation;

function imageUploadError($msg)
{
    global $message;
    $GLOBALS['KUNENA_rc'] = 0;
    $message = str_replace("[img/]", "", $message);
    $kunena_app =& JFactory::getApplication();
    $kunena_app->enqueueMessage(_IMAGE_NOT_UPLOADED .' ('. $msg .')', 'notice');
}

$GLOBALS['KUNENA_rc'] = 1; //reset return code
$filename = CKunenaFile::makeSafe($attachimage['name']);
$filearray = explode('.', $filename);
if (count($filearray)>1) $imageExt = '.' . array_pop($filearray);
else $imageExt = '';
$imageName = implode('.', $filearray);
// Get the Filesize
$imageSize = $attachimage['size'];

//Enforce it is a new file
$newFileName = $imageName . $imageExt;
if (file_exists(KUNENA_PATH_UPLOADED .DS. "images" .DS. $newFileName)) {
    $newFileName = $imageName . '-' . date('Ymd') . $imageExt;
    for ($i=2; file_exists(KUNENA_PATH_UPLOADED .DS. "images" .DS. $newFileName); $i++) {
    	$newFileName = $imageName . '-' . date('Ymd') . "-$i" . $imageExt;
    }
}

if ($GLOBALS['KUNENA_rc'])
{
    //Filename + proper path
    $imageLocation = strtr(KUNENA_PATH_UPLOADED .DS. "images" .DS. $newFileName, "\\", "/");
    $maxImgSize = $kunena_config->imagesize * 1024;

    // Check for empty filename
    if (!is_uploaded_file($attachimage['tmp_name']) || empty($attachimage['name'])) {
        imageUploadError(_IMAGE_ERROR_EMPTY);
    }
    // Check for allowed file type (jpeg, gif, png)
    else if (!($imgtype = KUNENA_check_image_type(substr($imageExt,1)))) {
        imageUploadError(_IMAGE_ERROR_TYPE);
    }
    // Check filesize
    else if ($imageSize > $maxImgSize) {
        imageUploadError(_IMAGE_ERROR_SIZE . " (" . $kunena_config->imagesize . "kb)");
    }
	else {
    list($width, $height) = @getimagesize($attachimage['tmp_name']);

    // Check image width
    if ($width > $kunena_config->imagewidth) {
        imageUploadError(_IMAGE_ERROR_WIDTH . " (" . $kunena_config->imagewidth . " pixels");
    }
    // Check image height
    else if ($height > $kunena_config->imageheight) {
        imageUploadError(_IMAGE_ERROR_HEIGHT . " (" . $kunena_config->imageheight . " pixels");
    }
	}
}

if ($GLOBALS['KUNENA_rc'])
{
	// file is OK, move it to the proper location
	CKunenaFile::upload($attachimage['tmp_name'], $imageLocation);

	// echo '<span class="contentheading">'._IMAGE_UPLOADED."...</span>";
    $code = '[img]' . KUNENA_LIVEUPLOADEDPATH. '/images/' . $newFileName . '[/img]';

    if (preg_match('/\[img\/\]/si', $message)) {
        $message = str_replace("[img/]", $code, $message);
    }
    else {
        $message = $message . ' ' . $code;
    }
    echo _KUNENA_ATTACHMENT_IMG_ADDED, ' '.$newFileName;
}
?>
