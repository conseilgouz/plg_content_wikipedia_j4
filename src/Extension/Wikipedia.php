<?php
/**
 * Plugin Wikipedia : search wikipedia for selected text in an article
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 */

namespace ConseilGouz\Plugin\Content\Wikipedia\Extension;

defined('_JEXEC') or die;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

final class Wikipedia extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare'   => 'onContent',
        ];
    }
    public function onContent(ContentPrepareEvent $event)
    {
        $context = $event[0];
        $article = $event[1];
        $params = $event[2];
        if (($context != "com_content.featured") && ($context != "com_content.category") && ($context != "com_content.article")) {
            return;
        }
        $media	= 'media/plg_content_wikipedia/';
        /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('wikipedia', $media.'css/wikipedia.css');
        $wa->registerAndUseScript('wikipedia', $media.'js/wikipedia.js');
        $dictionary = [];
        $sectionsList = $this->params->get('sectionsList');
        foreach ($sectionsList as $section) {
            $entry = [];
            $entry['definition'] = $section->definition;
            $entry['language'] = $section->language;
            $entry['url'] = $section->url;
            $lang = explode('-', $entry['language'])[0];
            $dictionary[strtolower($section->text).'&'.$lang] = $entry;
        }
        Factory::getApplication()->getDocument()->addScriptOptions(
            'plg_content_wikipedia',
            array('dictionary' => $dictionary
                )
        );

        return true;
    }
}
