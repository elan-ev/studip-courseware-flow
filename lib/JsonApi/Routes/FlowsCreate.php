<?php

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Routes\ValidationTrait;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\JsonApiController;

use CoursewareFlow\Models\Flow;
use CoursewareFlow\Helpers\CopyHelper;

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
        $target_courses = $this->getTargetCourses($json);

        if (!$source_unit) {
            throw new RecordNotFoundException();
        }

        if (!Authority::canCreateFlow($user, $source_unit->course)) {
            throw new AuthorizationFailedException();
        }

        $resources = $this->createFlows($json, $source_unit, $target_courses, $user);

        return $this->getPaginatedContentResponse($resources, count($resources));
    }

    protected function validateResourceDocument($json, $data)
    {
        if (!self::arrayHas($json, 'data')) {
            return 'Missing `data` member at document´s top level.';
        }
        if (!self::arrayHas($json, 'data.attributes.source-unit-id')) {
            return 'New document must have an `source-unit-id`.';
        }
        if (!self::arrayHas($json, 'data.attributes.target-course-ids')) {
            return 'New document must have `target-course-ids`.';
        }
    }

    private function createFlows(array $json, $source_unit, $target_courses, $user): array
    {
        $source_course = $source_unit->course;

        $flows = [];

        foreach ($target_courses as $target_course) {
            $flow = Flow::create([
                'source_course_id' => $source_course->id,
                'source_unit_id' => $source_unit->id,
                'target_course_id' => $target_course->id,
                'target_unit_id' => null,
                'status' => 'running',
                'active' => true,
                'auto_sync' => false,
            ]);
            $flows[] = $flow;
        }

        foreach ($flows as $flow) {
            $this->createFlowTargetUnit($flow, $source_unit, $user);
        }

        return $flows;
    }

    private function createFlowTargetUnit(Flow $flow, $source_unit, $user): void
    {
        $target_unit = CopyHelper::copyUnit($user, $source_unit, $flow->target_course_id);

        $flow->target_unit_id = $target_unit->id;
        $flow->status = 'idle';

                // 'structural_elements_map' => $source_unit->structural_elements_map,
                // 'container_map' => $source_unit->container_map, 
                // 'blocks_map' => $source_unit->blocks_map,
                // 'folders_map' => $source_unit->folders_map,
                // 'files_map' => $source_unit->files_map,
        $flow->store();
    }

    // private function createFlowForCourse($source_unit, $source_course, $target_course, $user): ?Flow
    // {
    //     $flow = Flow::create([
    //         'source_course_id' => $source_course->id,
    //         'source_unit_id' => $source_unit->id,
    //         'target_course_id' => $target_course->id,
    //         'target_unit_id' => null,
    //         // 'structural_elements_map' => $source_unit->structural_elements_map,
    //         // 'container_map' => $source_unit->container_map,
    //         // 'blocks_map' => $source_unit->blocks_map,
    //         // 'folders_map' => $source_unit->folders_map,
    //         // 'files_map' => $source_unit->files_map,
    //         'status' => 'running',
    //         'active' => true,
    //         'auto_sync' => false,
    //     ]);

    //     $target_unit = CopyHelper::copyUnit($user, $source_unit, $target_course);

    //     $flow->target_unit_id = $target_unit->id;
    //     $flow->status = 'idle';
    //     $flow->store();

    //     return $flow;
    // }


    private function getUnit($json): ?\Courseware\Unit
    {
        $unit_id = self::arrayGet($json, 'data.attributes.source-unit-id');

        return \Courseware\Unit::find($unit_id);
    }

    private function getTargetCourses($json): array
    {
        $course_ids = self::arrayGet($json, 'data.attributes.target-course-ids');
        $courses = [];

        foreach ($course_ids as $course_id) {
            $target_course = \Course::find($course_id);
            if (!$target_course) {
                throw new RecordNotFoundException();
            }
            $courses[] = $target_course;
        }
        
        return $courses;
    }
}