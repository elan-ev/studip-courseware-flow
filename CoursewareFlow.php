<?php
require_once __DIR__.'/bootstrap.php';

use JsonApi\Contracts\JsonApiPlugin;
use CoursewareFlow\JsonApi\Routes;
use CoursewareFlow\JsonApi\Schemas;

class CoursewareFlow extends StudIPPlugin implements SystemPlugin, JsonApiPlugin
{
    use Routes;
    use Schemas;
    public function __construct()
    {
        parent::__construct();

        PageLayout::addScript($this->getPluginUrl() . '/dist/courseware-flow.js', [
            'type' => 'module',
            'rel'  => 'preload',
        ]);

        PageLayout::addStylesheet($this->getPluginUrl() . '/dist/courseware-flow.css');
    }

    public function perform($unconsumedPath)
    {
        // This require must be here, to prevent vendor version conflicts.
        require_once __DIR__ . '/vendor/autoload.php';

        $trails_root  = $this->getPluginPath() . '/app';

        $dispatcher         = new Trails_Dispatcher($trails_root,
            rtrim(PluginEngine::getURL($this, [], ''), '/'),
            'index');

        $dispatcher->current_plugin = $this;
        $dispatcher->dispatch($unconsumedPath);
    }

    public function getPluginName()
    {
        return 'CoursewareFlow';
    }

    public function getInfoTemplate($courseId)
    {
        return null;
    }


}