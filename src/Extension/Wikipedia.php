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
use Joomla\CMS\Date\Date;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
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
        if ($this->params->get('ajax','false') == "true") {
            $sectionsList = $this->params->get('sectionsList');
            foreach ($sectionsList as $section) {
                $entry = [];
                $entry['definition'] = $section->definition;
                $entry['language'] = $section->language;
                $entry['url'] = $section->url;
                $lang = explode('-', $entry['language'])[0];
                $dictionary[strtolower($section->text).'&'.$lang] = $entry;
            }
        }
        Factory::getApplication()->getDocument()->addScriptOptions(
            'plg_content_wikipedia',
            array('dictionary' => $dictionary,
                  'ajax' => $this->params->get('ajax', 'false'),
                  'color' => $this->params->get('color', ''),
                  'linkcolor' => $this->params->get('linkcolor', ''),
                  'bgcolor' => $this->params->get('bgcolor', ''),
                )
        );

        return true;
    }
    public function goAjax($event)
    {
        $this->loadLanguage();
        $input	= Factory::getApplication()->input;
        $action = $input->get('action');
        if ($action == "dictload") { // load ajax dictionary
            $val = $this->create_dico();
            if (!$val) {
                $msg = Text::_('PLG_CONTENT_WIKIPEDIA_SQLERR');
                Factory::getApplication()->enqueueMessage($msg, 'notice');
                $out = '{"ret":"0","msg":"'.$msg.'"}';
            } else {
                $msg = sprintf(Text::_('PLG_CONTENT_WIKIPEDIA_SQLOK'), $val);
                Factory::getApplication()->enqueueMessage($msg, 'error');
                $out = '{"ret":"9","msg":"'.$msg.'"}';
            }
        } elseif ($action == "info") {
            $text = $input->getRaw('text');
            $lang = $input->get('lang');
            $result = $this->getInfo($text, $lang);
            $out = '{"ret":"0","'.$result.'"}';
        } else {
            $out = '{"ret":"9","msg":"Inconnu"}';
        }
        return  $event->addResult($out);
    }
    /*
    Create dico from dico.txt file in tmp directory
    It's an UTF-8 encoded file
    Each line contains 4  fields, tab separated
    Text<tab>Language<tab>Definition<tab>url
    Note : language and URL may be empty
    */
    private function create_dico()
    {
        $tab = "\t";
        if (!is_file(JPATH_ROOT.'/tmp/dico.txt')) {
            return;
        }
        $fp = fopen(JPATH_ROOT.'/tmp/dico.txt', 'r');
        if (!$fp) { // file not found : exit
            return;
        }
        // clean up dictionary table
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->truncateTable('#__cgwiki');
        $user = Factory::getApplication()->getIdentity();
        // reload it from text file
        $columns = array('text', 'language', 'definition',  'url', 'created', 'created_by');
        $count = 0;
        while (!feof($fp)) {
            $line = fgets($fp, 2048);
            $data = str_getcsv($line, $tab);
            $val = $data[0];
            if (!$val) { // empty record : exit
                continue;
            }
            $query = $db->getQuery(true);
            $lang = $data[1]; // language
            if (!$lang) {
                $lang = "*";
            }
            $def = $data[2]; // definition
            $max_strlen = 3000;
            if ($max_strlen > 0 && strlen($def) > $max_strlen) {
                $def = substr($def, 0, $max_strlen);
            }
            $def = ' '.trim($def).' ';
            $def = str_replace("@", "/", $def);
            $def = str_replace("\\x27", "'", $def);
            $def = htmlspecialchars($def, ENT_NOQUOTES);
            $def = str_replace(array("\n"," ~ ","\r"), array("<br />","<br />", ""), $def);

            $url = "";
            if (isset($data[3])) {
                $url = trim($data[3]);
            }

            $date = new Date();

            $values = array($db->q($data[0]), $db->q($lang),$db->q($def),$db->q($url), $db->q($date),$db->q($user->id) );
            $query
            ->insert($db->quoteName('#__cgwiki'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));
            // Set the query using our newly populated query object and execute it.
            $db->setQuery($query);
            $db->execute();
            $count++;
        }
        fclose($fp);
        return $count;
    }
    /*
        get info for selected text
        1. look for info in #__cgwiki table
        2. else look for info in wikipedia
    */
    private function getInfo($text, $lang)
    {
        $res = $this->getInfoDB($text, $lang);
        if (!$res) {
            $res = $this->getInfoWikipedia($text, $lang);
        }
        return $res;
    }
    private function getInfoDB($text, $lang)
    {
        $langs = ['*',$lang];
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*')
        ->from($db->quoteName('#__cgwiki'))
        ->where($db->qn('text') .' LIKE '.$db->q($text))
        ->where("(SUBSTRING(".$db->qn('language').", 1, 2) LIKE ".$db->q($lang) ." OR ".$db->qn('language'). " = ".$db->q('*').")");
        $db->setQuery($query);
        $res = $db->loadObject();
        if ($res) {
            return "definition\":\"".$res->definition."\",\"url\":\"".$res->url;
        } else {
            return false;
        }
    }
    private function getInfoWikipedia($text, $lang)
    {
        $text = str_replace(" ", '%20', $text);
        $url = 'https://api.wikimedia.org/core/v1/wikipedia/'.$lang.'/search/page?q='.$text.'&limit=1';
        $response = self::getWikipedia_via_curl($url);
        if ($response) {
            $json_array = json_decode($response);
            if (!isset($json_array->pages)) {
                return false;
            }
            $res = $json_array->pages[0];
            $url = "https://".$lang.".wikipedia.org/wiki/".$res->key;
            return "definition\":\"".$res->description."\",\"url\":\"".$url;
        }
    }
    private static function getWikipedia_via_curl($url)
    {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        } catch (\RuntimeException $e) {
            return null;
        }
    }

}
