<?php

namespace CoursewareFlow\Helpers;

use \Courseware\Block;
use \Courseware\Container;
use \Courseware\StructuralElement;
use \Courseware\Unit;
use CoursewareFlow\Models\Flow;

class SyncHelper
{

    private static function addToMap(array &$map, string $key, string $value): void
    {
        if (!isset($map[$key])) {
            $map[$key] = $value;
        }
    }
    public static function syncFlow(Flow $flow, $user): Flow
    {
        $flow->status = Flow::STATUS_SYNCING;
        $flow->store();

        self::syncUnit($flow, $user);

        $flow->sync_date = time();
        $flow->status = Flow::STATUS_IDLE;
        $flow->store();
        
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
        $structural_elements_image_map = json_decode($flow->structural_elements_image_map, true);
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

        // sync structural element image
        if ($source_element->image_id) {
            if (!array_key_exists($source_element->image_id, $structural_elements_image_map) || $structural_elements_image_map[$source_element->image_id] != $target_element->image_id) {
                $target_element->image_id = CopyHelper::copyStructuralElementImage($user, $source_element, $target_element);
                self::addToMap($structural_elements_image_map, $source_element->image_id, $target_element->image_id);
                $has_changes = true;
            }
        } else {
            $target_element->image_id = null;
            $has_changes = true;
        }

        if ($target_element->image_type !== $source_element->image_type) {
            $target_element->image_type = $source_element->image_type;
            $has_changes = true;
        }
        
        self::syncContainers($flow, $source_element, $target_element, $user);

        if ($has_changes) {
            $flow->structural_elements_image_map = json_encode($structural_elements_image_map);
            $flow->store();
            $target_element->store();
        }

        foreach ($source_element->children as $child) {
            $target_child = StructuralElement::find($flow->structural_elements_map[$child->id]);
            if ($target_child) {
                self::syncStructuralElements($flow, $target_child, $child, $user);
            }
        }
    }

