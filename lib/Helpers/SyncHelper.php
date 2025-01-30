<?php

namespace CoursewareFlow\Helpers;

use \Courseware\Container;
use \Courseware\StructuralElement;
use \Courseware\Unit;
use CoursewareFlow\Models\Flow;
class SyncHelper
{
    public static function syncFlow(Flow $flow): Flow
    {
        $flow->status = Flow::STATUS_SYNCING;
        $flow->store();

        self::syncUnit($flow);

        $flow->status = Flow::STATUS_IDLE;
        $flow->store();
        
        return $flow;
    } 
    public static function syncUnit(&$flow): void
    {
        $source_unit = Unit::find($flow->source_unit_id);
        if (!$source_unit) {
            throw new \Exception('Source unit not found');
        }

        $target_unit = Unit::find($flow->target_unit_id);

        if ($target_unit) {
            //TODO: update target_unit with changes from source_unit
            sleep(30); // simulate sync delay
            // TODO: sync structural element -> root of unit
        } else {
            $target_data = copyHelper::copyUnit($source_unit, $flow->target_course_id);
            // TODO: store target_data in flow
        }
    }

    private static function syncStructuralElements(Flow &$flow, $target_element, $source_element): void
    {

    }

    private static function syncContainers($source_course_id, $target_course_id): void
    {

    }

    private static function syncBlocks($source_course_id, $target_course_id): void
    {

    }

}