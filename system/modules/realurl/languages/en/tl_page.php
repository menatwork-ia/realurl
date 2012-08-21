<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    Language
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
$GLOBALS['TL_LANG']['tl_page']['realurl_no_inheritance']    = array('Don\'t inherit the alias to sub-pages', 'Click here if the site alias is not to be inherit to sub-pages.');