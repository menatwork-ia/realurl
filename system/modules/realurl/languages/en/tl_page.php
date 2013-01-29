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
 * Legends
 */
$GLOBALS['TL_LANG']['tl_page']['realurl_legend']            = 'Alias settings';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_page']['folderAlias']		        = array('Generate folder alias', 'Check here if you want to generate page alias including parent page alias (folder-like).');
$GLOBALS['TL_LANG']['tl_page']['subAlias']		            = array('Generate alias for subpages', 'Generate an alias for all subpages.');
$GLOBALS['TL_LANG']['tl_page']['useRootAlias']              = array('Use the website root alias', 'Used the alias of the website root as the basis for all other pages.');
$GLOBALS['TL_LANG']['tl_page']['realurl_overwrite']	        = array('Overwrite page alias', 'Click here to reset the entire alias.');
$GLOBALS['TL_LANG']['tl_page']['realurl_no_inheritance']    = array('Do not inherit page alias to subpages', 'Click here if the page alias is not to be inherit to subpages.');

/**
 * Miscellaneous
 */
$GLOBALS['TL_LANG']['tl_page']['realurl']['aliasShow']      = array('Show alias', 'Show all aliases.');
$GLOBALS['TL_LANG']['tl_page']['realurl']['aliasHide']      = array('Hide alias', 'Hide all aliases.');
$GLOBALS['TL_LANG']['tl_page']['realurl']['regenerate']     = array('Recreate alias', 'Recreate all aliases.');