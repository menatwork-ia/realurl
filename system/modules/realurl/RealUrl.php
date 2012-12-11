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
 * @copyright  Andreas Schempp 2008-2011
 * @copyright  MEN AT WORK 2011-2012
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @author     MEN AT WORK <cms@men-at-work.de>
 * @author     Leo Unglaub <leo@leo-unglaub.net> 
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */
class RealUrl extends Backend
{

    ////////////////////////////////////////////////////////////////////////////
    // Core Functions
    ////////////////////////////////////////////////////////////////////////////
    
    // Vars --------------------------------------------------------------------

    protected $arrAliasMapper = array();
    protected $arrRootMapper = array();
    protected $arrSkipMapper = array();

    // Core --------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
    }

    // Getter / Setter ---------------------------------------------------------

    public function addAliasMapper($intID, $strAlias)
    {
        $this->arrAliasMapper[$intID] = $strAlias;
    }

    public function resetAliasMapper()
    {
        $this->arrAliasMapper = array();
    }

    public function addRootMapper($intID, $blnUserRoot)
    {
        $this->arrRootMapper[$intID] = $blnUserRoot;
    }

    public function restRootMapper()
    {
        $this->arrRootMapper = array();
    }

    public function addSkipMapper($intID, $blnUserRoot)
    {
        $this->arrSkipMapper[$intID] = $blnUserRoot;
    }

    public function restSkipMapper()
    {
        $this->arrSkipMapper = array();
    }

    ////////////////////////////////////////////////////////////////////////////
    // Frontend Functions
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Parse url fragments to see if they are a parameter or part of the alias
     *
     * @param	array
     * @return	array
     * @link	http://www.contao.org/hooks.html?#getPageIdFromURL
     * @version 1.0
     */
    public function findAlias(array $arrFragments)
    {
        // See issues #10
        foreach ($arrFragments as $key => $value)
        {
            $arrFragments[$key] = rawurldecode($value);
        }

        // Remove empty strings
        // Remove auto_item if found
        // Reset keys
        $arrFiltered = array_values(array_filter($arrFragments, array(__CLASS__, 'fragmentFilter')));

        if (!$arrFiltered)
        {
            return $arrFragments;
        }

        // Load the global alias list
        $objAlias = $this->Database
                ->prepare("SELECT * FROM tl_realurl_aliases WHERE alias IN('" . implode("', '", $arrFragments) . "')")
                ->execute();

        $arrKnownAliases = $objAlias->fetchEach("alias");

        // Build alias 
        // Append fragments until an url parameter is found or no fragments are left
        for ($i = 1; $arrFiltered[$i] !== null && in_array($arrFiltered[$i], $arrKnownAliases); $i++)
            ;
        array_splice($arrFiltered, 0, $i, implode('/', array_slice($arrFiltered, 0, $i)));

        // Add the second fragment as auto_item if the number of fragments is even
        if ($GLOBALS['TL_CONFIG']['useAutoItem'] && count($arrFiltered) % 2 == 0)
        {
            array_insert($arrFiltered, 1, array('auto_item'));
        }

        return $arrFiltered;
    }

    /**
     * Filter functions
     * 
     * @param string $strFragment
     * @return boolean
     */
    public static function fragmentFilter($strFragment)
    {
        return strlen($strFragment) && $strFragment != 'auto_item';
    }

    ////////////////////////////////////////////////////////////////////////////
    // Backend Functions
    ////////////////////////////////////////////////////////////////////////////
    
    // Global operations -------------------------------------------------------

    /**
     * Add a new global button for show/hide alias
     * 
     * @param type $strHref
     * @param type $strLabel
     * @param type $strTitle
     * @param type $strClass
     * @param type $strAttributes
     * @param type $strTable
     * @param type $intRoot
     * 
     * @return string
     */
    public function bttShowAlias($strHref, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot)
    {
        if ($this->Session->get('realurl_showAlias'))
        {
            $strLabel = $GLOBALS['TL_LANG']['tl_page']['realurl']['aliasHide'][0];
            $strTitle = $GLOBALS['TL_LANG']['tl_page']['realurl']['aliasHide'][1];
            $blnState = 0;
        }
        else
        {
            $strLabel = $GLOBALS['TL_LANG']['tl_page']['realurl']['aliasShow'][0];
            $strTitle = $GLOBALS['TL_LANG']['tl_page']['realurl']['aliasShow'][1];
            $blnState = 1;
        }

        return vsprintf('%s<a href="%s" class="%s" title="%s"%s>%s</a> ', array(
                    //$this->User->isAdmin ? '<br/><br/>' : ' &#160; :: &#160; ',
                    '<br/><br/>',
                    $this->addToUrl($strHref . '&amp;state=' . $blnState),
                    $strClass,
                    specialchars($strTitle),
                    $strAttributes,
                    $strLabel
                ));
    }

    /**
     * Add a new button for regenerate all aliases
     * 
     * @param type $strHref
     * @param type $strLabel
     * @param type $strTitle
     * @param type $strClass
     * @param type $strAttributes
     * @param type $strTable
     * @param type $intRoot
     * @return string
     */
    public function bttRegenerate($strHref, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot)
    {
        // ToDo check user prem
        // $this->User->isAdmin
        
        return vsprintf('%s<a href="%s" class="%s" title="%s"%s>%s</a> ', array(
                    ' &#160; :: &#160; ',
                    $this->addToUrl($strHref),
                    $strClass,
                    specialchars($strTitle),
                    $strAttributes,
                    $strLabel
                ));
    }

    // Global operations function ----------------------------------------------

    /**
     * Callback for global operation - bttShowAlias
     */
    public function keyAlias()
    {
        // Save in state in Session
        $this->Session->set('realurl_showAlias', $this->Input->get('state'));

        // Redirect
        $this->redirect($this->getReferer());
    }

    /**
     * Callback for global operation - bttRegenerate
     */
    public function keyRegenerate()
    {
        // reate all aliases
        $this->regenerateAllAliases();
        
        // Redirect
        $this->redirect($this->getReferer());
    }
    
    // Mode callbacks ----------------------------------------------------------

    public function oncopyPage($intID)
    {
        // reate all aliases
        $this->regenerateAllAliases();
    }

    public function oncutPage($objDC)
    {
        // reate all aliases
        $this->regenerateAllAliases();
    }

    public function onrestorePage($intID)
    {
        // reate all aliases
        $this->regenerateAllAliases();
    }

    // Regex -------------------------------------------------------------------

    /**
     * Validate a folderurl alias.
     * The validation is identical to the regular "alnum" except 
     * that it also allows for slashes (/).
     *
     * @param	string
     * @param	mixed
     * @param	Widget
     * @return	bool
     * @version 2.0
     */
    public function validateRegexp($strRegexp, $varValue, Widget $objWidget)
    {
        if ($strRegexp == 'folderurl')
        {
            if (stripos($varValue, "/") !== false || stripos($varValue, "\\") !== false)
            {
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $objWidget->label));
            }

            if (!preg_match('/^[\pN\pL \.\/_-]*$/u', $varValue))
            {
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $objWidget->label));
            }

            if (preg_match('#/' . implode('/|/', $GLOBALS['URL_KEYWORDS']) . '/|/' . implode('$|/', $GLOBALS['URL_KEYWORDS']) . '$#', $varValue, $match))
            {
                $strError = str_replace('/', '', $match[0]);
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['realurl'], $strError, implode(', ', $GLOBALS['URL_KEYWORDS'])));
            }

            return true;
        }

        return false;
    }

    // Alias Functions ---------------------------------------------------------

    /**
     * Create a list with all aliases and save it in database 
     * 
     * @return void
     */
    public function createAliasList()
    {
        // Clear table
        $this->Database
                ->prepare("TRUNCATE tl_realurl_aliases")
                ->executeUncached();

        // Get all aliases
        $arrLists = array();

        $objAlias = $this->Database
                ->prepare("SELECT id, alias FROM tl_page")
                ->execute();

        while ($objAlias->next())
        {
            $strAlias = '';
            
            // Use cache or db
            if(key_exists($objAlias->id, $this->arrAliasMapper))
            {
                $strAlias = $this->arrAliasMapper[$objAlias->id];
            }
            else
            {
                $strAlias = $objAlias->alias;
            }
            
            if (stripos($strAlias, "/") === false)
            {
                $arrLists[$strAlias] = true;
            }
            else
            {
                foreach (trimsplit("/", $strAlias) as $value)
                {
                    $arrLists[$value] = true;
                }
            }
        }

        // Create a new list
        $this->Database
                ->prepare("INSERT INTO tl_realurl_aliases (alias) VALUES ('" . implode("'),\n('", array_keys($arrLists)) . "')")
                ->execute();
    }

    public function regenerateAllAliases()
    {
        $objRootPages = $this->Database
                ->prepare('SELECT * FROM tl_page WHERE type="root" AND folderAlias=1')
                ->executeUncached();

        while ($objRootPages->next())
        {
            // If we have an error exit here
            try
            {
                $mixAlias = $this->generateFolderAlias($objRootPages->id);

                // Add to array, because getPageDetails uses a cached db result
                $this->addAliasMapper($objRootPages->id, $mixAlias);

                // Update Alias
                if ($mixAlias != false)
                {
                    $this->Database->prepare('UPDATE tl_page %s WHERE id=?')
                            ->set(array('alias' => $mixAlias))
                            ->executeUncached($objRootPages->id);
                }

                if ($mixAlias != false)
                {
                    $arrPages = $this->getChildRecords(array($objRootPages->id), 'tl_page');
                    
                    foreach ($arrPages as $subValue)
                    {
                        // Add to array, because getPageDetails uses a cached db result
                        $mixAlias = $this->generateFolderAlias($subValue);

                        // Update Alias
                        if ($mixAlias != false)
                        {
                            // Add to array, because getPageDetails uses a cached db result
                            $this->addAliasMapper($subValue, $mixAlias);
                            
                            $this->Database->prepare('UPDATE tl_page %s WHERE id=?')
                                    ->set(array('alias' => $mixAlias))
                                    ->executeUncached($subValue);
                        }
                    }
                }
            }
            catch (Exception $exc)
            {
                $_SESSION['TL_ERROR'][] = $exc->getMessage();
                break;
            }
        }
    }

    /**
     * Generate for a tl_page an alias.
     * 
     * @param	mixed
     * @param	DataContainer
     * @param   boolean $useExtException If true an extended error message, with id, link and some more information, will be returned.
     * @return	mixed
     * @link	http://www.contao.org/callbacks.html#save_callback
     * @version 2.0
     */
    public function generateFolderAlias($intID, $varValue = '', $blnCheckInput = false)
    {
        // Init some Vars
        $objPage   = $this->getPageDetails($intID);
        $objRoot   = null;
        $objParent = null;

        $blnNoParentAlias    = false;
        $blnIsRoot           = false;
        $blnUseRootAlias     = false;
        $blnRealUrlOverwrite = false;
        $autoAlias           = false;

        $strRealUrlOverwrite = "";

        // Get the alias
        if (empty($varValue))
        {
            $varValue = explode("/", $objPage->alias);
            $varValue = array_pop($varValue);
        }

        // Load root page
        if ($objPage->type == 'root')
        {
            $objRoot   = $objPage;
            $blnIsRoot = true;
        }
        else
        {
            // Get root/parent page
            $objRoot   = $this->getPageDetails($objPage->rootId);
            $objParent = $this->getPageDetails($objPage->pid);

            // Get state of no inheritance from cache or database
            if (key_exists($objParent->id, $this->arrSkipMapper))
            {
                $blnNoParentAlias = $this->arrSkipMapper[$objParent->id];
            }
            else if ($objParent->realurl_no_inheritance == 1)
            {
                $blnNoParentAlias = true;
            }
        }

        // Set state of use root alias
        if (key_exists($objRoot->id, $this->arrRootMapper))
        {
            $blnUseRootAlias = $this->arrRootMapper[$objRoot->id];
        }
        else
        {
            $blnUseRootAlias = $objRoot->useRootAlias;
        }

        // Check if realurl is enabled
        if (!$objRoot->folderAlias)
        {
            return false;
        }

        // Check if overwrite is enabeld. Only for current DC
        if ($blnCheckInput && $objPage->id == $this->Input->get('id') && $this->Input->post('realurl_overwrite') == true)
        {
            $blnRealUrlOverwrite = true;
            $strRealUrlOverwrite = $this->Input->post('realurl_basealias');

            if (strlen($strRealUrlOverwrite) == 0)
            {
                throw new Exception($GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']);
            }
        }
        // Check if overwrite is enabeld. Only for current DC
        else if ($objPage->realurl_overwrite == true)
        {
            $blnRealUrlOverwrite = true;
            $strRealUrlOverwrite = $objPage->realurl_basealias;

            if (strlen($strRealUrlOverwrite) == 0)
            {
                throw new Exception($GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']);
            }
        }

        // Generate an alias if there is none
        if ($blnCheckInput && $varValue == '' && $dc->id == $this->Input->get('id') && strlen($this->Input->post('title')) != 0)
        {
            $autoAlias = true;
            $varValue  = standardize($this->Input->post('title'));
        }
        else if ($blnCheckInput && $varValue == '')
        {
            $autoAlias = true;
            $varValue  = standardize($objPage->title);
        }
        // Generate an alias if there is none
        else if ($varValue == '')
        {
            $autoAlias = true;
            $varValue  = standardize($objPage->title);
        }

        // Check Keywords
        if (in_array($varValue, $GLOBALS['URL_KEYWORDS']) && $useExtException == false)
        {
            throw new Exception($GLOBALS['TL_LANG']['ERR']['realUrlKeywords'], $objPage->id);
        }
        else if (in_array($varValue, $GLOBALS['URL_KEYWORDS']) && $useExtException == true)
        {
            $strUrl = $this->Environment->base . "contao/main.php?do=page&act=edit&id=" . $objPage->id;
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['realUrlKeywordsExt'], $strUrl, $objPage->title, $objPage->id, $varValue), $objPage->id);
        }

        // Create alias, check some/many conditions
        // Check if we have a root page
        if ($blnIsRoot == true)
        {
            // Use the value like it is, because we have a root page
        }
        else
        {
            // Check if overwrite is enabled
            if ($blnRealUrlOverwrite == true)
            {
                // Use only the overwrite value 
                $varValue = preg_replace("/\/$/", "", $strRealUrlOverwrite);
            }
            else
            {
                // Check if we have to skip the parent alias
                if ($blnNoParentAlias == true)
                {
                    // Create a copy from current parent
                    $objValidParent = $objParent;

                    // Get the next valid parent page
                    while (true)
                    {
                        $objValidParent = $this->getPageDetails($objValidParent->pid);

                        if ($objValidParent == null)
                        {
                            // If this is true we have an error or bug, but we will stop here
                            throw new Exception($GLOBALS['TL_LANG']['ERR']['noPageFound'], $objPage->id);
                        }

                        if (($objValidParent->realurl_no_inheritance == 0 && !key_exists($objValidParent->id, $this->arrSkipMapper)) || $objValidParent->type == 'root')
                        {
                            break;
                        }
                    }

                    // Check if we have root and if we can use it
                    if ($objValidParent->type == 'root')
                    {
                        if ($blnUseRootAlias == true)
                        {
                            // Use the parent alias
                            if (key_exists($objValidParent->id, $this->arrAliasMapper))
                            {
                                $varValue = $this->arrAliasMapper[$objValidParent->id] . '/' . $varValue;
                            }
                            else
                            {
                                $varValue = $objValidParent->alias . '/' . $varValue;
                            }
                        }
                        else
                        {
                            // Use the value like it is, because we have no valid parent and we could not use the root
                        }
                    }
                    else
                    {
                        // Use the parent alias
                        if (key_exists($objValidParent->id, $this->arrAliasMapper))
                        {
                            $varValue = $this->arrAliasMapper[$objValidParent->id] . '/' . $varValue;
                        }
                        else
                        {
                            $varValue = $objValidParent->alias . '/' . $varValue;
                        }
                    }
                }
                else
                {
                    // Check if we have to use the root alias
                    if ($blnUseRootAlias == true)
                    {
                        // Use the parent alias
                        if (key_exists($objParent->id, $this->arrAliasMapper))
                        {
                            $varValue = $this->arrAliasMapper[$objParent->id] . '/' . $varValue;
                        }
                        else
                        {
                            $varValue = $objParent->alias . '/' . $varValue;
                        }
                    }
                    else
                    {
                        // If we don`t use the root, check if parent is one
                        if ($objParent->type == 'root')
                        {
                            // Use the value like it is, because the parent page is a root page
                        }
                        else
                        {
                            // Use the parent alias
                            if (key_exists($objParent->id, $this->arrAliasMapper))
                            {
                                $varValue = $this->arrAliasMapper[$objParent->id] . '/' . $varValue;
                            }
                            else
                            {
                                $varValue = $objParent->alias . '/' . $varValue;
                            }
                        }
                    }
                }
            }
        }

        // Check whether the page alias exists, if add language to url is enabled
        // Search only in one language page tree        
        if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'] == true)
        {
            $arrChildren = $this->getChildRecords(array($objPage->rootId), 'tl_page', false);

            if (count($arrCildren) != 0)
            {
                $objAlias = $this->Database
                        ->prepare("SELECT id FROM tl_page WHERE (id=? OR alias=?) AND id IN(" . implode(", ", $arrChildren) . ")")
                        ->execute($objPage->id, $varValue);
            }
            else
            {
                $objAlias = $this->Database
                        ->prepare("SELECT id FROM tl_page WHERE (id=? OR alias=?)")
                        ->execute($objPage->id, $varValue);
            }
        }
        else
        {
            $objAlias = $this->Database
                    ->prepare("SELECT id FROM tl_page WHERE id=? OR alias=?")
                    ->execute($objPage->id, $varValue);
        }

        if ($objAlias->numRows > ($autoAlias ? 0 : 1))
        {
            $arrPages = array();
            $strDomain   = '';
            $strLanguage = '';

            while ($objAlias->next())
            {
                $objCurrentPage = $this->getPageDetails($objAlias->id);
                $domain         = ($objCurrentPage->domain != '') ? $objCurrentPage->domain : '*';
                $language       = (!$objCurrentPage->rootIsFallback) ? $objCurrentPage->rootLanguage : '*';

                // Store the current page data
                if ($objCurrentPage->id == $objPage->id)
                {
                    $strDomain   = $domain;
                    $strLanguage = $language;
                }
                else
                {
                    if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'])
                    {
                        // Check domain and language
                        $arrPages[$domain][$language][] = $objAlias->id;
                    }
                    else
                    {
                        // Check the domain only
                        $arrPages[$domain][] = $objAlias->id;
                    }
                }
            }

            $arrCheck = $GLOBALS['TL_CONFIG']['addLanguageToUrl'] ? $arrPages[$strDomain][$strLanguage] : $arrPages[$strDomain];

            // Check if there are multiple results for the current domain
            if (!empty($arrCheck))
            {
                if ($autoAlias)
                {
                    $varValue .= '-' . $objPage->id;
                }
                else
                {
                    throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
                }
            }
        }

        return $varValue;
    }

    // Lables ------------------------------------------------------------------

    /**
     * Callback for the lables on the overview page
     * 
     * @param type $row
     * @param type $label
     * @param DataContainer $dc
     * @param type $imageAttribute
     * @param type $blnReturnImage
     * @param type $blnProtected
     * @return type
     */
    public function labelPage($row, $label, DataContainer $dc = null, $imageAttribute = '', $blnReturnImage = false, $blnProtected = false)
    {
        // Call some callbacks
        $arrCallback = array(
            array('tl_page', 'addIcon')
        );

        foreach ($arrCallback as $value)
        {
            $this->import($value[0]);
            $label = $this->$value[0]->$value[1]($row, $label, $dc, $imageAttribute, $blnReturnImage, $blnProtected);
        }

        if ($this->Session->get('realurl_showAlias') == 0)
        {
            return $label;
        }

        // Get the alias
        $strAlias = $row['alias'];

        if (strlen($strAlias) == 0)
        {
            return $label;
        }

        if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'])
        {
            if ($row['type'] == 'root')
            {
                $strLanguage = $row['language'];
            }
            else
            {
                $objPage     = $this->getPageDetails($row['id']);
                $strLanguage = $objPage->language;
            }
        }

        // Build lable
        $arrAlias = explode("/", $strAlias);

        if (!empty($strLanguage))
        {
            $arrAlias = array_merge(array($strLanguage), $arrAlias);
        }


        $strPageTitle = array_pop($arrAlias);

        $strLableAlias = ' <span style="color:#a3a3a3;">[';

        if (count($arrAlias) != 0)
        {
            $strLableAlias .= implode('/', $arrAlias) . '/';
        }
        $strLableAlias .= '<span style="color:#d98f46;">' . $strPageTitle . '</span>';
        $strLableAlias .= ']</span>';

        return $label . $strLableAlias;
    }

}