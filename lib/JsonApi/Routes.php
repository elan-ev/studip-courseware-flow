<?php

namespace CoursewareFlow\JsonApi;

trait Routes
{
    public function registerAuthenticatedRoutes(\Slim\Routing\RouteCollectorProxy $group)
    {
        $group->get('/courseware-flows', Routes\FlowsIndex::class);
        $group->get('/courseware-flows/{id}', Routes\FlowShow::class);
        $group->get('/units/{id}/courseware-flows', Routes\UnitFlowsIndex::class);

        $group->post('/courseware-flows', Routes\FlowCreate::class);
        $group->post('/courseware-flows/create-flows', Routes\FlowsCreate::class);

        $group->patch('/courseware-flows/{id}', Routes\FlowUpdate::class);

        $group->delete('/courseware-flows/{id}', Routes\FlowDelete::class);
        //TODO: Batch delete
    }
    public function registerUnauthenticatedRoutes(\Slim\Routing\RouteCollectorProxy $group)
    {
    }
}