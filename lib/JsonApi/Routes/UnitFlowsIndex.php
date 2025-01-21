<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\JsonApiController;
use CoursewareFlow\models\Flow;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UnitFlowsIndex extends JsonApiController
{
    protected $allowedPagingParameters = ['offset', 'limit'];

    protected $allowedIncludePaths = ['unit'];

    public function __invoke(Request $request, Response $response, $args)
    {
        $user = $this->getUser($request);
        $unit = \Courseware\Unit::find($args['id']);

        if (!$unit) {
            throw new RecordNotFoundException();
        }

        if (!Authority::canIndexUnitFlows($user, $unit->course)) {
            throw new AuthorizationFailedException();
        }

        list($offset, $limit) = $this->getOffsetAndLimit();

        $resources = Flow::findBySQL('source_unit_id = ? ORDER BY mkdate LIMIT ? OFFSET ?', [$unit->id, $limit, $offset]);

        return $this->getPaginatedContentResponse($resources, count($resources));
    }
}