<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  Andreas Schempp 2008-2011
 * @copyright  MEN AT WORK 2012-2013 
 * @package    realurl
 * @license    GNU/LGPL 
 * @filesource
 */
  
/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['addCustomRegexp'][]       = array('RealUrl', 'validateRegexp');
$GLOBALS['TL_HOOKS']['getPageIdFromUrl'][]      = array('RealUrl', 'findAlias');

/**
 * Functions
 */
$GLOBALS['BE_MOD']['design']['page']['realurl_showAlias'] = array('RealUrl', 'keyAlias');
$GLOBALS['BE_MOD']['design']['page']['realurl_regenerate'] = array('RealUrl', 'keyRegenerate');

/**
 * URL Keywords
 */
$GLOBALS['URL_KEYWORDS'][] = 'items';
$GLOBALS['URL_KEYWORDS'][] = 'articles';
$GLOBALS['URL_KEYWORDS'][] = 'events';
$GLOBALS['URL_KEYWORDS'][] = 'page';
$GLOBALS['URL_KEYWORDS']   = array_unique(array_merge($GLOBALS['URL_KEYWORDS'], trimsplit(',', $GLOBALS['TL_CONFIG']['urlKeywords'])));

/**
 * CSS/JS files
 */
$objInput = Input::getInstance();
if (TL_MODE == 'BE' && $objInput->get('do') == 'page')
{
    $GLOBALS['TL_CSS'][] = 'system/modules/realurl/html/realurl.css';
}