<?php
/**
 * FlowSync
 *
 * JSON-API-Route zum Synchronisieren eines bestehenden Flows zwischen Kursen.
 * Prüft die Berechtigungen des Benutzers und verwendet den SyncHelper, um den Flow zu aktualisieren.
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

        $updated_resource = $this->syncFlow($resource, $user);

        return $this->getContentResponse($updated_resource);
    }

    private function syncFlow(Flow $resource, $user) : Flow
    {
        return SyncHelper::syncFlow($resource, $user);
    }
}