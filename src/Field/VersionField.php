<?php
/**
* Wikipedia Plugin  - Joomla 4.x/5.x plugin
* copyright 		: Copyright (C) 2024 ConseilGouz. All rights reserved.
* license    		: https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
*/
namespace ConseilGouz\Plugin\Content\Wikipedia\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\String\StringHelper;

// Prevent direct access
defined('_JEXEC') || die;

class VersionField extends FormField
{
	/**
	 * Element name
	 *
	 * @var   string
	 */
	protected $_name = 'Version';
	private $plg_full_name;
	private $default_lang;
	private $langShortCode;

	function getInput()
	{
		$return = '';

		$xml = $this->def('xml');

		// Load language
		$jinput = Factory::getApplication()->input;
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
		$query
			->select($db->quoteName(array('element','folder','type')))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('extension_id') . '=' . $db->Quote($jinput->get('extension_id', null)));
		$db->setQuery($query, 0, 1);
		$row = $db->loadAssoc();

		if ($row['type'] == 'plugin')
		{
			$this->plg_full_name = 'plg_' . $row['folder'] . '_' . $row['element'];

			// Is used for building joomfish links
			$this->langShortCode = null;

			$this->default_lang = ComponentHelper::getParams('com_languages')->get('admin');
			$language = Factory::getLanguage();
			$language->load($this->plg_full_name, JPATH_ROOT . dirname($xml), 'en-GB', true);
			$language->load($this->plg_full_name, JPATH_ROOT . dirname($xml), $this->default_lang, true);
		}

		$extension = $this->def('extension');

		$user = Factory::getApplication()->getIdentity();
		$authorise = $user->authorise('core.manage', 'com_installer');

		if (!StringHelper::strlen($extension) || !StringHelper::strlen($xml) || !$authorise)
		{
			return;
		}

		$version = '';

		if ($xml)
		{
			$xml = simplexml_load_file(JPATH_SITE . '/' . $xml);
			if ($xml && isset($xml->version))
			{
				$version = $xml->version;
			}
		}
		$margintop = $this->def('margintop');

		$document = Factory::getDocument();
		$css = '';
		$css .= ".version {display:block;text-align:right;color:brown;font-size:10px;margin-top:".$margintop."}";
		$css .= ".readonly.plg-desc {font-weight:normal;}";
		$css .= "fieldset.radio label {width:auto;}";
		$document->addStyleDeclaration($css);

		$return .= '<span class="version">' . Text::_('JVERSION') . ' ' . $version . "</span>";

		return $return;
	}
	public function def($val, $default = '')
	{
	    return ( isset( $this->element[$val] ) && (string) $this->element[$val] != '' ) ? (string) $this->element[$val] : $default;
	}
	
}
