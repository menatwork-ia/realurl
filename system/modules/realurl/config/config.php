<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

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
 * Functions
 */
$GLOBALS['BE_MOD']['design']['page']['realurl_showAlias'] = array('RealUrl', 'keyAlias');

/**
 * CSS/JS files
 */
$objInput = Input::getInstance();
if (TL_MODE == 'BE' && $objInput->get('do') == 'page')
{
    $GLOBALS['TL_CSS'][] = 'system/modules/realurl/html/realurl.css';
}
