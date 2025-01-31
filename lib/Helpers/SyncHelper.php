<?php

namespace CoursewareFlow\Helpers;

use \Courseware\Container;
use \Courseware\StructuralElement;
use \Courseware\Unit;
use CoursewareFlow\Models\Flow;
class SyncHelper
{
    public static function syncFlow(Flow $flow, $user): Flow
    {
        // $flow->status = Flow::STATUS_SYNCING;
        // $flow->store();

        self::syncUnit($flow, $user);

        // $flow->status = Flow::STATUS_IDLE;
        // $flow->store();
        
        return $flow;
    } 
    public static function syncUnit(&$flow, $user): void
    {
        $source_unit = Unit::find($flow->source_unit_id);
        if (!$source_unit) {
            throw new \Exception('Source unit not found');
        }

        $target_unit = Unit::find($flow->target_unit_id);

        if ($target_unit) {
            // synchronize unit
            if ($target_unit->config != $source_unit->config) {
                $target_unit->config = $source_unit->config;
                $target_unit->store();
            }
            self::syncStructuralElementsQuantity($flow, $source_unit->structural_element, $user);
            self::syncStructuralElements($flow, $target_unit->structural_element , $source_unit->structural_element);
        } else {
            $target = CopyHelper::copyUnit($user, $source_unit, $flow->target_course_id);
            $flow->target_unit_id = $target['target_unit']->id;
            $flow->status = 'idle';
            $flow->structural_elements_map = json_encode($target['structural_elements_map']);
            $flow->structural_elements_image_map = json_encode($target['structural_elements_image_map']);
            $flow->container_map = json_encode($target['container_map']);
            $flow->blocks_map = json_encode($target['blocks_map']);
            $flow->files_map = json_encode($target['files_map']);
            $flow->folders_map = json_encode($target['folders_map']);
            $flow->store();
        }
    }

    private static function syncStructuralElementsQuantity(Flow &$flow, StructuralElement $root, $user): void
    {
        $deletedElements = json_decode($flow->structural_elements_map, true);
        $structural_elements_map = json_decode($flow->structural_elements_map, true);

        //get all structural elements in source_unit
        $source_structural_elements = array_merge([$root], $root->findDescendants($user));
        $flow_map = json_decode($flow->structural_elements_map, true);
        $map_changed = false;
        foreach($source_structural_elements as $element) {
            unset($deletedElements[$element->id]);
            if (!array_key_exists($element->id, $structural_elements_map)) {
                $target_parent_id = $structural_elements_map[$element->parent_id] ?? $root->id;
                $target_parent = StructuralElement::find($target_parent_id);
                $target_element = CopyHelper::copyStructuralElement($user, $element, $target_parent);
                $flow_map[$element->id] = $target_element->id;
                $map_changed = true;
            }
        }

        if (sizeof($deletedElements) > 0) {
            foreach($deletedElements as $source_element_id => $target_element_id) {
                $target_element = StructuralElement::find($target_element_id);
                if ($target_element) {
                    $target_element->delete();
                }
                
                unset($flow_map[$source_element_id]);
            }
            $map_changed = true;
        }

        if ($map_changed) {
            $flow->structural_elements_map = json_encode($flow_map);
            $flow->store();
        }
    }

    private static function syncStructuralElements(Flow &$flow, StructuralElement $target_element, StructuralElement $source_element): void
    {
        $has_changes = false;

        $fields = ['commentable', 'position', 'purpose', 'title', 'release_date', 'withdraw_date']; // ??? release_date and withdraw_date ???

        foreach ($fields as $field) {
            if ($source_element->$field !== $target_element->$field) {
                $target_element->$field = $source_element->$field;
                $has_changes = true;
            }
        }

        if ($source_element->payload != $target_element->payload) {
            $target_element->payload = $source_element->payload;
            $has_changes = true;
        }

        $target_element_parent_id_by_source = $flow->structural_elements_map[$source_element->parent_id];

        if ($target_element_parent_id_by_source !== $target_element->parent_id) {
            $parent = StructuralElement::find($target_element_parent_id_by_source);
            if ($parent) {
                $target_element->parent_id = $parent->id;
                $has_changes = true;
            } else {
                throw new \Exception('Parent structural element not found');
            }
        }

        //todo: image_id && image_ref
        //todo: sync containers


        if ($has_changes) {
            $target_element->store();
        }

        foreach ($source_element->children as $child) {
            $target_child = StructuralElement::find($flow->structural_elements_map[$child->id]);
            if ($target_child) {
                self::syncStructuralElements($flow, $target_child, $child);
            }
        }

        //todo: sync blocks
        //todo: sync files
        //todo: sync folders
        
    
    }

    private static function syncContainers($source_course_id, $target_course_id): void
    {

    }

    private static function syncBlocks($source_course_id, $target_course_id): void
    {

    }

}