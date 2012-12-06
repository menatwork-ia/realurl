<?php

if (!defined('TL_ROOT'))
	die('You cannot access this file directly!');

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
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */
/**
 * Replace core callbacks
 */
array_insert($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'], 0, array(array('tl_page_realurl', 'verifyAliases')));

foreach ($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] as $i => $arrCallback)
{
	if ($arrCallback[1] == 'generateArticle')
	{
		$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][$i][0] = 'tl_page_realurl';
		break;
	}
}

foreach ($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'] as $i => $arrCallback)
{
	if ($arrCallback[1] == 'generateAlias')
	{
		$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][$i] = array('tl_page_realurl', 'generateFolderAlias');
		break;
	}
}

$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = array('tl_page_realurl', 'createAliasList');

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] .= ';{realurl_legend},folderAlias,subAlias,useRootAlias';
$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][]			 = 'realurl_overwrite';
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['realurl_overwrite']	 = 'realurl_basealias';

foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $keyPalette => $valuePalette)
{
	if ($keyPalette != "root" && $keyPalette != '__selector__')
	{
		$GLOBALS['TL_DCA']['tl_page']['palettes'][$keyPalette] = preg_replace('@([,|;]type)([,|;])@', '$1,realurl_no_inheritance,realurl_overwrite$2', $GLOBALS['TL_DCA']['tl_page']['palettes'][$keyPalette]);
	}
}

/**
 * Callbacks
 */
$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'] = array('tl_page_realurl', 'labelPage');

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']		 = 'folderurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave']	 = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['load_callback'][]		 = array('tl_page_realurl', 'hideParentAlias');

