<?php

namespace CoursewareFlow\Helpers;

use \Courseware\Block;
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
            self::syncStructuralElements($flow, $target_unit->structural_element , $source_unit->structural_element, $user);
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

    private static function syncStructuralElements(Flow &$flow, StructuralElement $target_element, StructuralElement $source_element, $user): void
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
        self::syncContainers($flow, $source_element, $target_element, $user);
die();

        if ($has_changes) {
            $target_element->store();
        }

        foreach ($source_element->children as $child) {
            $target_child = StructuralElement::find($flow->structural_elements_map[$child->id]);
            if ($target_child) {
                self::syncStructuralElements($flow, $target_child, $child);
            }
        }
    }

    private static function syncContainers(&$flow, $source_element, $target_element, $user): void
    {
        $deletable_containers = array_map($target_element->containers, 'id');

        self::syncBlocksQuantity($flow, $source_element, $target_element, $user);

        foreach ($source_element->containers as $container) {
            $target_container = Container::find($flow->container_map[$container->id]);
            if ($target_container) { 
                unset($deletable_containers, $target_container->id);
                self::syncContainerPayload($flow, $target_container, $container, $user);
                // todo: self::syncBlocks();
            } else {
                CopyHelper::copyContainer($user, $target_element, $container, $flow->container_map, $flow->blocks_map, $flow->files_map, $flow->folders_map);
                $flow->store();
            }
        }
        
        if (sizeof($deletable_containers) > 0) {
            foreach ($deletable_containers as $container_id) {
                $container = Container::find($container_id);
                if ($container) {
                    $container->delete();
                }
            }
        }
    }

    private static function syncBlocksQuantity(Flow &$flow, StructuralElement $source_element, StructuralElement $target_element, $user): void
    {
        $source_containers = $source_element->containers;
        $target_containers = $target_element->containers;

        $blocks_map = json_decode($flow->blocks_map, true);
        $files_map = json_decode($flow->files_map, true);
        $folders_map = json_decode($flow->folders_map, true);
        $map_changed = false;

        $source_blocks = [];
        $target_blocks = [];
        foreach ($source_containers as $source_container) {
            $source_blocks = array_merge($source_blocks,  Block::findBySQL('container_id = ?', [$source_container->id]));
        }
        foreach ($target_containers as $target_container) {
            $target_blocks = array_merge($target_blocks,  Block::findBySQL('container_id = ?', [$target_container->id]));
        }
        $deletable_blocks = array_column($target_blocks, 'id');

        foreach($source_blocks as $block) {
            $target_block = $flow->blocks_map[$block->id];
            
            if ($target_block) {
                $deletable_blocks = array_diff($deletable_blocks, [$target_block]);
            } else {
                $target_container = Container::find($flow->container_map[$block->container_id]);

                CopyHelper::copyBlock($user, $target_container, $block,  $blocks_map, $files_map, $folders_map);
                $map_changed = true;
            }
        }

        if ($map_changed) {
            $flow->blocks_map = json_encode($blocks_map);
            $flow->files_map = json_encode($files_map);
            $flow->folders_map = json_encode($folders_map);
            $flow->store();
        }

        foreach ($deletable_blocks as $block_id) {
            $block = Block::find($block_id);
            if ($block) {
                $block->delete();
            }
        }

    }

    private static function syncContainerPayload(Flow $flow, Container $target_container, Container $source_container, $user): void
    {
        $source_payload = json_decode($source_container->payload, true);
        $target_payload = json_decode($target_container->payload, true);
        // sync settings
        $target_payload['colspan'] = $source_payload['colspan'];
        
        // sync sections use block_map
        foreach ($source_payload['sections'] as $index => $section) {
            $target_payload['sections'][$index]['name'] = $section['name'];
            $target_payload['sections'][$index]['icon'] = $section['icon'];

            //todo: sync block ids and sort use block_map
            $target_block_list = [];
            foreach ($section['blocks'] as $block_id) {
                $target_block = Block::find($flow->blocks_map[$block_id]);
                if ($target_block) {
                    $target_block_list[] = $target_block->id;
                }
            }
            $target_payload['sections'][$index]['blocks'] = $target_block_list;
        }

        // store target container
    }

    private static function syncBlocks(): void
    {

    }

}