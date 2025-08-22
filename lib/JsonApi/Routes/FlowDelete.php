<?php
/**
 * FlowDelete
 *
 * JSON-API-Route zum Löschen eines bestehenden Flows. 
 * Prüft die Berechtigungen des Benutzers, validiert das Request-Dokument 
 * und löscht optional die zugehörige Ziel-Unit, deren Dateien und VIPS-Zuweisungen.
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

class FlowDelete extends JsonApiController
{
    use ValidationTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        $user = $this->getUser($request);
        $resource = Flow::find($args['id']);

        if (!$resource) {
            throw new RecordNotFoundException();
        }

        if(!Authority::canDeleteFlow($user, $resource)) {
            throw new AuthorizationFailedException();
        }

        $json = $this->validate($request, $resource);

        $with_unit = $json['data']['attributes']['with-unit'];

        if ($with_unit) {
            $target_unit = \Courseware\Unit::find($resource->target_unit_id);
                if ($target_unit) {
                    $target_unit->delete();
                }
                $target_folder = \Folder::find($resource->target_folder_id);
                if ($target_folder) {
                    $target_folder->delete();
                }
                $vips_assignment_ids = json_decode($resource->vips_map, true) ?? [];
                $vips_assignment_ids = array_values($vips_assignment_ids);
                foreach ($vips_assignment_ids as $vips_assignment_id) {
                    $vips_assignment = \VipsAssignment::find($vips_assignment_id);
                    if ($vips_assignment) {
                        $vips_assignment->delete();
                    }
                }
        }
        $source_element = \Courseware\Unit::find($resource->source_unit_id)->structural_element;
        if ($source_element) {
            $source_element->title = str_replace('🔄 ', '', $source_element->title);
            $source_element->store();
        }
        
        $resource->delete();

        return $this->getCodeResponse(204);
    }

    protected function validateResourceDocument($json, $data)
    {
        if (!self::arrayHas($json, 'data')) {
            return 'Missing `data` member at document´s top level.';
        }
    }
}