    private static function syncContainers(&$flow, $source_element, $target_element, $user): void
    {
        self::syncBlocksQuantity($flow, $source_element, $target_element, $user);

        $deletable_containers = array_column($target_element->containers->toArray(), 'id');
        $container_map = json_decode($flow->container_map, true);
        $blocks_map = json_decode($flow->blocks_map, true);
        $files_map = json_decode($flow->files_map, true);
        $folders_map = json_decode($flow->folders_map, true);
        $map_changed = false;

        foreach ($source_element->containers as $container) {
            $target_container = Container::find($flow->container_map[$container->id]);
            if ($target_container) { 
                $deletable_containers = array_diff($deletable_containers, [$target_container->id]);
                
                self::syncContainerAttributes($flow, $target_container, $container, $user);
            } else {
                CopyHelper::copyContainer($user, $target_element, $container, $container_map, $blocks_map, $files_map, $folders_map);
                $flow->store();
            }
        }

        if ($map_changed) {
            $flow->container_map = json_encode($container_map);
            $flow->blocks_map = json_encode($blocks_map);
            $flow->files_map = json_encode($files_map);
            $flow->folders_map = json_encode($folders_map);
            $flow->store();
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
            $target_block_id = $flow->blocks_map[$block->id];
            
            if ($target_block_id) {
                $deletable_blocks = array_diff($deletable_blocks, [$target_block_id]);
                $target_block = Block::find($target_block_id);
                self::syncBlock($flow, $user, $target_block, $block, $files_map, $folders_map, $map_changed);
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

    private static function syncContainerAttributes(Flow $flow, Container $target_container, Container $source_container, $user): void
    {
        $target_container->position = $source_container->position;

        $source_payload = json_decode($source_container->payload, true);
        $target_payload = json_decode($target_container->payload, true);
        // sync settings
        $target_payload['colspan'] = $source_payload['colspan'];
        
        // sync sections use block_map
        foreach ($source_payload['sections'] as $index => $section) {
            $target_payload['sections'][$index]['name'] = $section['name'];
            $target_payload['sections'][$index]['icon'] = $section['icon'];

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
        $target_container->payload = json_encode($target_payload);
        $target_container->store();
    }

    private static function syncBlock(Flow &$flow, $user, Block $target_block, Block $source_block, &$files_map, &$folders_map, &$map_changed): void
    {
        if (!$target_block ||!$source_block) {
            return;
        }
        $source_payload = json_decode($source_block->payload, true);
        $target_payload = json_decode($target_block->payload, true);

        // overwrite all payload settings
        $target_payload = $source_payload;

        // update file and folder ids with maps
        $target_payload = self::updateFileIds($flow, $user, $target_block, $source_block, $files_map);
        $target_payload = self::updateFolderIds($flow, $user, $target_payload, $source_block, $folders_map);


        // special handling for link blocks

        $target_block->payload = json_encode($target_payload);
        $target_block->store();
    }

    private static function updateFileIds(Flow &$flow, $user, $target_block, $source_block, &$files_map): Array
    {
        $source_payload = $source_block->type->getPayload();
        $target_payload = json_decode($target_block->payload, true);

        switch ($source_block->block_type) {
            case 'audio':
            case 'canvas':
            case 'document':
            case 'download':
            case 'image-map':
            case 'video':
                if (isset($files_map[$source_payload['file_id']])) {
                    $target_payload['file_id'] = $files_map[$source_payload['file_id']];
                } else {
                    if ($source_payload['file_id'] !== '') {
                        $copied_file_id = self::copyFileById($flow,$user, $source_payload['file_id']);
                        $target_payload['file_id'] = $copied_file_id;
                        self::addToMap($files_map, $source_payload['file_id'], $copied_file_id);
                    } else {
                        $target_payload['file_id'] = '';
                    }
                }
                break;
            case 'before-after':
                break;
            case 'dialog-cards':
                break;
            case 'headline':
                break;
            case 'text':
                break;
        }

        return $target_payload;
    }

    private static function updateFolderIds(Flow &$flow, $user, $target_payload, $source_block, &$folders_map): Array
    {
        $source_payload = $source_block->type->getPayload();

        switch ($source_block->block_type) {
            case 'folder':
            case 'gallery':
                if (isset($folders_map[$source_payload['folder_id']])) {
                    $target_payload['folder_id'] = $folders_map[$source_payload['folder_id']];
                    // self::syncTargetFolder($user, $target_payload['folder_id'], $source_payload['folder_id']); // TODO
                } else {
                    if ($source_payload['folder_id'] !== '') {
                        $target_payload['folder_id'] = self::copyFolderById($flow, $user,  $source_payload['folder_id']);
                    } else {
                        $target_payload['folder_id'] = '';
                    }
                }
                break;
        }

        return $target_payload;
    }

        /**
     * Copies a file to a specified range.
     *
     * @param string $fileId  the ID of the file
     * @param string $rangeId the ID of the range
     *
     * @return string the ID of the copy
     */
    private static function copyFileById($flow, $user, string $source_file_id): string
    {
        $file_ref = \FileRef::find($source_file_id);

        if (!$file_ref) {
            return '';
        }

        if (!$flow->target_folder) {
            $flow->createTargetFolder($user);
        }

        $copiedFile = \FileManager::copyFile(
            $file_ref->getFiletype(),
            $flow->target_folder->getTypedFolder(),
            $user
        );

        if (is_object($copiedFile)) {
            return $copiedFile->id;
        }

        return '';
    }

    private static function copyFolderById($flow, $user, string $source_folder_id): string
    {
        $source_folder = \Folder::find($source_folder_id);

        if (!$source_folder) {
            return '';
        }

        if (!$flow->target_folder) {
            $flow->createTargetFolder($user);
        }

        $copiedFolder = \FileManager::copyFolder(
            $source_folder->getTypedFolder(),
            $flow->target_folder->getTypedFolder(),
            $user
        );

        if (is_object($copiedFolder)) {
            return $copiedFolder->id;
        }

        return '';
    }

    private static function syncTargetFolder($user, string $target_folder_id, string $source_folder_id): void
    {
        // get all file-refs in source folder an compare their file-ids with the target folder file-refs and their file-ids
        
        // if a file-id is not in the target folder, copy the file to the target folder

        // if a file-id is in the target folder but not in the source folder, delete the file from the target folder
    }

}