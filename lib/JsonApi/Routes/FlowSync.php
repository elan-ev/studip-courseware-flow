<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\JsonApiController;
use JsonApi\Routes\ValidationTrait;
use CoursewareFlow\Models\Flow;
use CoursewareFlow\Helpers\SyncHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlowSync extends JsonApiController
{
    public function __invoke(Request $request, Response $response, $args)
    {
        
        $resource = Flow::find($args['id']);
        if (!$resource) {
            throw new RecordNotFoundException();
        }
        $user = $this->getUser($request);
        if (!Authority::canUpdateFlow($user, $resource)) {
            throw new AuthorizationFailedException();
        }

        $updated_resource = $this->syncFlow($resource);

        return $this->getContentResponse($updated_resource);
    }

    private function syncFlow(Flow $resource) : Flow
    {
        return SyncHelper::syncFlow($resource);
    }
}