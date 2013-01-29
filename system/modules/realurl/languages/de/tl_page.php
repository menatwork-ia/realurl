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
$GLOBALS['TL_LANG']['tl_page']['realurl_legend']            = 'Alias-Einstellungen';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_page']['folderAlias']		        = array('Ordner-Alias erstellen', 'Seitenaliase automatisch mit übergeordneten Seiten generieren (Ordner-ähnlich).');
$GLOBALS['TL_LANG']['tl_page']['subAlias']		            = array('Alias von Unterseiten aktualisieren', 'Generiert einen Alias für Unterseiten.');
$GLOBALS['TL_LANG']['tl_page']['useRootAlias']              = array('Alias des Startpunkts verwenden', 'Verwendet den Alias des Startpunkts als Basis für alle anderen Seiten.');
$GLOBALS['TL_LANG']['tl_page']['realurl_overwrite']	        = array('Alias überschreiben', 'Klicken Sie hier, um den gesamten Alias zurückzusetzen.');
$GLOBALS['TL_LANG']['tl_page']['realurl_no_inheritance']    = array('Alias nicht an Unterseiten vererben', 'Klicken Sie hier, wenn der Alias dieser Seite nicht an Unterseiten vererbt werden soll.');

/**
 * Miscellaneous
 */
$GLOBALS['TL_LANG']['tl_page']['realurl']['aliasShow']      = array('Alias anzeigen  ', 'Zeigt den vollen Seitenalias hinter dem Seitentitel an.');
$GLOBALS['TL_LANG']['tl_page']['realurl']['aliasHide']      = array('Alias verstecken', 'Versteckt den vollen Seitenalias.');
$GLOBALS['TL_LANG']['tl_page']['realurl']['regenerate']     = array('Alias neu erstellen', 'Erstellt für jeden Teilbaum den Alias neu, so fern realurl aktiviert ist.');