<?php
/**
 * CoursewareFlow
 *
 * Stud.IP-Plugin zum Kopieren und Synchronisieren von Courseware-Lernmaterial zwischen Veranstaltungen.
 *
 * @package   CoursewareFlow
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 * @link      https://elan-ev.de
 */

require_once __DIR__ . '/bootstrap.php';

use JsonApi\Contracts\JsonApiPlugin;
use CoursewareFlow\JsonApi\Routes;
use CoursewareFlow\JsonApi\Schemas;

class CoursewareFlow extends StudIPPlugin implements StandardPlugin, SystemPlugin, JsonApiPlugin
{
    use Routes;
    use Schemas;
    public function __construct()
    {
        parent::__construct();
        PageLayout::addScript($this->getPluginUrl() . '/dist/courseware-flow.js', [
            'type' => 'module',
            'rel' => 'preload',
        ]);

        PageLayout::addStylesheet($this->getPluginUrl() . '/dist/courseware-flow.css');

        $this->loadVipsPlugin();

    }

    public function perform($unconsumedPath)
    {
        // This require must be here, to prevent vendor version conflicts.
        require_once __DIR__ . '/vendor/autoload.php';

        $trails_root = $this->getPluginPath() . '/app';

        $dispatcher = new Trails_Dispatcher(
            $trails_root,
            rtrim(PluginEngine::getURL($this, [], ''), '/'),
            'index'
        );

        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumedPath);
    }

    public function getPluginName()
    {
        return 'Courseware Flow';
    }

    public function getInfoTemplate($courseId)
    {
        return null;
    }

    public function getTabNavigation($courseId)
    {
        $tabs = array();

        $nav = new Navigation($this->getPluginName(), PluginEngine::getURL($this, [], 'index'));
        $tabs['coursewareflow'] = $nav;
        $nav->addSubNavigation('index', new Navigation(
            'Übersicht',
            PluginEngine::getURL($this, [], 'index')
        ));
        return $tabs;
    }

    public function getIconNavigation($courseId, $last_visit, $user_id)
    {
        return null;
    }

    private function loadVipsPlugin()
    {
        $plugin_vips = \PluginManager::getInstance()->getPlugin('VipsPlugin');

        if ($plugin_vips === null) {
            return;
        }

        require_once 'public/' . $plugin_vips->getPluginPath() . '/lib/vips_common.inc.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/Exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/sc_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/mc_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/mco_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/lt_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/tb_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/cloze_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/rh_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/seq_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/me_exercise.php';
        require_once 'public/' . $plugin_vips->getPluginPath() . '/exercises/lti_exercise.php';
    }


}