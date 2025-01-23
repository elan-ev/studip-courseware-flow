<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Routes\ValidationTrait;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\JsonApiController;

use CoursewareFlow\models\CourseTool;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlowsCreate extends JsonApiController
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

        if (!$source_unit) {
            throw new RecordNotFoundException();
        }

        if (!Authority::canCreateFlow($user, $source_unit->course)) {
            throw new AuthorizationFailedException();
        }

        $resource = $this->createFlow($json, $source_unit, $target_course, $user);

        return $this->getCreatedResponse($resource);
    }

    protected function validateResourceDocument($json, $data)
    {
        if (!self::arrayHas($json, 'data')) {
            return 'Missing `data` member at document´s top level.';
        }
        if (!self::arrayHas($json, 'data.attributes.tool-id')) {
            return 'New document must not have an `tool-id`.';
        }
        if (!self::arrayHas($json, 'data.attributes.course-id')) {
            return 'New document must not have an `course-id`.';
        }
    }

    private function createFlow(array $json, $source_unit, $target_course, $user): Flow
    {
        $source_course = $source_unit->course;

        $target_unit = $source_unit::copy($user, $target_course->id, $target_course->range_type);
        //TODO: create own copy function to get mapping information

        $flow = Flow::create([
            'source_course_id' => $source_course->id,
            'source_unit_id' => $source_unit->id,
            'target_course_id' => $target_course->id,
            'target_unit_id' => $target_unit->id,
            // 'structural_elements_map' => $source_unit->structural_elements_map,
            // 'container_map' => $source_unit->container_map,
            // 'blocks_map' => $source_unit->blocks_map,
            'active' => true,
            'auto_sync' => false,
        ]);

        return $flow;
    }

    private function getUnit(Request $json): ?\Courseware\Unit
    {
        $unit_id = self::arrayGet($json, 'data.attributes.source-unit-id');

        return \Courseware\Unit::find($unit_id);
    }

    private function getTargetCourse($json): ?\Course
    {
        $course_id = self::arrayGet($json, 'data.attributes.target-course-id');

        return \Course::find($course_id);
    }
}