$GLOBALS['TL_DCA']['tl_page']['fields']['folderAlias'] = array(
	'label'		 => &$GLOBALS['TL_LANG']['tl_page']['folderAlias'],
	'inputType'	 => 'checkbox',
	'eval'		 => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['subAlias'] = array(
	'label'		 => &$GLOBALS['TL_LANG']['tl_page']['subAlias'],
	'inputType'	 => 'checkbox',
	'eval'		 => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['useRootAlias'] = array(
	'label'		 => &$GLOBALS['TL_LANG']['tl_page']['useRootAlias'],
	'inputType'	 => 'checkbox',
	'eval'		 => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_no_inheritance'] = array(
	'label'		 => &$GLOBALS['TL_LANG']['tl_page']['realurl_no_inheritance'],
	'inputType'	 => 'checkbox',
	'eval'		 => array(
		'tl_class'	 => 'w50',
		'doNotCopy'	 => true
	),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_overwrite'] = array(
	'label'		 => &$GLOBALS['TL_LANG']['tl_page']['realurl_overwrite'],
	'inputType'	 => 'checkbox',
	'eval'		 => array(
		'submitOnChange' => true,
		'tl_class'		 => 'w50',
		'doNotCopy'		 => true
	),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_basealias'] = array(
	'label'			 => &$GLOBALS['TL_LANG']['tl_page']['alias'],
	'inputType'		 => 'text',
	'load_callback'	 => array(array('tl_page_realurl', 'loadFullAlias')),
	'eval' => array(
		'spaceToUnderscore'	 => true,
		'trailingSlash'		 => true,
		'doNotCopy'			 => true,
		'tl_class'			 => 'clr long'
	)
);

/**
 * Helper/Callback class
 */
class tl_page_realurl extends tl_page
{

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

		// Build lable
		$arrAlias		 = explode("/", $strAlias);
		$strPageTitle	 = array_pop($arrAlias);

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

	protected function getLablePicture($row)
	{
		$strImageTag = '  <img src="system/modules/realurl/html/img/%s" alt="%s" />';
		$strReturn = '';

		// Root page
		if ($row['type'] == 'root')
		{
			// Generate oprions
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
				if(!empty($row['subAlias']))
				{
					$strReturn .= sprintf($strImageTag, 'node-design', 'Aliases von Unterseiten aktualisieren.');
				}
			}
		}
		
		return $strReturn;
	}

	/**
	 * Only use the last portion of the page alias for the article alias
	 * 
	 * @param	DataContainer
	 * @return	void
	 * @link	http://www.contao.org/callbacks.html#onsubmit_callback
	 * @version 1.0
	 */
	public function generateArticle(DataContainer $dc)
	{
		// Return if there is no active record (override all)
		if (!$dc->activeRecord)
		{
			return;
		}

		$arrAlias				 = explode('/', $dc->activeRecord->alias);
		$dc->activeRecord->alias = array_pop($arrAlias);

		parent::generateArticle($dc);
	}

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
	 * @param   boolean $useExtException If true an extended error message, with id, link and some more information, will be returned.
	 * @return	mixed
	 * @link	http://www.contao.org/callbacks.html#save_callback
	 * @version 2.0
	 */
	public function generateFolderAlias($varValue, $dc, $useExtException = false)
	{
		// Init some Vars
		$objPage	 = $this->getPageDetails($dc->id);
		$objRoot	 = null;
		$objParent	 = null;

		$blnNoParentAlias	 = false;
		$blnIsRoot			 = false;
		$blnUseRootAlias	 = false;
		$blnRealUrlOverwrite = false;
		$autoAlias			 = false;

		$strRealUrlOverwrite = "";

		// Load root page
		if ($objPage->type == 'root')
		{
			$objRoot	 = $objPage;
			$blnIsRoot	 = true;
		}
		else
		{
			// Get root/parent page
			$objRoot	 = $this->getPageDetails($objPage->rootId);
			$objParent	 = $this->getPageDetails($objPage->pid);

			// Get state of no inheritance
			if ($objParent->realurl_no_inheritance == 1)
			{
				$blnNoParentAlias = true;
			}
		}

		// Set state of use root alias
		$blnUseRootAlias = $objRoot->useRootAlias;

		// Check if realurl is enabled
		if (!$objRoot->folderAlias)
		{
			return parent::generateAlias($varValue, $dc);
		}

		// Check if overwrite is enabeld. Only for current DC
		if ($dc->id == $this->Input->get('id') && $this->Input->post('realurl_overwrite') == true)
		{
			$blnRealUrlOverwrite = true;
			$strRealUrlOverwrite = $this->Input->post('realurl_basealias');

			if (strlen($strRealUrlOverwrite) == 0)
			{
				throw new Exception($GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']);
			}
		}

		// Generate an alias if there is none
		if ($varValue == '' && $dc->id == $this->Input->get('id') && strlen($this->Input->post('title')) != 0)
		{
			$autoAlias	 = true;
			$varValue	 = standardize($this->Input->post('title'));
		}
		else if ($varValue == '')
		{
			$autoAlias	 = true;
			$varValue	 = standardize($objPage->title);
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

						if ($objValidParent->realurl_no_inheritance == 0 || $objValidParent->realurl_no_inheritance == '' || $objValidParent->type == 'root')
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
							$varValue = $objValidParent->alias . '/' . $varValue;
						}
						else
						{
							// Use the value like it is, because we have no valid parent and we could not use the root
						}
					}
					else
					{
						// Use the parent alias
						$varValue = $objValidParent->alias . '/' . $varValue;
					}
				}
				else
				{
					// Check if we have to use the root alias
					if ($blnUseRootAlias == true)
					{
						// Use the parent alias
						$varValue = $objParent->alias . '/' . $varValue;
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
							$varValue = $objParent->alias . '/' . $varValue;
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
						->execute($dc->id, $varValue);
			}
			else
			{
				$objAlias = $this->Database
						->prepare("SELECT id FROM tl_page WHERE (id=? OR alias=?)")
						->execute($dc->id, $varValue);
			}
		}
		else
		{
			$objAlias = $this->Database
					->prepare("SELECT id FROM tl_page WHERE id=? OR alias=?")
					->execute($dc->id, $varValue);
		}

		if ($objAlias->numRows > ($autoAlias ? 0 : 1))
		{
			$arrPages = array();
			$strDomain	 = '';
			$strLanguage = '';

			while ($objAlias->next())
			{
				$objCurrentPage	 = $this->getPageDetails($objAlias->id);
				$domain			 = ($objCurrentPage->domain != '') ? $objCurrentPage->domain : '*';
				$language		 = (!$objCurrentPage->rootIsFallback) ? $objCurrentPage->rootLanguage : '*';

				// Store the current page data
				if ($objCurrentPage->id == $dc->id)
				{
					$strDomain	 = $domain;
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
			$arrFolders	 = trimsplit("/", $varValue);
			$varValue	 = array_pop($arrFolders);
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
					continue;
				}

				$arrFolders	 = trimsplit("/", $objChildren->alias);
				$strAlias	 = array_pop($arrFolders);
				$strAlias	 = $this->generateFolderAlias($strAlias, (object) array(
							'id'			 => $objChildren->id,
							'activeRecord'	 => $objChildren), $useExtException);

				$this->Database
						->prepare("UPDATE tl_page SET alias=? WHERE id=?")
						->executeUncached($strAlias, $objChildren->id);

				$this->generateAliasRecursive($objChildren->id, $useExtException);
			}
		}
	}

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

}