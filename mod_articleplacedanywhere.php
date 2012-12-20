<?php
/**
* @copyright    Copyright (C) 2008 Ian MacLennan. All rights reserved.
* @copyright    Upgrade to J2.5.  Copyright 2012 HartlessByDesign, LLC.
* @copyright	Portions Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

if (!defined('DS')) {
    define("DS", DIRECTORY_SEPARATOR);
}
// Include the syndicate functions only once
require_once (dirname(__FILE__).DS.'helper.php');

$lang = JFactory::getLanguage();
$lang->load('com_content', JPATH_SITE);

$item = modArticlePlacedAnywhereHelper::getItem($params);

//require icon HTML
include_once JPATH_SITE . DS . "components" . DS . "com_content" . DS . "helpers" . DS . "icon.php";

// check if any results returned
if (empty( $item )) {
	return;
}

if ($params->get('load_mootools', 0)) {
	JHTML::_( 'behavior.mootools' );
}

$layout = $params->get('layout', 'default');
$filter = JFilterInput::getInstance();
$layout = $filter->clean($layout, 'word');
$path = JModuleHelper::getLayoutPath('mod_articleplacedanywhere', $layout);
if (file_exists($path)) {
	require($path);
}
