<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\JsonApiController;
use CoursewareFlow\Models\Flow;
use CoursewareFlow\Helpers\SyncHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UnitFlowsSync extends JsonApiController
{
    public function __invoke(Request $request, Response $response, $args)
    {
        
        $user = $this->getUser($request);
        $resource = \Courseware\Unit::find($args['id']);

        if (!$resource) {
            throw new RecordNotFoundException();
        }
        $user = $this->getUser($request);
        if (!Authority::canUpdateUnitFlows($user, $resource)) {
            throw new AuthorizationFailedException();
        }

        $flows = Flow::findBySQL('active = 1 AND status = ? AND source_unit_id = ?', ['idle', $resource->id]);

        foreach ($flows as $flow) {
            $this->syncFlow($flow, $user);
        }


        return $this->getContentResponse($resource);
    }

    private function syncFlow(Flow $resource, $user) : Flow
    {
        return SyncHelper::syncFlow($resource, $user);
    }
}