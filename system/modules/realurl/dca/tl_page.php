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
 * callback / core callbacks overwrite
 */

// Labels
$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'] = array('RealUrl', 'labelPage');
$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = array('RealUrl', 'createAliasList');

$arrConfig = &$GLOBALS['TL_DCA']['tl_page']['config'];
foreach (array('onrestore', 'oncopy', 'oncut') as $strCallback)
{
    $strKey             = $strCallback . '_callback';
    $arrConfig[$strKey] = (array) $arrConfig[$strKey];
    array_unshift($arrConfig[$strKey], array('RealUrl', $strCallback . 'Page'));
}

// Save
foreach ($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'] as $i => $arrCallback)
{
    if ($arrCallback[1] == 'generateAlias')
    {
        $GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][$i] = array('tl_page_realurl', 'generateFolderAlias');
        break;
    }
}

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave'] = true;

/**
 * Global operations
 */
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['realurl_showAlias'] = array(
    'label'           => &$GLOBALS['TL_LANG']['tl_page']['realurl']['showAlias'],
    'href'            => 'key=realurl_showAlias',
    'class'           => 'relaurl_alias_toggle',
    'button_callback' => array('RealUrl', 'bttShowAlias'),
);

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['realurl_Regenerate'] = array(
    'label'           => &$GLOBALS['TL_LANG']['tl_page']['realurl']['regenerate'],
    'href'            => 'key=realurl_regenerate',
    'class'           => 'relaurl_regenerate',
    'button_callback' => array('RealUrl', 'bttRegenerate'),
);

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] .= ';{realurl_legend},folderAlias,subAlias,useRootAlias';
$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][]       = 'realurl_overwrite';
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['realurl_overwrite'] = 'realurl_basealias';

foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $keyPalette => $valuePalette)
{
    if ($keyPalette != "root" && $keyPalette != '__selector__')
    {
        $GLOBALS['TL_DCA']['tl_page']['palettes'][$keyPalette] = preg_replace('@([,|;]type)([,|;])@', '$1,realurl_no_inheritance,realurl_overwrite$2', $GLOBALS['TL_DCA']['tl_page']['palettes'][$keyPalette]);
    }
}

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']       = 'folderurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave'] = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['load_callback'][]    = array('tl_page_realurl', 'hideParentAlias');

