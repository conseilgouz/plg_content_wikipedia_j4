<?php
/**
* Wikipedia Plugin  - Joomla 4.x/5.x/6.x plugin
* copyright 		: Copyright (C) 2025 ConseilGouz. All rights reserved.
* license    		: https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
*/
// No direct access to this file
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;
use Joomla\Component\Categories\Administrator\Model\CategoryModel;
use Joomla\Component\Mails\Administrator\Model\TemplateModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;

class plgcontentwikipediaInstallerScript
{
    private $min_joomla_version      = '4.0.0';
    private $min_php_version         = '8.0';
    private $name                    = 'Plugin Content Wikipedia';
    private $exttype                 = 'plugin';
    private $extname                 = 'wikipedia';
    private $previous_version        = '';
    private $newlib_version	         = '';
    private $dir           = null;
    private $db;
    private $lang;
    private $installerName = 'plgcontentwikipediainstaller';
    public function __construct()
    {
        $this->dir = __DIR__;
        $this->lang = Factory::getLanguage();
        $this->lang->load($this->extname);
    }

    public function preflight($type, $parent)
    {
        if (! $this->passMinimumJoomlaVersion()) {
            $this->uninstallInstaller();
            return false;
        }

        if (! $this->passMinimumPHPVersion()) {
            $this->uninstallInstaller();
            return false;
        }
        // To prevent installer from running twice if installing multiple extensions
        if (! file_exists($this->dir . '/' . $this->installerName . '.xml')) {
            return true;
        }
    }
    function uninstall($parent) {
        $template = new TemplateModel(array('ignore_request' => true));
        $table = $template->getTable();
        $table->delete('plg_content_automsg.ownermail');
        $table->delete('plg_content_automsg.usermail');
        return true;
    }

    public function postflight($type, $parent)
    {
        $this->db = Factory::getContainer()->get(DatabaseInterface::class);

        if (($type == 'install') || ($type == 'update')) { // remove obsolete dir/files
            $this->postinstall_cleanup();
        }
        if (!$this->checkLibrary('conseilgouz')) { // need library installation
            $ret = $this->installPackage('lib_conseilgouz');
            if ($ret) {
                Factory::getApplication()->enqueueMessage('ConseilGouz Library ' . $this->newlib_version . ' installed', 'notice');
            }
        }
        return true;
    }
    private function postinstall_cleanup()
    {

        $obsoleteFolders = ['language'];
        // Remove plugins' files which load outside of the component. If any is not fully updated your site won't crash.
        foreach ($obsoleteFolders as $folder) {
            $f = JPATH_SITE . '/plugins/plg_content_'.$this->extname.'/' . $folder;

            if (!@file_exists($f) || !is_dir($f) || is_link($f)) {
                continue;
            }
            Folder::delete($f);
        }
        $db = $this->db;
        $conditions = array(
            $db->qn('type') . ' = ' . $db->q('plugin'),
            $db->qn('element') . ' = ' . $db->quote($this->extname)
        );
        $fields = array($db->qn('enabled') . ' = 1');

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
        $db->setQuery($query);
        try {
            $db->execute();
        } catch (\RuntimeException $e) {
            Log::add('unable to enable '.$this->name, Log::ERROR, 'jerror');
        }
    }
    // Check if Joomla version passes minimum requirement
    private function passMinimumJoomlaVersion()
    {
        $j = new Version();
        $version = $j->getShortVersion();
        if (version_compare($version, $this->min_joomla_version, '<')) {
            Factory::getApplication()->enqueueMessage(
                'Incompatible Joomla version : found <strong>' . $version . '</strong>, Minimum : <strong>' . $this->min_joomla_version . '</strong>',
                'error'
            );

            return false;
        }

        return true;
    }

    // Check if PHP version passes minimum requirement
    private function passMinimumPHPVersion()
    {

        if (version_compare(PHP_VERSION, $this->min_php_version, '<')) {
            Factory::getApplication()->enqueueMessage(
                'Incompatible PHP version : found  <strong>' . PHP_VERSION . '</strong>, Minimum <strong>' . $this->min_php_version . '</strong>',
                'error'
            );
            return false;
        }

        return true;
    }
    private function checkLibrary($library)
    {
        $file = $this->dir.'/lib_conseilgouz/conseilgouz.xml';
        if (!is_file($file)) {// library not installed
            return false;
        }
        $xml = simplexml_load_file($file);
        $this->newlib_version = $xml->version;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $conditions = array(
             $db->qn('type') . ' = ' . $db->q('library'),
             $db->qn('element') . ' = ' . $db->quote($library)
            );
        $query = $db->getQuery(true)
                ->select('manifest_cache')
                ->from($db->quoteName('#__extensions'))
                ->where($conditions);
        $db->setQuery($query);
        $manif = $db->loadObject();
        if ($manif) {
            $manifest = json_decode($manif->manifest_cache);
            if ($manifest->version >= $this->newlib_version) { // compare versions
                return true; // library ok
            }
        }
        return false; // need library
    }
    private function installPackage($package)
    {
        $tmpInstaller = new Joomla\CMS\Installer\Installer();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tmpInstaller->setDatabase($db);
        $installed = $tmpInstaller->install($this->dir . '/' . $package);
        return $installed;
    }
    private function uninstallInstaller()
    {
        if (! is_dir(JPATH_PLUGINS . '/system/' . $this->installerName)) {
            return;
        }
        $this->delete([
            JPATH_PLUGINS . '/system/' . $this->installerName . '/language',
            JPATH_PLUGINS . '/system/' . $this->installerName,
        ]);
        $db = $this->db;
        $query = $db->getQuery(true)
            ->delete('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote($this->installerName))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'));
        $db->setQuery($query);
        $db->execute();
        Factory::getCache()->clean('_system');
    }
    public function delete($files = [])
    {
        foreach ($files as $file) {
            if (is_dir($file)) {
                Folder::delete($file);
            }

            if (is_file($file)) {
                File::delete($file);
            }
        }
    }
}
