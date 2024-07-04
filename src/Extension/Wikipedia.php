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
            'onAjaxWikipedia'   => 'goAjax',
            
        ];
    }
    public function onContent(ContentPrepareEvent $event)
    {
        
        $media	= 'media/plg_content_wikipedia/';
        /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('wikipedia', $media.'css/wikipedia.css');
        $wa->registerAndUseScript('wikipedia', $media.'js/wikipedia.js');

        return true;
    }
    public function goAjax($event) {
        $input	= Factory::getApplication()->input;
        $text  = $input->get('text', '', 'string');

        $out ='{"ret":"9","msg":"'.$text.'"}';
        return  $event->addResult($out);

        
    }
}
