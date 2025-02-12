<?php

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