$GLOBALS['TL_DCA']['tl_page']['fields']['folderAlias'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['folderAlias'],
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['subAlias'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['subAlias'],
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['useRootAlias'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['useRootAlias'],
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_no_inheritance'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['realurl_no_inheritance'],
    'inputType' => 'checkbox',
    'eval'      => array(
        'tl_class'  => 'w50',
        'doNotCopy' => true
    ),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_overwrite'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['realurl_overwrite'],
    'inputType' => 'checkbox',
    'eval'      => array(
        'submitOnChange' => true,
        'tl_class'       => 'w50',
        'doNotCopy'      => true
    ),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_basealias'] = array(
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['alias'],
    'inputType'     => 'text',
    'load_callback' => array(array('tl_page_realurl', 'loadFullAlias')),
    'eval' => array(
        'spaceToUnderscore' => true,
        'trailingSlash'     => true,
        'doNotCopy'         => true,
        'tl_class'          => 'clr long'
    )
);

/**
 * Helper/Callback class
 */
class tl_page_realurl extends tl_page
{

    // Core --------------------------------------------------------------------

    protected $objRealUrl;

    public function __construct()
    {
        parent::__construct();

        $this->objRealUrl = new RealUrl();
    }

    // Alias -------------------------------------------------------------------

    /**
     * Load the current full alias
     * 
     * @param type $varValue
     * @param type $dc
     * @return type 
     */
    public function loadFullAlias($varValue, $dc)
    {
        // Load current page alias
        $objPage = $this->getPageDetails($dc->id);
        return $objPage->alias;
    }

    /**
     * Replaces the default contao core function to auto-generate a page alias if it has not been set yet.
     * 
     * @param	mixed
     * @param	DataContainer
     * @return	mixed
     * @link	http://www.contao.org/callbacks.html#save_callback
     * @version 2.0
     */
    public function generateFolderAlias($varValue, $dc)
    {           
        // If empty recreate the alias
        if (empty($varValue))
        {
            $varValue = parent::generateAlias($varValue, $dc);
        }

        // Generate folder alias
        $varValue = $this->objRealUrl->generateFolderAlias($dc->id, $varValue, true);

        // Check if realurl is enabled or better say, fallback
        if ($varValue == '' || $varValue == false)
        {
            return parent::generateAlias($varValue, $dc);
        }

        $objPage = $this->getPageDetails($dc->id);

        // If root set some information, cause the database is not up to date -.-
        if ($objPage->type == 'root')
        {
            $this->objRealUrl->addRootMapper($dc->id, $this->Input->post('useRootAlias'));
        }

        // Bugfix, because db is not up to date
        $this->objRealUrl->addSkipMapper($dc->id, ($this->Input->post('realurl_no_inheritance')) ? true : false);
        $this->objRealUrl->addAliasMapper($dc->id, $varValue);

        // Get all childs and update these aliases
        foreach ($this->getChildRecords(array($dc->id), 'tl_page', true) as $value)
        {
            // If we have an error exit here
            try
            {
                $objChildPage = $this->getPageDetails($value);

                if ($objChildPage->realurl_no_inheritance == true)
                {
                    $this->objRealUrl->addSkipMapper($value, true);
                }

                $mixSubAlias = $this->objRealUrl->generateFolderAlias($value);

                // Add to array, because getPageDetails uses a cached db result
                $this->objRealUrl->addAliasMapper($value, $mixSubAlias);

                if ($mixSubAlias != false)
                {
                    $this->Database->prepare('UPDATE tl_page %s WHERE id=?')
                            ->set(array('alias' => $mixSubAlias))
                            ->executeUncached($value);
                }
            }
            catch (Exception $exc)
            {
                $_SESSION['TL_ERROR'][] = $exc->getMessage();
                break;
            }
        }

        // Return the current
        return $varValue;
    }

    /**
     * Hide the parent alias from the user when editing the alias field.
     * Including the root page alias.
     * 
     * @param	string
     * @param	DataContainer
     * @return	string
     * @link	http://www.contao.org/callbacks.html#load_callback
     * @version 2.0
     */
    public function hideParentAlias($varValue, $dc)
    {
        $objPage = $this->getPageDetails($dc->id);
        $objRoot = $this->Database->execute("SELECT * FROM tl_page WHERE id=" . (int) $objPage->rootId);

        if ($objRoot->folderAlias)
        {
            $arrFolders = trimsplit("/", $varValue);
            $varValue   = array_pop($arrFolders);
        }

        return $varValue;
    }

    /**
     * Generate the page alias even if the alias field is hidden from the user
     * 
     * @param DataContainer
     * @return void
     * @link http://www.contao.org/callbacks.html#onsubmit_callback
     * @version 2.0
     */
    public function verifyAliases($dc)
    {
        // Check dc
        if (!$dc->activeRecord)
        {
            return;
        }

        // Load current page
        $objPage = $this->getPageDetails($dc->id);

        // Load root page
        if ($objPage->type == 'root')
        {
            $objRoot = $objPage;
        }
        else
        {
            $objRoot = $this->Database
                    ->prepare("SELECT * FROM tl_page WHERE id=?")
                    ->execute($objPage->rootId);
        }

        // Check if realurl is enabled
        if (!$objRoot->folderAlias)
        {
            return;
        }

        // Check if alias exists or create one
        if ($dc->activeRecord->alias == '')
        {
            try
            {
                $strAlias = $this->generateFolderAlias('', $dc, true);
            }
            catch (Exception $exc)
            {
                $_SESSION['TL_INFO'][] = $exc->getMessage();
                return;
            }

            $this->Database
                    ->prepare("UPDATE tl_page SET alias=? WHERE id=?")
                    ->execute($strAlias, $dc->id);
        }

        // Check if the subalias is enabled
        if ($objRoot->subAlias)
        {
            try
            {
                $this->generateAliasRecursive($dc->id, true);
            }
            catch (Exception $exc)
            {
                $_SESSION['TL_INFO'][] = $exc->getMessage();
                return;
            }
        }
    }

    /**
     * Create all aliases for current page and subpages.
     * 
     * @param int $intParentID ID of current page
     * @param bool $useExtException See generateFolderAlias for more informations 
     * @return void
     */
    public function generateAliasRecursive($intParentID, $useExtException = false)
    {
        $arrChildren = $this->getChildRecords($intParentID, 'tl_page', true);

        if (count($arrChildren))
        {
            $objChildren = $this->Database
                    ->prepare("SELECT * FROM tl_page WHERE id IN (" . implode(',', $arrChildren) . ") ORDER BY id")
                    ->executeUncached();

            while ($objChildren->next())
            {
                // Check if overwrite is enabled
                if ($objChildren->realurl_overwrite == true)
                {
                    $this->generateAliasRecursive($objChildren->id, $useExtException);
                    continue;
                }

                $arrFolders = trimsplit("/", $objChildren->alias);
                $strAlias   = array_pop($arrFolders);
                $strAlias   = $this->generateFolderAlias($strAlias, (object) array(
                            'id'           => $objChildren->id,
                            'activeRecord' => $objChildren), $useExtException);

                $this->Database
                        ->prepare("UPDATE tl_page SET alias=? WHERE id=?")
                        ->executeUncached($strAlias, $objChildren->id);

                $this->generateAliasRecursive($objChildren->id, $useExtException);
            }
        }
    }

}