<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Demodata.Features101
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Cefjdemos\Plugin\DemoData\Features101\Extension;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\Event\SubscriberInterface;
use Joomla\Event\Event;
use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Module;
use Joomla\CMS\Workflow\Workflow;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;
use Joomla\Component\Content\Administrator\Table\ArticleTable;
use Joomla\Component\Menus\Administrator\Table\MenuTable;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Some explanation of the structure of this file:
 *
 * Public functions are called from the plugin to accomplish the install and
 * uninstall stages. The latter are completed in reverse order by checking for
 * an is_installed variable stored in the plugin parameters.
 *
 * Steps:
 * - public static function getSubscribedEvents - called to set up the plugin events
 * - public function onDemodataGetOverview - initialises the Install / Uninstall button
 * - public function onAjaxDemodataApplyStep1 - Install Users / Remove Modules
 * - public function onAjaxDemodataApplyStep2 - Install Categories / Uninstall Associations
 * - public function onAjaxDemodataApplyStep3 - Install Tags / Remove Menuitems
 * - public function onAjaxDemodataApplyStep4 - Install Banners / Remove Menus
 * - public function onAjaxDemodataApplyStep5 - Install Field Groups / Uninstall Newsfeeds
 * - public function onAjaxDemodataApplyStep6 - Install Fields / Uninstall Contacts
 * - public function onAjaxDemodataApplyStep7 - Install Workflows / Uninstall Articles
 * - public function onAjaxDemodataApplyStep8 - Install Stages / Uninstall Transitions
 * - public function onAjaxDemodataApplyStep9 - Install Transitions / Uninstall Stages
 * - public function onAjaxDemodataApplyStep10 - Install Articles / Uninstall Workflows
 * - public function onAjaxDemodataApplyStep11 - Install Contacts / Uninstall Fields
 * - public function onAjaxDemodataApplyStep12 - Install Newsfeeds / Uninstall Field Groups
 * - public function onAjaxDemodataApplyStep13 - Install Menus / Uninstall Banners
 * - public function onAjaxDemodataApplyStep14 - Install Menuitems / Uninstall Tags
 * - public function onAjaxDemodataApplyStep15 - Install Associations / Uninstall Categories
 * - public function onAjaxDemodataApplyStep16 - Install Modules / Uninstall Users
 * - public function onAjaxDemodataApplyStep17 - Completion
 *
 * Each stage, except the last, calls a function to install or uninstall one of the stages.
 *
 * The data to be installed are stored in json files in the $language folder.
 */

/**
 * Demodata - Features101 Plugin
 *
 * @since  4.0.0
 */
