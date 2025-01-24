<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\JsonApiController;
use CoursewareFlow\models\Flow;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UnitFlowsDelete extends JsonApiController
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $user = $this->getUser($request);
        $resource = \Courseware\Unit::find($args['id']);

        if (!$resource) {
            throw new RecordNotFoundException();
        }

        if(!Authority::canDeleteUnitFlows($user, $resource)) {
            throw new AuthorizationFailedException();
        }

        $flows = Flow::findBySQL('source_unit_id = ?', [$resource->id]);
        $clonedFlows = array_map(fn($obj) => clone $obj, $flows);

        foreach ($flows as $flow) {
            $flow->delete();
        }

        return $this->getPaginatedContentResponse($clonedFlows, count($clonedFlows));
    }
}