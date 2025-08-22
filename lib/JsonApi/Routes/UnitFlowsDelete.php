<?php
/**
 * UnitFlowsDelete
 *
 * JSON-API-Route zum Löschen aller Flows einer bestimmten Courseware-Unit. 
 * Optional können dabei auch die Ziel-Units, zugehörige Ordner und VIPS-Zuweisungen gelöscht werden.
 * Prüft Berechtigungen des Benutzers und validiert die eingehenden JSON-Daten.
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
use JsonApi\Routes\ValidationTrait;
use CoursewareFlow\Models\Flow;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UnitFlowsDelete extends JsonApiController
{
    use ValidationTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $user = $this->getUser($request);
        $resource = \Courseware\Unit::find($args['id']);

        $json = $this->validate($request, $resource);

        $with_units = $json['data']['attributes']['with-units'];

        if (!$resource) {
            throw new RecordNotFoundException();
        }

        if(!Authority::canDeleteUnitFlows($user, $resource)) {
            throw new AuthorizationFailedException();
        }

        $flows = Flow::findBySQL('source_unit_id = ?', [$resource->id]);
        $clonedFlows = array_map(fn($obj) => clone $obj, $flows);

        foreach ($flows as $flow) {
            if ($with_units) {
                $target_unit = \Courseware\Unit::find($flow->target_unit_id);
                if ($target_unit) {
                    $target_unit->delete();
                }
                $target_folder = \Folder::find($flow->target_folder_id);
                if ($target_folder) {
                    $target_folder->delete();
                }
                $vips_assignment_ids = json_decode($flow->vips_map, true) ?? [];
                $vips_assignment_ids = array_values($vips_assignment_ids);
                foreach ($vips_assignment_ids as $vips_assignment_id) {
                    $vips_assignment = \VipsAssignment::find($vips_assignment_id);
                    if ($vips_assignment) {
                        $vips_assignment->delete();
                    }
                }
            } else {
                $target_element = \Courseware\Unit::find($flow->target_unit_id)->structural_element;
                if ($target_element) {
                    $target_element->title = str_replace('🔄 ', '', $target_element->title);
                    $target_element->store();
                }
            }
            $source_element = \Courseware\Unit::find($flow->source_unit_id)->structural_element;
            if ($source_element) {
                $source_element->title = str_replace('🔄 ', '', $source_element->title);
                $source_element->store();
            }

            $flow->delete();
        }

        return $this->getPaginatedContentResponse($clonedFlows, count($clonedFlows));
    }

    protected function validateResourceDocument($json, $data)
    {
        if (!self::arrayHas($json, 'data')) {
            return 'Missing `data` member at document´s top level.';
        }
    }
}