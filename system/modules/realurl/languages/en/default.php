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
 * Error messages
 */
$GLOBALS['TL_LANG']['ERR']['realurl']                   = 'The keyword "%s" cannot be in your alias because an extension use it.<br />Disallowed keywords: %s';
$GLOBALS['TL_LANG']['ERR']['aliasExistsFolder']         = 'The alias "%s" already exists! (the parent alias was automatically added)';
$GLOBALS['TL_LANG']['ERR']['noRootPageFound']           = 'There was no suitable website root found.';
$GLOBALS['TL_LANG']['ERR']['noPageFound']               = 'There was no suitable site found.';
$GLOBALS['TL_LANG']['ERR']['realUrlKeywords']           = 'The alias includes a reserved word for keywords (%1$s).';
$GLOBALS['TL_LANG']['ERR']['realUrlKeywordsExt']        = 'The alias of the page <strong><a href="%s">%s (ID: %s)</a></strong> contains a reserved word for keywords. Keyword: %s';
$GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']     = 'The complete alias can not be empty.';