final class Features101 extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var     boolean
     *
     * @since   __DEPLOY_VERSION__
     */
    protected $autoloadLanguage = true;

    /**
     * @var     string
     *
     * @since   __DEPLOY_VERSION__
     */
    protected $path = null;

    /**
     * @var    integer Id, author of all generated content.
     *
     * @since   __DEPLOY_VERSION__
     */
    protected $adminId;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since 5.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onDemodataGetOverview'    => 'onDemodataGetOverview',
            'onAjaxDemodataApplyStep1' => 'onAjaxDemodataApplyStep1',
            'onAjaxDemodataApplyStep2' => 'onAjaxDemodataApplyStep2',
            'onAjaxDemodataApplyStep3' => 'onAjaxDemodataApplyStep3',
            'onAjaxDemodataApplyStep4' => 'onAjaxDemodataApplyStep4',
            'onAjaxDemodataApplyStep5' => 'onAjaxDemodataApplyStep5',
            'onAjaxDemodataApplyStep6' => 'onAjaxDemodataApplyStep6',
            'onAjaxDemodataApplyStep7' => 'onAjaxDemodataApplyStep7',
            'onAjaxDemodataApplyStep8' => 'onAjaxDemodataApplyStep8',
            'onAjaxDemodataApplyStep9' => 'onAjaxDemodataApplyStep9',
            'onAjaxDemodataApplyStep10' => 'onAjaxDemodataApplyStep10',
            'onAjaxDemodataApplyStep11' => 'onAjaxDemodataApplyStep11',
            'onAjaxDemodataApplyStep12' => 'onAjaxDemodataApplyStep12',
            'onAjaxDemodataApplyStep13' => 'onAjaxDemodataApplyStep13',
            'onAjaxDemodataApplyStep14' => 'onAjaxDemodataApplyStep14',
            'onAjaxDemodataApplyStep15' => 'onAjaxDemodataApplyStep15',
            'onAjaxDemodataApplyStep16' => 'onAjaxDemodataApplyStep16',
            'onAjaxDemodataApplyStep17' => 'onAjaxDemodataApplyStep17',
        ];
    }

    /**
     * Get an overview of the proposed demodata.
     *
     * @param  GetOverviewEvent $event Event instance
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function onDemodataGetOverview(Event $event): void
    {
        if (!$this->getApplication()->getIdentity()->authorise('core.create', 'com_content')) {
            return;
        }

        $data              = new \stdClass();
        $data->name        = $this->_name;
        $data->title       = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_OVERVIEW_TITLE');
        $data->description = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_OVERVIEW_DESC');
        $data->icon        = 'wifi';
        $data->steps       = 17;
        $data->is_installed = $this->params->get('is_installed', 0);

        $event->setArgument('result', [$data]);
    }

    /**
     * Step to install users or uninstall modules.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep1(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        // Start logging.
        $date = date("Y-m-d H:i:s");

        if (empty($is_installed)) {
            $msg = "Starting install: {$date}\n";
            file_put_contents(JPATH_ADMINISTRATOR . '/logs/features101.log', $msg);
            $this->installUsergroups($event, 1);
            $this->installUsers($event, 1);
            return;
        } else {
            $msg = "Starting uninstall: {$date}\n";
            file_put_contents(JPATH_ADMINISTRATOR . '/logs/features101.log', $msg, FILE_APPEND);
            $this->uninstallModules($event, 16);
            return;
        }
    }

    /**
     * Step to install tags or uninstall associations.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep2(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installCategories($event, 2);
            return;
        } else {
            $this->uninstallAssociations($event, 2);
            return;
        }
    }

    /**
     * Step to install banners or uninstall menuitems.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep3(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installTags($event, 3);
            return;
        } else {
            $this->uninstallMenuitems($event, 3);
            return;
        }
    }

    /**
     * Step to install fieldgroups or uninstall menus.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep4(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        // Install Step 4: Content
        if (empty($is_installed)) {
            $this->installBanners($event, 4);
            return;
        } else {
            $this->uninstallMenus($event, 4);
            return;
        }
    }

    /**
     * Step to install fields or uninstall newsfeeds.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep5(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        // Install Step 5: Menus
        if (empty($is_installed)) {
            $this->installFieldgroups($event, 5);
            return;
        } else {
            $this->uninstallNewsfeeds($event, 5);
            return;
        }
    }

    /**
     * Step to install workflows or uninstall contacts.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep6(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installFields($event, 6);
            return;
        } else {
            $this->uninstallContacts($event, 6);
            return;
        }
    }

    /**
     * Step to install stages or uninstall articles.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep7(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installWorkflows($event, 7);
            return;
        } else {
            $this->uninstallArticles($event, 7);
            return;
        }
    }

    /**
     * Step to install transitions or uninstall categories.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep8(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installWorkflowstages($event, 8);
            return;
        } else {
            $this->uninstallWorkflowtransitions($event, 8);
            return;
        }
    }

    /**
     * Step to install categories or uninstall transitions.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep9(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installWorkflowtransitions($event, 9);
            return;
        } else {
            $this->uninstallWorkflowstages($event, 9);
            return;
        }
    }

    /**
     * Step to install articles or uninstall stages.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep10(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installArticles($event, 10);
            return;
        } else {
            $this->uninstallWorkflows($event, 10);
            return;
        }
    }

    /**
     * Step to install contacts or uninstall workflows.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep11(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installContacts($event, 11);
            return;
        } else {
            $this->uninstallFields($event, 11);
            return;
        }
    }

    /**
     * Step to install newsfeeds or uninstall fields.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep12(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installNewsfeeds($event, 12);
            return;
        } else {
            $this->uninstallFieldgroups($event, 12);
            return;
        }
    }

    /**
     * Step to install menus or uninstall fieldgroups.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep13(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installMenus($event, 13);
            return;
        } else {
            $this->uninstallBanners($event, 13);
            return;
        }
    }

    /**
     * Step to install menuitems or uninstall banners.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep14(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installMenuitems($event, 14);
            return;
        } else {
            $this->uninstallTags($event, 14);
            return;
        }
    }

    /**
     * Step to install associations or uninstall tags.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep15(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installAssociations($event, 15);
            return;
        } else {
            $this->uninstallCategories($event, 15);
            return;
        }
    }

    /**
     * Step to install modules or uninstall users.
     *
     * @param   AjaxEvent $event Event instance
     *
     * @return  void
     */
    public function onAjaxDemodataApplyStep16(AjaxEvent $event): void
    {
        if (!Session::checkToken('get') || $this->getApplication()->getInput()->get('type') != $this->_name) {
            return;
        }

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);

        if (empty($is_installed)) {
            $this->installModules($event, 16);
            return;
        } else {
            $this->uninstallUsers($event, 16);
            return;
        }
    }

    /**
     * Final step to show completion of demodata.
     *
     * @param AjaxEvent $event Event instance
     *
     * @return  void
     *
     * @since  4.0.0
     */
    public function onAjaxDemodataApplyStep17(AjaxEvent $event): void
    {
        if ($this->getApplication()->getInput()->get('type') !== $this->_name) {
            return;
        }
        $step = 17;

        // Is data to be installed or uninstalled?
        $is_installed = $this->params->get('is_installed', 0);
        if ($is_installed) {
            $this->params->set('is_installed', 0);
            $msg = "Step: {$step}, Uninstallation completed\n";
        } else {
            $this->params->set('is_installed', 1);
            $msg = "Step: {$step}, Installation completed\n";
        }
        $this->updateParams($this->params);
        file_put_contents(JPATH_ADMINISTRATOR . '/logs/features101.log', $msg, FILE_APPEND);

        $response            = [];
        $response['success'] = true;
        if (!$is_installed) {
            $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');
        } else {
            $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');
        }
        $event->setArgument('result', [$response]);
    }

    /**
     * Create the language associations.
     *
     * @param   array  $groupedAssociations  Array of language associations for all items.
     *
     * @return  boolean  True on success.
     *
     * @since   4.0.0
     */
    private function addAssociations($groupedAssociations)
    {
        $db = $this->getDatabase();

        foreach ($groupedAssociations as $context => $associations) {
            $key   = md5(json_encode($associations));
            $query = $db->createQuery()
                ->insert($db->quoteName('#__associations'));

            foreach ($associations as $language => $id) {
                $query->values(
                    implode(
                        ',',
                        $query->bindArray(
                            [
                                $id,
                                $context,
                                $key,
                            ],
                            [
                                ParameterType::INTEGER,
                                ParameterType::STRING,
                                ParameterType::STRING,
                            ]
                        )
                    )
                );
            }

            $db->setQuery($query);

            try {
                $db->execute();
            } catch (\RuntimeException) {
                return false;
            }
        }

        return true;
    }

    private function updateParams($data) {
        $pluginName = 'plg_' . 'demodata_' . 'features101';
        $params = json_encode($data);

        // Create a new db object.
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = :params')
            ->where($db->quoteName('name') . ' = :pluginname')
            ->bind(':pluginname', $pluginName)
            ->bind(':params', $params);

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (ExecutionFailureException) {
            return false;
        }
    }

    /**
     * Check to see whether a previous installation failed
     *
     * @param AjaxEvent $event  Event instance
     * @param int       $step   The step number
     * @param string    $asset  The asset name, for example 'tags'
     */
    protected function checkstep($event, $step, $asset) {
        if (empty($asset)) {
            return;
        }
        if (!empty($this->params->get($asset))) {
            $msg = "Step: {$step}, Asset: {$asset}, Action: Skip\n";
            file_put_contents(JPATH_ADMINISTRATOR . '/logs/features101.log', $msg, FILE_APPEND);
            $response            = [];
            $response['success'] = true;
            $response['message'] = $msg;
            $event->setArgument('result', [$response]);
            return true;
        } else {
            $msg = "Step: {$step}, Asset: {$asset}\n";
            file_put_contents(JPATH_ADMINISTRATOR . '/logs/features101.log', $msg, FILE_APPEND);
        }
    }

    /**
     * Log uninstallation
     *
     * @param int       $step   The step number
     * @param string    $asset  The asset name, for example 'tags'
     */
    protected function loguninstallstep($step, $asset) {
        $msg = "Step: {$step}, Asset: {$asset}\n";
        file_put_contents(JPATH_ADMINISTRATOR . '/logs/features101.log', $msg, FILE_APPEND);
    }

    protected function getLanguages() {
        $languages = strtolower($this->params->get('languages'));
        return explode(',', $languages);
    }

    /**
     * Install functions in alphabet order
     */

    protected function installArticles($event, $step) {
        if ($this->checkstep($event, $step, 'articles')) {
            return;
        }

        // Create a new db object.
        $db    = $this->getDatabase();

        $mvcFactory = $this->getApplication()->bootComponent('com_content')->getMVCFactory();
        $articleModel = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);
        $article_ids = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the content articles to be installed from the $language.
        foreach($languages as $language) {

            $file = __DIR__ . "/../../{$language}/articles.json";
            if (!is_file($file)) {
                continue;
            }
            $articles_json = file_get_contents($file);
            $articles = json_decode($articles_json, true);

            foreach ($articles as $article) {
                // Get the article source text.
                $content = file_get_contents(__DIR__ . '/../../' . $language . '/articles/' . $article['text_source']);
                list($article['introtext'], $article['fulltext']) = explode('<hr id="system-readmore">', $content);

                // Get the category id from its alias.
                $query = $db->createQuery()
                    ->select($db->quoteName('id'))
                    ->from($db->quotename('#__categories'))
                    ->where($db->quoteName('alias') . '=' . $db->quote($article['category_alias']));
                $db->setQuery($query);
                $article['catid'] = $db->loadResult();

                // If empty, set to 2.
                $article['catid'] = $article['catid'] ?? 2;
                $article['id'] = 0;

                // Set the created_by_alias value to something suitable
                $article['created_by_alias'] = 'Cinderella';

                // Set the tag ids, example: "tags": ["east-lothian"],
                if (!empty($article['tags'])) {
                    $query = $db->createQuery()
                        ->select($db->quoteName('id'))
                        ->from($db->quotename('#__tags'))
                        ->whereIn($db->quoteName('alias'), $article['tags'], ParameterType::STRING);
                    try {
                        $db->setQuery($query);
                        $tagIds = $db->loadColumn();
                        $article['tags'] = $tagIds;
                        $test = $query->__tostring();
                    } catch (ExecutionFailureException) {
                        $test = 'Stop Here';
                    }
                }
                if (!$articleModel->save($article)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_FIELDS_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($articleModel->getError()));
                    file_put_contents(JPATH_ADMINISTRATOR . '/logs/features101.log', $response['message'], FILE_APPEND);

                    $event->addResult($response);
                    return;
                }

                // Get ID from article we just added
                $id = $articleModel->getState('article.id');
                $article_ids[] = $id;

                // Add the fields
                if (!empty($article['fields'])) {
                    foreach ($article['fields'] as $field_name => $value) {
                        // get the field_id from its name
                        $query = $db->createQuery();
                        $query
                            ->select($db->quoteName('id'))
                            ->from($db->quoteName('#__fields'))
                            ->where($db->quoteName('name') . '=:alias')
                            ->bind(':alias', $field_name, ParameterType::STRING);
                        $db->setQuery($query);
                        $field_id = $db->loadResult();
                        $item = (object) [
                            'item_id'  => $id,
                            'field_id' => $field_id,
                            'value'    => $value,
                        ];
                        $this->getDatabase()->insertObject('#__fields_values', $item);
                    }
                }
            }
        }

        // Store the article ids in the plugin parameters.
        $this->params->set('articles', implode(',', $article_ids));
        $this->updateParams($this->params);

        // Make a symbolic link from the plugin images folder to the media folder.
        $target = JPATH_SITE . '/plugins/demodata/features101/images';
        $link = JPATH_SITE . '/images/demodata';

        // If $link already exists, symlink() will fail — remove it first if necessary:
        if (file_exists($link)) {
            unlink($link);
        }
        symlink($target, $link);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installAssociations($event, $step) {
        if ($this->checkstep($event, $step, 'associations')) {
            return;
        }

        // Create a new db object.
        $db    = $this->getDatabase();

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the items to be installed from the last $language.
        foreach($languages as $language) {

            $file = __DIR__ . "/../../{$language}/associations.json";
            if (!is_file($file)) {
                continue;
            }
            $associations_json = file_get_contents($file);
            $associations = json_decode($associations_json, true);
            foreach ($associations as $association) {
                // Get the table to look in for the associated items.
                switch ($association['context']) {
                    case 'com_menus.item':
                        $table = '#__menu';
                        break;
                    case 'com_content.item':
                        $table = '#__content';
                        break;
                    case 'com_categories.item':
                        $table = '#__categories';
                        break;
                    default:
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_FEATURES101_ERROR_UNDEFINED_TABLE', $step, $association['context']);
                    $event->addResult($response);
                    return;
                }
                //$groupedAssociations['context'] = $association['context'];
                $ids = [];
                foreach ($association['alias'] as $itemlanguage => $alias) {
                    // Get the id of the menu item using its alias.
                    $query = $db->createQuery()
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName($table))
                        ->where($db->quoteName('alias') . '=:alias')
                        ->bind(':alias', $alias, ParameterType::STRING);
                    $db->setQuery($query);
                    $id = $db->loadResult();
                    $ids[$itemlanguage] = $id;
                }
                $groupedAssociations[$association['context']] = $ids;
                if (!$this->addAssociations($groupedAssociations)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_FEATURES101_STEP_FAILED', $step, $groupedAssociations);

                    $event->addResult($response);
                    return;
                }
                unset($groupedAssociations);
            }
        }

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installBanners($event, $step) {
        if ($this->checkstep($event, $step, 'banners')) {
            return;
        }
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installCategories($event, $step) {
        if ($this->checkstep($event, $step, 'categories')) {
            return;
        }

        // Create a new db object.
        $db    = $this->getDatabase();
        $mvcFactory = $this->getApplication()->bootComponent('com_categories')->getMVCFactory();
        $categoryModel = $mvcFactory->createModel('Category', 'Administrator', ['ignore_request' => true]);
        $category_ids = [];
        $parents = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the content articles to be installed from the $language.
        foreach($languages as $language) {

            $file = __DIR__ . "/../../{$language}/categories.json";
            if (!is_file($file)) {
                continue;
            }
            $categories_json = file_get_contents($file);
            $categories = json_decode($categories_json, true);

            foreach ($categories as $i => $category) {
                // If this is a sub-category, get the parent category id
                if ($category['level'] > 1) {
                    // Parent must be created before child category.
                    $category['parent_id'] = $parents[$category['parent_alias']];
                } else {
                    $category['parent_id'] = 1;
                }
                $category['id'] = 0;
                $category['published'] = 1;
                if (!$categoryModel->save($category)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_FIELDS_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($categoryModel->getError()));

                    $event->addResult($response);
                    return;
                }

                // Get ID from category we just added
                $id = $categoryModel->getState('category.id');
                $category_ids[] = $id;
                $parents[$category['alias']] = $id;
            }
        }
        // Store the category ids in the plugin parameters.
        $this->params->set('categories', implode(',', $category_ids));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installContacts($event, $step) {
        if ($this->checkstep($event, $step, 'contacts')) {
            return;
        }
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installFieldgroups($event, $step) {
        if ($this->checkstep($event, $step, 'fieldgroups')) {
            return;
        }

        // Create a new db object.
        $db    = $this->getDatabase();

        $mvcFactory = $this->getApplication()->bootComponent('com_fields')->getMVCFactory();
        $fieldgroupModel = $mvcFactory->createModel('Group', 'Administrator', ['ignore_request' => true]);
        $ids = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the content articles to be installed from the $language.
        foreach($languages as $language) {
            $file = __DIR__ . "/../../{$language}/fieldgroups.json";
            if (!is_file($file)) {
                continue;
            }
            $fieldgroups_json = file_get_contents($file);
            $fieldgroups = json_decode($fieldgroups_json, true);

            foreach ($fieldgroups as $i => $fieldgroup) {
                if (!$fieldgroupModel->save($fieldgroup)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_FIELDS_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($fieldgroupModel->getError()));

                    $event->addResult($response);
                    return;
                }

                // Get ID from fieldgroup we just added
                $ids[] = $fieldgroupModel->getState('group.id');
            }
        }

        // Store the fieldgroup ids in the plugin parameters.
        $this->params->set('fieldgroups', implode(',', $ids));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installFields($event, $step) {
        if ($this->checkstep($event, $step, 'fields')) {
            return;
        }

        // Create a new db object.
        $db    = $this->getDatabase();

        $mvcFactory = $this->getApplication()->bootComponent('com_fields')->getMVCFactory();
        $fieldModel = $mvcFactory->createModel('Field', 'Administrator', ['ignore_request' => true]);
        $ids = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the fields to be installed from the $language.
        foreach($languages as $language) {
            $file = __DIR__ . "/../../{$language}/fields.json";
            if (!is_file($file)) {
                continue;
            }
            $fields_json = file_get_contents($file);
            $fields = json_decode($fields_json, true);

            // Get a list of fieldgroups to use in creation of fields.
            $query = $db->createQuery();
            $query->select($db->quoteName(['id', 'title']))
                ->from($db->quoteName('#__fields_groups'));
            $db->setQuery($query);
            $rows = $db->loadObjectList();
            $index = [];
            foreach ($rows as $row) {
                $index[$row->title] = $row->id;
            }

            foreach ($fields as $i => $field) {
                $field['group_id'] = $index[$field['parent_fieldgroup']] ?? 0;
                if (!$fieldModel->save($field)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_FIELDS_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($fieldModel->getError()));

                    $event->addResult($response);
                    return;
                }

                // Get ID from field we just added
                $ids[] = $fieldModel->getState('field.id');
            }
        }

        // Store the field ids in the plugin parameters.
        $this->params->set('fields', implode(',', $ids));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installMenus($event, $step) {
        if ($this->checkstep($event, $step, 'menus')) {
            return;
        }

        // Install the Menus
        $mvcFactory = $this->getApplication()->bootComponent('com_menus')->getMVCFactory();
        $menuModel = $mvcFactory->createModel('Menu', 'Administrator', ['ignore_request' => true]);
        $menu_ids = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the fields to be installed from the $language.
        foreach($languages as $language) {
            $file = __DIR__ . "/../../{$language}/menus.json";
            if (!is_file($file)) {
                continue;
            }
            $menus_json = file_get_contents($file);
            $menus = json_decode($menus_json, true);

            foreach ($menus as $menu) {
                if (!$menuModel->save($menu)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_FIELDS_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($menuModel->getError()));

                    $event->addResult($response);
                    return;
                }

                // Get ID from menu we just added
                $id = $menuModel->getState('menu.id');
                $menu_ids[] = $id;
            }
        }

        // Store the menu ids in the plugin parameters.
        $this->params->set('menus', implode(',', $menu_ids));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installMenuitems($event, $step) {
        if ($this->checkstep($event, $step, 'menuitems')) {
            return;
        }

        $db    = $this->getDatabase();
        $access  = (int) $this->getApplication()->get('access', 1);

        $mvcFactory = $this->getApplication()->bootComponent('com_menus')->getMVCFactory();
        $menuitemModel = $mvcFactory->createModel('Item', 'Administrator', ['ignore_request' => true]);
        $menuitem_ids = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the content component id - temporarily set all to com_content
        $component_id = ExtensionHelper::getExtensionRecord('com_content', 'component')->extension_id;

        // Get the fields to be installed from the $language.
        foreach($languages as $language) {
            $file = __DIR__ . "/../../{$language}/menuitems.json";
            if (!is_file($file)) {
                continue;
            }
            $menuitems_json = file_get_contents($file);
            $menuitems = json_decode($menuitems_json, true);

            foreach ($menuitems as $menuitem) {
                $menuitem['id']             = 0;
                $menuitem['component_id']   = $component_id;
                $menuitem['published']      = 1;
                $menuitem['note']           = '';
                $menuitem['img']            = '';
                $menuitem['associations']   = [];
                $menuitem['client_id']      = 0;
                // One item may be set to be the Home item.
                if (empty($menuitem['home'])) {
                    $menuitem['home']           = 0;
                }

                // Set browserNav to default if not set
                if (!isset($menuitem['browserNav'])) {
                    $menuitem['browserNav'] = 0;
                }

                // Set access to default if not set
                if (!isset($menuitem['access'])) {
                    $menuitem['access'] = $access;
                }

                // Set template_style_id to global if not set
                if (!isset($menuitem['template_style_id'])) {
                    $menuitem['template_style_id'] = 0;
                }

                // Set parent_id to root (1) if not set
                if (!isset($menuitem['parent_id'])) {
                    $menuitem['parent_id'] = 1;
                }

                if ($menuitem['type'] == 'Single Article') {
                    // If a single article get the article id from the article alias
                    $query = $db->createQuery();
                    $query
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('#__content'))
                        ->where($db->quoteName('alias') . '=:alias')
                        ->bind(':alias', $menuitem['article-alias'], ParameterType::STRING);
                    $db->setQuery($query);
                    $article_id = $db->loadResult();
                    $menuitem['link'] .= $article_id;
                    unset($menuitem['article-alias']);
                } else if($menuitem['type'] == 'Category Blog') {
                    // Get the category id from the category alias
                    $query = $db->createQuery();
                    $query
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('#__categories'))
                        ->where($db->quoteName('alias') . '=:alias')
                        ->bind(':alias', $menuitem['category-alias'], ParameterType::STRING);
                    $db->setQuery($query);
                    $category_id = $db->loadResult();
                    $menuitem['link'] .= $category_id;
                    unset($menuitem['category-alias']);
                }

                $menuitem['type'] = 'component';

                // If the parent_id is 0 get the parent from its alias
                if (empty($menuitem['parent_id'])) {
                    $query = $db->createQuery();
                    $query
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('#__menu'))
                        ->where($db->quoteName('alias') . '=:alias')
                        ->bind(':alias', $menuitem['parent-alias'], ParameterType::STRING);
                    $db->setQuery($query);
                    $menuitem['parent_id'] = $db->loadResult();
                }

                if (!$menuitemModel->save($menuitem)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_FIELDS_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($menuitemModel->getError()));

                    $event->addResult($response);
                    return;
                }
                // Get ID from menu we just added
                $id = $menuitemModel->getState('item.id');
                $menuitem_ids[] = $id;
            }
        }

        // Store the menuitem ids in the plugin parameters.
        $this->params->set('menuitems', implode(',', $menuitem_ids));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installModules($event, $step) {
        if ($this->checkstep($event, $step, 'modules')) {
            return;
        }

        $db    = $this->getDatabase();
        $access  = (int) $this->getApplication()->get('access', 1);

        // Install the Menus
        $mvcFactory = $this->getApplication()->bootComponent('com_modules')->getMVCFactory();
        $moduleModel = $mvcFactory->createModel('Module', 'Administrator', ['ignore_request' => true]);
        $module_ids = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the fields to be installed from the $language.
        foreach($languages as $language) {
            $file = __DIR__ . "/../../{$language}/modules.json";
            if (!is_file($file)) {
                continue;
            }
            $modules_json = file_get_contents($file);
            $modules = json_decode($modules_json, true);

            foreach ($modules as $module) {
                // Set values which are always the same.
                $module['id']         = 0;
                $module['asset_id']   = 0;
                $module['note']       = '';
                $module['published']  = 1;

                // Get the id of the menu item containing the menu-base-alias
                $query = $db->createQuery();
                $query
                    ->select($db->quoteName('id'))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('alias') . '=:alias')
                    ->bind(':alias', $module['menu-base-alias'], ParameterType::STRING);
                $db->setQuery($query);
                $module['params']['base'] = $db->loadResult();

                if (!isset($module['content'])) {
                    $module['content'] = '';
                }

                if (!isset($module['access'])) {
                    $module['access'] = $access;
                }

                if (!isset($module['showtitle'])) {
                    $module['showtitle'] = 1;
                }

                if (!isset($module['client_id'])) {
                    $module['client_id'] = 0;
                }

                if (!$moduleModel->save($module)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_SAMPLEDATA_BLOG_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($moduleModel->getError()));

                    $event->addResult($response);
                    return;
                }
                // Get ID from menu we just added
                $id = $moduleModel->getState('module.id');
                $module_ids[] = $id;
            }
        }

        // Store the menuitem ids in the plugin parameters.
        $this->params->set('modules', implode(',', $module_ids));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installNewsfeeds($event, $step) {
        if ($this->checkstep($event, $step, 'newsfeeds')) {
            return;
        }
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installTags($event, $step) {
        if ($this->checkstep($event, $step, 'tags')) {
            return;
        }

        // Create a new db object.
        $db    = $this->getDatabase();

        $mvcFactory = $this->getApplication()->bootComponent('com_tags')->getMVCFactory();
        $tagModel = $mvcFactory->createModel('Tag', 'Administrator', ['ignore_request' => true]);
        $ids = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the fields to be installed from the $language.
        foreach($languages as $language) {
            $file = __DIR__ . "/../../{$language}/tags.json";
            if (!is_file($file)) {
                continue;
            }
            $tags_json = file_get_contents($file);
            $tags = json_decode($tags_json, true);

            foreach ($tags as $i => $tag) {
                // if the parent_alias is empty this will be a parent tag
                if (empty($tag['parent_alias'])) {
                    $tag['parent_id'] = 0;
                    if (!$tagModel->save($tag)) {
                        $response            = [];
                        $response['success'] = false;
                        $response['message'] = Text::sprintf('PLG_DEMODATA_TAG_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($tagModel->getError()));

                        $event->addResult($response);
                        return;
                    }
                    // Get ID from tag we just added
                    $ids[] = $tagModel->getState('tag.id');
                } else {
                    // find the parent_id from the parent alias
                    $query = $db->createQuery();
                    $query->select($db->quoteName('id'))
                        ->from($db->quoteName('#__tags'))
                        ->where($db->quoteName('alias') . '=:alias')
                        ->bind(':alias', $tag['parent_alias'], ParameterType::STRING);
                    $db->setQuery($query);
                    $tag['parent_id'] = $db->loadResult();

                    if (!$tagModel->save($tag)) {
                        $response            = [];
                        $response['success'] = false;
                        $response['message'] = Text::sprintf('PLG_DEMODATA_TAG_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($tagModel->getError()));

                        $event->addResult($response);
                        return;
                    }
                    // Get ID from tag we just added
                    $ids[] = $tagModel->getState('tag.id');
                }
            }
        }

        // Store the tag ids in the plugin parameters.
        $this->params->set('tags', implode(',', $ids));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installUsergroups($event, $step) {
        if ($this->checkstep($event, $step, 'usergroups')) {
            return;
        }
        // Create a new db object.
        $db    = $this->getDatabase();

        $mvcFactory = $this->getApplication()->bootComponent('com_users')->getMVCFactory();
        $groupModel = $mvcFactory->createModel('Group', 'Administrator', ['ignore_request' => true]);
        $groupids = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the fields to be installed from the $language.
        foreach($languages as $language) {
            $file = __DIR__ . "/../../{$language}/usergroups.json";
            if (!is_file($file)) {
                continue;
            }
            $groups_json = file_get_contents($file);
            $groups = json_decode($groups_json, true);

            foreach ($groups as $group) {
                // Get the parent id from the parent name.
                $query = $db->createQuery();
                $query->select($db->quoteName('id'))
                    ->from($db->quoteName('#__usergroups'))
                    ->where($db->quoteName('title') . '=:title')
                    ->bind(':title', $group['parent'], ParameterType::STRING);
                $db->setQuery($query);
                $group['parent_id'] = $db->loadResult();
                $group['id'] = 0;

                if (!$groupModel->save($group)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_USER_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($groupModel->getError()));

                    $event->addResult($response);
                    return;
                }

                // Get ID of the group just added.
                $id = $groupModel->getState('group.id');
                $groupids[]  = $id;
            }
        }

        // Save the group ids in the plugin parameters.
        $this->params->set('usergroups', implode(',', $groupids));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installUsers($event, $step) {
        if ($this->checkstep($event, $step, 'users')) {
            return;
        }

        // Create a new db object.
        $db    = $this->getDatabase();

        $mvcFactory = $this->getApplication()->bootComponent('com_users')->getMVCFactory();
        $userModel = $mvcFactory->createModel('User', 'Administrator', ['ignore_request' => true]);
        $ids = [];
        $creds = [];

        // Get the list of languages for installation from the plugin parameters.
        $languages = $this->getLanguages();

        // Get the fields to be installed from the $language.
        foreach($languages as $language) {
            $file = __DIR__ . "/../../{$language}/users.json";
            if (!is_file($file)) {
                continue;
            }
            $users_json = file_get_contents($file);
            $users = json_decode($users_json, true);

            foreach ($users as $i => $user) {

                $user['password'] = $this->generateStrongPassword(12);
                $user['password2'] = $user['password'];

                // Get the parent id from the parent name.
                $query = $db->createQuery();
                $query->select($db->quoteName('id'))
                    ->from($db->quoteName('#__usergroups'))
                    ->where($db->quoteName('title') . '=:title')
                    ->bind(':title', $user['groups'], ParameterType::STRING);
                $db->setQuery($query);
                $user['groups'] = (array) $db->loadResult();

                // Log the saved passwords if required?
                $creds[] = $user['username'] . ':' . $user['password'];

                if (!$userModel->save($user)) {
                    $response            = [];
                    $response['success'] = false;
                    $response['message'] = Text::sprintf('PLG_DEMODATA_USER_STEP_FAILED', $step, $this->getApplication()->getLanguage()->_($userModel->getError()));

                    $event->addResult($response);
                    return;
                }

                // Get ID from user we just added
                $ids[] = $userModel->getState('user.id');
            }
        }

        // Store the user ids in the plugin parameters.
        $this->params->set('users', implode(',', $ids));
        $this->params->set('credentials', implode(', ', $creds));
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installWorkflows($event, $step) {
        if ($this->checkstep($event, $step, 'workflows')) {
            return;
        }
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installWorkflowstages($event, $step) {
        if ($this->checkstep($event, $step, 'workflowstages')) {
            return;
        }
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function installWorkflowtransitions($event, $step) {
        if ($this->checkstep($event, $step, 'workflowtransitions')) {
            return;
        }
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_INSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    /**
     * Uninstall functions in alphabet order
     */

    protected function uninstallArticles($event, $step) {
        $this->loguninstallstep($step, 'articles');

        // Create a new db object.
        $db    = $this->getDatabase();

        // Uninstall the articles.
        $mvcFactory = $this->getApplication()->bootComponent('com_content')->getMVCFactory();

        $articleslist = $this->params->get('articles');

        if (!empty($articleslist)) {
            $articleModel = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);
            $ids = explode(',', $articleslist);

            // The published state needs to be set to -2 to allow deletion.
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query
                ->update($db->quoteName('#__content'))
                ->set($db->quoteName('state') . ' = -2')
                ->where($db->quoteName('id') . ' IN (' . $articleslist . ')');
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (ExecutionFailureException) {
                return false;
            }

            foreach ($ids as $id) {
                // If something went wrong the groups could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$articleModel->delete($id)) {
                    // The group may have been removed manually
                }
            }
        }

        // Remove the category ids from the plugin parameters.
        $this->params->set('articles', '');
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallAssociations($event, $step) {
        $this->loguninstallstep($step, 'associations');
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallBanners($event, $step) {
        $this->loguninstallstep($step, 'banners');
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallCategories($event, $step) {
        $this->loguninstallstep($step, 'categories');

        // Create a new db object.
        $db    = $this->getDatabase();

        $mvcFactory = $this->getApplication()->bootComponent('com_categories')->getMVCFactory();

        $categorieslist = $this->params->get('categories');

        if (!empty($categorieslist)) {
            $categoryModel = $mvcFactory->createModel('Category', 'Administrator', ['ignore_request' => true]);
            $ids = explode(',', $categorieslist);

            // The published state needs to be set to -2 to allow deletion.
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query
                ->update($db->quoteName('#__categories'))
                ->set($db->quoteName('published') . ' = -2')
                ->where($db->quoteName('id') . ' IN (' . $categorieslist . ')');
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (ExecutionFailureException) {
                return false;
            }

            foreach ($ids as $id) {
                // If something went wrong the groups could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$categoryModel->delete($id)) {
                    // The group may have been removed manually
                }
            }
        }

        // Remove the category ids from the plugin parameters.
        $this->params->set('categories', '');
        $this->updateParams($this->params);

        // Unlink images.
        $link = JPATH_SITE . '/images/demodata';
        if (file_exists($link)) {
            unlink($link);
        }

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallContacts($event, $step) {
        $this->loguninstallstep($step, 'contacts');
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallFields($event, $step) {
        $this->loguninstallstep($step, 'fields');

        $mvcFactory = $this->getApplication()->bootComponent('com_fields')->getMVCFactory();

        $fieldslist = $this->params->get('fields');

        if (!empty($fieldslist)) {
            $fieldModel = $mvcFactory->createModel('Field', 'Administrator', ['ignore_request' => true]);
            $ids = explode(',', $fieldslist);
            // The published state needs to be set to -2 to allow deletion.
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query
                ->update($db->quoteName('#__fields'))
                ->set($db->quoteName('state') . ' = -2')
                ->where($db->quoteName('id') . ' IN (' . $fieldslist . ')');
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (ExecutionFailureException) {
                return false;
            }

            foreach ($ids as $id) {
                // If something went wrong the groups could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$fieldModel->delete($id)) {
                    // The field may have been removed manually
                }
            }
        }

        // Remove the fields ids from the plugin parameters.
        $this->params->set('fields', '');
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallFieldgroups($event, $step) {
        $this->loguninstallstep($step, 'fieldgroups');

        $mvcFactory = $this->getApplication()->bootComponent('com_fields')->getMVCFactory();

        $fieldgroupslist = $this->params->get('fieldgroups');

        if (!empty($fieldgroupslist)) {
            $fieldgroupModel = $mvcFactory->createModel('Group', 'Administrator', ['ignore_request' => true]);

            $ids = explode(',', $fieldgroupslist);

            // The published state needs to be set to -2 to allow deletion.
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query
                ->update($db->quoteName('#__fields_groups'))
                ->set($db->quoteName('state') . ' = -2')
                ->where($db->quoteName('id') . ' IN (' . $fieldgroupslist . ')');
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (ExecutionFailureException) {
                return false;
            }

            foreach ($ids as $id) {
                // If something went wrong the groups could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$fieldgroupModel->delete($id)) {
                    // The group may have been removed manually
                }
            }
        }

        // Remove the tag ids from the plugin parameters.
        $this->params->set('fieldgroups', '');
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallMenuitems($event, $step) {
        $this->loguninstallstep($step, 'menuitems');

        $mvcFactory = $this->getApplication()->bootComponent('com_menus')->getMVCFactory();

        $menuitemslist = $this->params->get('menuitems');

        if (!empty($menuitemslist)) {
            $menuitemModel = $mvcFactory->createModel('Item', 'Administrator', ['ignore_request' => true]);
            $ids = explode(',', $menuitemslist);

            // The published state needs to be set to -2 to allow deletion.
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query
                ->update($db->quoteName('#__menu'))
                ->set($db->quoteName('published') . ' = -2')
                ->where($db->quoteName('id') . ' IN (' . $menuitemslist . ')');
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (ExecutionFailureException) {
                return false;
            }

            foreach ($ids as $id) {
                // If something went wrong the groups could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$menuitemModel->delete($id)) {
                    // The items may have been removed manually
                }
            }
        }
        // Remove the menuitems ids from the plugin parameters.
        $this->params->set('menuitems', '');
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallMenus($event, $step) {
        $this->loguninstallstep($step, 'menuitems');

        $mvcFactory = $this->getApplication()->bootComponent('com_menus')->getMVCFactory();

        $menuslist = $this->params->get('menus');

        if (!empty($menuslist)) {
            $menuModel = $mvcFactory->createModel('Menu', 'Administrator', ['ignore_request' => true]);
            $ids = explode(',', $menuslist);
            foreach ($ids as $id) {
                // If something went wrong the groups could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$menuModel->delete($id)) {
                    // The menu may have been removed manually
                }
            }
        }
        // Remove the menu ids from the plugin parameters.
        $this->params->set('menus', '');
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallModules($event, $step) {
        $this->loguninstallstep($step, 'modules');

        $mvcFactory = $this->getApplication()->bootComponent('com_modules')->getMVCFactory();

        $moduleslist = $this->params->get('modules');

        if (!empty($moduleslist)) {
            $moduleModel = $mvcFactory->createModel('Module', 'Administrator', ['ignore_request' => true]);
            $ids = explode(',', $moduleslist);

            // The published state needs to be set to -2 to allow deletion.
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query
                ->update($db->quoteName('#__modules'))
                ->set($db->quoteName('published') . ' = -2')
                ->where($db->quoteName('id') . ' IN (' . $moduleslist . ')');
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (ExecutionFailureException) {
                return false;
            }

            foreach ($ids as $id) {
                // If something went wrong the groups could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$moduleModel->delete($id)) {
                    // The items may have been removed manually
                }
            }
        }

        // Remove the module ids from the plugin parameters.
        $this->params->set('modules', '');
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallNewsfeeds($event, $step) {
        $this->loguninstallstep($step, 'newsfeeds');
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallTags($event, $step) {
        $this->loguninstallstep($step, 'tags');

        $mvcFactory = $this->getApplication()->bootComponent('com_tags')->getMVCFactory();

        if (!empty($this->params->get('tags'))) {
            $tagModel = $mvcFactory->createModel('Tag', 'Administrator', ['ignore_request' => true]);
            $tagslist = $this->params->get('tags');
            $ids = explode(',', $tagslist);
            $tagslist = "({$tagslist})";
            // The published state needs to be set to -2 to allow deletion.
            $db    = $this->getDatabase();
            $query = $db->createQuery();
            $query
                ->update($db->quoteName('#__tags'))
                ->set($db->quoteName('published') . ' = -2')
                ->where($db->quoteName('id') . ' IN ' . $tagslist);
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (ExecutionFailureException) {
                return false;
            }

            foreach ($ids as $id) {
                // If something went wrong the tags could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$tagModel->delete($id)) {
                }
            }
        }

        // Remove the tag ids from the plugin parameters.
        $this->params->set('tags', '');
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallUsers($event, $step) {
        $this->loguninstallstep($step, 'users');

        $mvcFactory = $this->getApplication()->bootComponent('com_users')->getMVCFactory();

        if (!empty($this->params->get('users'))) {
            $userModel = $mvcFactory->createModel('User', 'Administrator', ['ignore_request' => true]);
            $ids = explode(',', $this->params->get('users'));
            foreach ($ids as $id) {
                // If something went wrong the user could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$userModel->delete($id)) {
                    // The user may have been deleted manually
                }
            }
        }

        // Remove the user ids from the plugin parameters.
        $this->params->set('users', '');
        $this->params->set('credentials', '');

        if (!empty($this->params->get('usergroups'))) {
            $groupModel = $mvcFactory->createModel('Group', 'Administrator', ['ignore_request' => true]);
            $ids = explode(',', $this->params->get('usergroups'));
            foreach ($ids as $id) {
                // If something went wrong the groups could be empty.
                if (empty($id)) {
                    continue;
                }
                if (!$groupModel->delete($id)) {
                    // The group may have been removed manually
                }
            }
        }

        $this->loguninstallstep($step, 'usergroups');

        // Remove the group ids from the plugin parameters.
        $this->params->set('usergroups', '');
        $this->updateParams($this->params);

        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallWorkflows($event, $step) {
        $this->loguninstallstep($step, 'workflows');
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallWorkflowstages($event, $step) {
        $this->loguninstallstep($step, 'workflowstages');
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }

    protected function uninstallWorkflowtransitions($event, $step) {
        $this->loguninstallstep($step, 'workflowtransitions');
        $response            = [];
        $response['success'] = true;
        $response['message'] = $this->getApplication()->getLanguage()->_('PLG_DEMODATA_FEATURES101_STEP' . $step . '_UNINSTALL_SUCCESS');

        $event->setArgument('result', [$response]);
    }


    // https://gist.github.com/tylerhall/521810
    // Generates a strong password of N length containing at least one lower case letter,
    // one uppercase letter, one digit, and one special character. The remaining characters
    // in the password are chosen at random from those four sets.
    //
    // The available characters in each set are user friendly - there are no ambiguous
    // characters such as i, l, 1, o, 0, etc. This, coupled with the $add_dashes option,
    // makes it much easier for users to manually type or speak their passwords.
    //
    // Note: the $add_dashes option will increase the length of the password by
    // floor(sqrt(N)) characters.

    private function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds')
    {
        $sets = array();
        if(strpos($available_sets, 'l') !== false)
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if(strpos($available_sets, 'u') !== false)
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if(strpos($available_sets, 'd') !== false)
            $sets[] = '23456789';
        if(strpos($available_sets, 's') !== false)
            $sets[] = '!@#$%&*?';

        $all = '';
        $password = '';
        foreach($sets as $set)
        {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++)
            $password .= $all[array_rand($all)];

        $password = str_shuffle($password);

        if(!$add_dashes)
            return $password;

        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while(strlen($password) > $dash_len)
        {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }
}
