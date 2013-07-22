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
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = preg_replace('@([,|;]allowedTags)([,|;])@', '$1,urlKeywords$2', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['urlKeywords'] = array
(
	'label'           => &$GLOBALS['TL_LANG']['tl_settings']['urlKeywords'],
	'inputType'       => 'text',
	'eval'            => array('tl_class'=>'long'),
);