<?php

namespace CoursewareFlow\JsonApi;

trait Routes
{
    public function registerAuthenticatedRoutes(\Slim\Routing\RouteCollectorProxy $group)
    {
        $group->get('/courseware-flows', Routes\FlowsIndex::class);
        $group->get('/courseware-flows/{id}', Routes\FlowShow::class);
        $group->get('/units/{id}/courseware-flows', Routes\UnitFlowsIndex::class);
        $group->get('/courses/{id}/courseware-flows', Routes\CourseFlowsIndex::class);

        $group->post('/courseware-flows', Routes\FlowCreate::class);
        $group->post('/courseware-flows/create-flows', Routes\FlowsCreate::class);
        
        $group->post('/courseware-flows/{id}/sync', Routes\FlowSync::class);
        $group->patch('/courseware-flows/{id}', Routes\FlowUpdate::class);    
        $group->post('/courseware-flows/{id}/delete', Routes\FlowDelete::class);
        $group->post('/units/{id}/courseware-flows', Routes\UnitFlowsDelete::class);
        $group->post('/units/{id}/courseware-flows/sync', Routes\UnitFlowsSync::class);
    }
    public function registerUnauthenticatedRoutes(\Slim\Routing\RouteCollectorProxy $group)
    {
    }
}