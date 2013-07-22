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
$GLOBALS['TL_LANG']['ERR']['realurl']                   = 'Das Wort "%s" kann nicht im Alias verwendet werden, weil es von einer Erweiterung reserviert ist.<br />Gesperrte Schlüsselwörter: %s';
$GLOBALS['TL_LANG']['ERR']['aliasExistsFolder']         = 'Der Alias "%s" existiert bereits! (Der übergeordnete Alias wurde automatisch hinzugefügt)';
$GLOBALS['TL_LANG']['ERR']['noRootPageFound']           = 'Es konnte kein passender Startpunkt gefunden werden.';
$GLOBALS['TL_LANG']['ERR']['noPageFound']               = 'Es konnte keine passende Seite gefunden werden.';
$GLOBALS['TL_LANG']['ERR']['realUrlKeywords']           = 'Der Alias beinhaltet ein für Keywords reserviertes Wort (%1$s).';
$GLOBALS['TL_LANG']['ERR']['realUrlKeywordsExt']        = 'Der Alias der Seite <strong><a href="%s">%s (ID: %s)</a></strong> beinhaltet ein für Keywords reserviertes Wort. Keyword: %s';
$GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']     = 'Der komplette Alias darf nicht leer sein.';