<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\JsonApiController;
use CoursewareFlow\models\Flow;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlowDelete extends JsonApiController
{
    public function __invoke(Request $request, Response $response, $args)
    {

        $resource = Flow::find($args['id']);

        if (!$resource) {
            throw new RecordNotFoundException();
        }

        $user = $this->getUser($request);

        if(!Authority::canDeleteFlow($user, $resource)) {
            throw new AuthorizationFailedException();
        }
        
        $resource->delete();

        return $this->getCodeResponse(204);
    }
}