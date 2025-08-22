<?php
/**
 * FlowShow
 *
 * JSON-API-Route zum Abrufen eines einzelnen Flows. 
 * Prüft die Berechtigungen des Benutzers und liefert die Flow-Ressource zurück.
 *
 * @package   CoursewareFlow\JsonApi\Routes
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 * @link      https://elan-ev.de
 */

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\JsonApiController;
use CoursewareFlow\Models\Flow;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlowShow extends JsonApiController
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $resource = Flow::find($args['id']);
        if (!$resource) {
            throw new RecordNotFoundException();
        }
        $user = $this->getUser($request);
        if (!Authority::canShowFlow($user, $resource)) {
            throw new AuthorizationFailedException();
        }

        return $this->getContentResponse($resource);
    }
}