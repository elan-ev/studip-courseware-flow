<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\JsonApiController;
use JsonApi\Routes\ValidationTrait;
use CoursewareFlow\Models\Flow;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlowUpdate extends JsonApiController
{
    use ValidationTrait;

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
        $json = $this->validate($request, $resource);
        $updated_resource = $this->updateFlow($resource, $json);

        return $this->getContentResponse($updated_resource);
    }

    protected function validateResourceDocument($json, $data)
    {
        if (!self::arrayHas($json, 'data')) {
            return 'Missing `data` member at document´s top level.';
        }

        if (!self::arrayHas($json, 'data.id')) {
            return 'Document must have an `id`.';
        }
    }

    private function updateFlow(Flow $resource, array $json) : Flow
    {

        if (isset($json['data']['attributes']['active'])) {
            $resource->active = $json['data']['attributes']['active'];
        }

        if (isset($json['data']['attributes']['auto_sync'])) {
            $resource->auto_sync = $json['data']['attributes']['auto_sync'];
        }

        $resource->store();

        return $resource;
    }
}