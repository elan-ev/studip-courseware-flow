<?php
/**
 * UnitFlowsSync
 *
 * JSON-API-Route zum Synchronisieren aller aktiven und "idle" Flows einer bestimmten Courseware-Unit.
 * Prüft die Berechtigung des Benutzers, führt die Synchronisation durch und liefert die Unit zurück.
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