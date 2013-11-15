<?php

if (!defined('TL_ROOT'))
    die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * @copyright  Andreas Schempp 2008-2011
 * @copyright  MEN AT WORK 2012-2013 
 * @package    realurl
 * @license    GNU/LGPL 
 * @filesource
 */
class RealUrl extends Backend
{

    // Core --------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
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

    // Functions ---------------------------------------------------------------

    /**
     * Auto-generate a page alias if it has not been set yet
     * @param mixed
     * @param \DataContainer
     * @return string
     * @throws \Exception
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $autoAlias = false;

        //pizzaschule/bedienung
        
        // Generate an alias if there is none
        if ($varValue == '')
        {
            $autoAlias = true;
            $varValue  = standardize(String::restoreBasicEntities($dc->activeRecord->title));

            // Generate folder URL aliases (see #4933)
            if ($GLOBALS['TL_CONFIG']['folderUrl'])
            {
                $dc->activeRecord->alias = $varValue;
                $objPage                 = PageModel::findWithDetails($dc->activeRecord->id);

                $intPid = $objPage->pid;
                $i      = 0;
                
                while ($i < 90)
                {
                    // Get parent.
                    $objParentPage = PageModel::findWithDetails($intPid);

                    // Skip for root or empty.
                    if ($objParentPage->tpye == 'root' || empty($objParentPage))
                    {
                        break;
                    }

                    // Check flag.
                    if ($objParentPage->realurl_no_inheritance == true)
                    {
                        $objPage->folderUrl = $objParentPage->folderUrl;
                        break;
                    }
                    else
                    {
                        $intPid = $objParentPage->pid;
                    }

                    // Security flag.
                    $i++;
                }

                if ($objPage->folderUrl != '')
                {
                    $varValue = $objPage->folderUrl . $varValue;
                }
            }
        }

        $objAlias = $this->Database->prepare("SELECT id FROM tl_page WHERE id=? OR alias=?")
                ->execute($dc->id, $varValue);

        // Check whether the page alias exists
        if ($objAlias->numRows > ($autoAlias ? 0 : 1))
        {
            $arrPages    = array();
            $strDomain   = '';
            $strLanguage = '';

            while ($objAlias->next())
            {
                $objCurrentPage = PageModel::findWithDetails($objAlias->id);

                $domain   = $objCurrentPage->domain ? : '*';
                $language = (!$objCurrentPage->rootIsFallback) ? $objCurrentPage->rootLanguage : '*';

                // Store the current page's data
                if ($objCurrentPage->id == $dc->id)
                {
                    // Get the DNS and language settings from the POST data (see #4610)
                    if ($objCurrentPage->type == 'root')
                    {
                        $strDomain   = Input::post('dns');
                        $strLanguage = Input::post('language');
                    }
                    else
                    {
                        $strDomain   = $domain;
                        $strLanguage = $language;
                    }
                }
                else
                {
                    // Check the domain and language or the domain only
                    if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'])
                    {
                        $arrPages[$domain][$language][] = $objAlias->id;
                    }
                    else
                    {
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
                    $varValue .= '-' . $dc->id;
                }
                else
                {
                    throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
                }
            }
        }

        return $varValue;
    }

}