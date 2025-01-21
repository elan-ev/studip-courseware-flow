<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\JsonApiController;
use CoursewareFlow\models\Flow;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlowsIndex extends JsonApiController
{
    protected $allowedPagingParameters = ['offset', 'limit'];

    public function __invoke(Request $request, Response $response, $args)
    {
        $user = $this->getUser($request);
        $isRoot = $GLOBALS['perm']->have_perm('root', $user->id);
        if(!Authority::canIndexFlows($user)) {
            throw new AuthorizationFailedException();
        }
        list($offset, $limit) = $this->getOffsetAndLimit();
        if ($isRoot) {
            $resources = Flow::findBySQL('1 ORDER BY mkdate LIMIT ? OFFSET ?', [$limit, $offset]);
        } else {
            $resources = Flow::findBySQL('active = 1 ORDER BY mkdate LIMIT ? OFFSET ?', [$limit, $offset]);
        }
        

        return $this->getPaginatedContentResponse($resources, count($resources));
    
    }
}