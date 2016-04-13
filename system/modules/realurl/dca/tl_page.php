<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  Andreas Schempp 2008-2011
 * @copyright  MEN AT WORK
 * @package    realurl
 * @license    GNU/LGPL 
 * @filesource
 */

/**
 * callback / core callbacks overwrite
 */
$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback_old'] = $GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'];
$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback']     = array('RealUrl', 'labelPage');

/**
 * Global operations
 */
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['realurl_showAlias'] = array(
    'label'           => &$GLOBALS['TL_LANG']['tl_page']['realurl']['showAlias'],
    'href'            => 'key=realurl_showAlias',
    'class'           => 'relaurl_alias_toggle',
    'button_callback' => array('RealUrl', 'bttShowAlias'),
);

/**
 * Palettes
 */
foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $keyPalette => $valuePalette)
{
    if ($keyPalette != "root" && $keyPalette != '__selector__')
    {
        $GLOBALS['TL_DCA']['tl_page']['palettes'][$keyPalette] = preg_replace('@([,|;]type)([,|;])@', '$1,realurl_no_inheritance$2', $GLOBALS['TL_DCA']['tl_page']['palettes'][$keyPalette]);
    }
}

/**
 * Fields
 */
foreach ($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'] as $i => $arrCallback)
{
    if ($arrCallback[0] == 'tl_page' && $arrCallback[1] == 'generateAlias')
    {
        $GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][$i] = array('RealUrl', 'generateAlias');
        break;
    }
}

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_no_inheritance'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['realurl_no_inheritance'],
    'inputType' => 'checkbox',
    'exclude'   => true,
    'eval'      => array('tl_class'  => 'w50'),
);