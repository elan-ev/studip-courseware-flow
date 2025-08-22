<?php
/**
 * FlowCreate
 *
 * JSON-API-Route zum Erstellen eines neuen Flows zwischen zwei Courseware-Einheiten.
 * Prüft die Berechtigungen des Benutzers, validiert das Request-Dokument
 * und erstellt die Ziel-Unit mit allen zugehörigen Strukturelementen, Containern, Blocks und Dateien.
 *
 * @package   CoursewareFlow\JsonApi\Routes
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 * @link      https://elan-ev.de
 */

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Routes\ValidationTrait;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\JsonApiController;

use CoursewareFlow\Models\Flow;
use CoursewareFlow\Helpers\CopyHelper;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlowCreate extends JsonApiController
{
    use ValidationTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $json = $this->validate($request);
        $user = $this->getUser($request);
        $source_unit = $this->getUnit($json);
        $target_course = $this->getTargetCourse($json);

        if (!$target_course) {
            throw new RecordNotFoundException();
        }

        if (!Authority::canCreateFlow($user, $source_unit->course)) {
            throw new AuthorizationFailedException();
        }

        $resource = $this->createFlow( $source_unit, $target_course, $user);

        return $this->getCreatedResponse($resource);
    }

    protected function validateResourceDocument($json, $data)
    {
        if (!self::arrayHas($json, 'data')) {
            return 'Missing `data` member at document´s top level.';
        }
        if (!self::arrayHas($json, 'data.attributes.target_course_id')) {
            return 'New document must not have an `target_course_id`.';
        }
        if (!self::arrayHas($json, 'data.attributes.source_unit_id')) {
            return 'New document must not have an `source_unit_id`.';
        }
    }

    private function createFlow($source_unit, $target_course, $user): Flow
    {
        $source_course = $source_unit->course;

        $flow = Flow::create([
            'source_course_id' => $source_course->id,
            'source_unit_id' => $source_unit->id,
            'target_course_id' => $target_course->id,
            'target_unit_id' => '',
            'status' => 'running',
            'active' => true,
            'auto_sync' => true,
        ]);

        $this->createFlowTargetUnit($flow, $source_unit, $user);

        return $flow;
    }

    private function createFlowTargetUnit(Flow $flow, $source_unit, $user): void
    {
        $target = CopyHelper::copyUnit($user, $source_unit, $flow->target_course_id);

        $flow->target_unit_id = $target['target_unit']->id;
        $flow->status = 'idle';
        $flow->structural_elements_map = json_encode($target['structural_elements_map']);
        $flow->structural_elements_image_map = json_encode($target['structural_elements_image_map']);
        $flow->container_map = json_encode($target['container_map']);
        $flow->blocks_map = json_encode($target['blocks_map']);
        $flow->files_map = json_encode($target['files_map']);
        $flow->folders_map = json_encode($target['folders_map']);
        $flow->target_folder_id = $target['target_folder_id'];
        $flow->vips_map = json_encode($target['vips_map']);
        $flow->sync_date = time();
        $flow->store();
    }

    private function getUnit($json): ?\Courseware\Unit
    {
        $unit_id = self::arrayGet($json, 'data.attributes.source_unit_id');

        return \Courseware\Unit::find($unit_id);
    }

    private function getTargetCourse($json): ?\Course
    {
        $course_id = self::arrayGet($json, 'data.attributes.target_course_id');
        $target_course = \Course::find($course_id);

        return $target_course;
    }
}