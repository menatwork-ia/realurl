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
class RealUrl extends Frontend
{

    ////////////////////////////////////////////////////////////////////////////
    // Core Functions
    ////////////////////////////////////////////////////////////////////////////
    // Vars

    protected $arrAliasMapper = array();
    protected $arrRootMapper = array();
    protected $arrSkipMapper = array();

    // Core

    public function __construct()
    {
        parent::__construct();
    }

    // Getter / Setter

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

    public static function fragmentFilter($strFragment)
    {
        return strlen($strFragment) && $strFragment != 'auto_item';
    }

    ////////////////////////////////////////////////////////////////////////////
    // Backend Functions
    ////////////////////////////////////////////////////////////////////////////
    
    // Regex -------------------------------------------------------------------

    /**
     * Validate a folderurl alias.
     * The validation is identical to the regular "alnum" except that it also allows for slashes (/).
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
                ->prepare("SELECT alias FROM tl_page")
                ->execute();

        while ($objAlias->next())
        {
            if (stripos($objAlias->alias, "/") === false)
            {
                $arrLists[$objAlias->alias] = true;
            }
            else
            {
                foreach (trimsplit("/", $objAlias->alias) as $value)
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

    public function foobaa($dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            $this->regenerateAllAliases();
        }

        // Array with pages
        $arrKnownId = array();

        // Generate the current record alias
        $mixAlias = $this->generateFolderAlias($dc->id);

        if ($mixAlias != false)
        {
            // Set as allready done
            $arrKnownId[$dc->id];

            // Update alias 
            $this->Database->prepare('UPDATE tl_page %s WHERE id=?')
                    ->set(array('alias' => $mixAlias))
                    ->executeUncached($dc->id);
        }

        if ($mixAlias != false)
        {
            // Create the aliases for all sub pages
            foreach ($this->getChildRecords($dc->id, 'tl_page') as $value)
            {
                if (in_array($value, $arrKnownId))
                {
                    continue;
                }

                $arrKnownId[$value];

                $mixSubAlias = $this->generateFolderAlias($value);

                if ($mixSubAlias != false)
                {
                    $this->Database->prepare('UPDATE tl_page %s WHERE id=?')
                            ->set(array('alias' => $mixSubAlias))
                            ->executeUncached($value);
                }
            }
        }
    }

    public function regenerateAllAliases()
    {
        return;
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

            // Get state of no inheritance
            if ($objParent->realurl_no_inheritance == 1)
            {
                $blnNoParentAlias = true;
            }
        }

        // Set state of use root alias
        if(key_exists($objRoot->id, $this->arrRootMapper))
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

                        if ( ($objValidParent->realurl_no_inheritance == 0 && !key_exists($objValidParent->id, $this->arrSkipMapper)) || $objValidParent->type == 'root')
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

        // Get the alias
        $strAlias = $row['alias'];

        if (strlen($strAlias) == 0)
        {
            return $label;
        }

        // $GLOBALS['TL_CONFIG']['addLanguageToUrl']
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

        //$strLableAlias .= $this->getLablePicture($row);

        return $label . $strLableAlias;
    }

    /**
     * Get the icon for a special node.
     * 
     * @param type $row
     * @return type
     */
    protected function getLablePicture($row)
    {
        $strImageTag = '  <img src="system/modules/realurl/html/img/%s" alt="%s" />';
        $strReturn   = '';

        // Root page
        if ($row['type'] == 'root')
        {
            // Generate options
            if (empty($row['folderAlias']))
            {
                $strReturn .= sprintf($strImageTag, 'node.png', 'Keine Vererbung aktiviert.');
            }
            else
            {
                // Use alias from root page
                if (empty($row['useRootAlias']))
                {
                    $strReturn .= sprintf($strImageTag, 'node-select-child.png', 'Vererbung aktiviert, ohne Rootseite');
                }
                else
                {
                    $strReturn .= sprintf($strImageTag, 'node-select-all.png', 'Keine Verrebung aktiviert, mit Rootseite.');
                }

                // Auto update options
                if (!empty($row['subAlias']))
                {
                    $strReturn .= sprintf($strImageTag, 'node-design', 'Aliases von Unterseiten aktualisieren.');
                }
            }
        }

        return $strReturn;
    }

}