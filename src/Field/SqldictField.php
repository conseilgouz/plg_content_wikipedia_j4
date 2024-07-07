<?php
/**
* Wikipedia Plugin  - Joomla 4.x/5.x plugin
* copyright 		: Copyright (C) 2024 ConseilGouz. All rights reserved.
* license    		: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/

namespace ConseilGouz\Plugin\Content\Wikipedia\Field;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\String\StringHelper;

// Prevent direct access
defined('_JEXEC') || die;

class SqldictField extends FormField
{
    /**
     * Element name
     *
     * @var   string
     */
    protected $_name = 'Sqldict';

    public function getInput()
    {
        $return = '';

        // Load language
        $jinput = Factory::getApplication()->input;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query
            ->select('count(*) as count')
            ->from($db->quoteName('#__cgwiki'));
        $db->setQuery($query);
        $count = $db->loadResult();
        if (!$count) {
            $count = 0;
        }
        $query = $db->getQuery(true);
        $query
            ->select('created')
            ->from($db->quoteName('#__cgwiki'))
            ->setLimit(1);
        $db->setQuery($query);
        $date = $db->loadResult();
        if (!$date) {
            $date = "";
        } else {
            $date = HTMLHelper::date($date);
        }
        $return = "<span id='counts'>".sprintf(Text::_('PLG_CONTENT_WIKIPEDIA_SQL'), $count, $date)."</span>";

        return $return;
    }
}
