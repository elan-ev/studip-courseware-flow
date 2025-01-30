<?php

namespace CoursewareFlow\Helpers;

use \Courseware\Container;
use \Courseware\StructuralElement;
use \Courseware\Unit;

function addToMap(array &$map, string $key, string $value): void
{
    if (!isset($map[$key])) {
        $map[$key] = $value;
    } else {
        throw new \Exception("Duplicate key: $key");
    }
}

class CopyHelper
{
    public static function copyUnit($user, $source_unit, $target_course_id): array
    {
        $target_data = self::copyUnitContent($user, $source_unit, $target_course_id);

        $target_unit = Unit::build([
            'range_id' => $target_course_id,
            'range_type' => 'course',
            'structural_element_id' => $target_data['target_unit_structural_element']->id,
            'content_type' => 'courseware',
            'creator_id' => $user->id,
            'public' => '',
            'release_date' => $source_unit->release_date,
            'withdraw_date' => $source_unit->withdraw_date,
            'config' => $source_unit->config,
        ]);

        $target_unit->store();
        $target_data['target_unit'] = $target_unit;

        return $target_data;
    }

    private static function copyUnitContent($user, $source_unit, $target_course_id)
    {
        $source_unit_structural_element = $source_unit->structural_element;

        $target_unit_structural_element = StructuralElement::build([
            'parent_id' => null,
            'range_id' => $target_course_id,
            'range_type' => 'course',
            'owner_id' => $user->id,
            'editor_id' => $user->id,
            'edit_blocker_id' => null,
            'title' => $source_unit_structural_element->title,
            'purpose' => $source_unit_structural_element->purpose,
            'position' => 0,
            'payload' => $source_unit_structural_element->payload,
            'commentable' => 0
        ]);

        $target_unit_structural_element->store();
        $structural_elements_map = [];
        $structural_elements_image_map = [];
        $container_map = [];
        $blocks_map = [];

        addToMap($structural_elements_map, $source_unit_structural_element->id, $target_unit_structural_element->id);

        $image_id = self::copyStructuralElementImage($user, $source_unit_structural_element, $target_unit_structural_element);

        if ($image_id) {
            $target_unit_structural_element->image_id = $image_id;
            $target_unit_structural_element->image_type = $source_unit_structural_element->image_type;
            $target_unit_structural_element->store();
            addToMap($structural_elements_image_map, $source_unit_structural_element->image_id, $target_unit_structural_element->image_id);
        }

        //simulate delay
        // sleep(10);
        self::copyContainers($user, $target_unit_structural_element, $source_unit_structural_element, $container_map, $blocks_map);
        self::copyChildren($user, $target_unit_structural_element, $source_unit_structural_element, $structural_elements_map, $structural_elements_image_map, $container_map, $blocks_map);

        return [
            'target_unit_structural_element' => $target_unit_structural_element,
            'structural_elements_map' => $structural_elements_map,
            'structural_elements_image_map' => $structural_elements_image_map,
            'container_map' => $container_map,
            'blocks_map' => $blocks_map,
        ];
    }



    private static function copyStructuralElementImage(\User $user, StructuralElement $source, StructuralElement $target): ?string
    {
        if ($source->image_type === \StockImage::class) {
            return $source->image_id;
        }

        if ($source->image_type === \FileRef::class) {
            $file_ref_id = null;

            /** @var ?\FileRef $original_file_ref */
            $original_file_ref = \FileRef::find($source->image_id);
            if ($original_file_ref) {
                $instance = new \Courseware\Instance(\Courseware\StructuralElement::getCoursewareCourse($target->range_id));
                $folder = \Courseware\Filesystem\PublicFolder::findOrCreateTopFolder($instance);
                /** @var \FileRef $file_ref */
                $file_ref = \FileManager::copyFile($original_file_ref->getFileType(), $folder, $user);
                $file_ref_id = $file_ref->id;
            }

            return $file_ref_id;
        }

        return null;
    }

    private static function copyChildren(\User $user, StructuralElement $parent, StructuralElement $source, &$structural_elements_map, &$structural_elements_image_map,  &$container_map, &$blocks_map): array
    {

        foreach ($source->children as $child) {
            $new_child = StructuralElement::build([
                'parent_id' => $parent->id,
                'range_id' => $parent->range_id,
                'range_type' => $parent->range_type,
                'owner_id' => $user->id,
                'editor_id' => $user->id,
                'edit_blocker_id' => null,
                'title' => $child->title,
                'purpose' => $child->purpose,
                'position' => 0,
                'payload' => $child->payload,
                'commentable' => 0
            ]);
            $new_child->store();

            addToMap($structural_elements_map, $child->id, $new_child->id);

            $image_id = self::copyStructuralElementImage($user, $child, $new_child);

            if ($image_id) {
                $new_child->image_id = $image_id;
                $new_child->image_type = $child->image_type;
                $new_child->store();
            }

            self::copyChildren($user, $new_child, $child, $structural_elements_map, $structural_elements_image_map, $container_map, $blocks_map);
            self::copyContainers($user, $new_child, $child, $container_map, $blocks_map);
        }

        return [
            'structural_elements_map' => $structural_elements_map,
            'structural_elements_image_map' => $structural_elements_image_map,
            'container_map' => $container_map,
            'blocks_map' => $blocks_map,
        ];
    }

    private static function copyContainers($user, $target_element, $source_element, &$container_map, &$blocks_map): array
    {
        foreach ($source_element->containers as $container) {
            $new_container = Container::create([
                'structural_element_id' => $target_element->id,
                'owner_id' => $user->id,
                'editor_id' => $user->id,
                'edit_blocker_id' => null,
                'position' => $container->position,
                'container_type' => $container->type->getType(),
                'payload' => $container->payload,
            ]);

            addToMap($container_map, $container->id, $new_container->id);
            self::copyBlocks($user, $new_container, $container, $blocks_map);
        }

        return [$container_map, $blocks_map];
    }

    private static function copyBlocks($user, $target_container, $source_container, &$blocks_map): array
    {
        $newBlockList = [];

        foreach ($source_container->blocks as $block) {
            $newBlock = $block->copy($user, $target_container); // map new file and folder ids. Each block has its own payload and way to store file id information
            addToMap($blocks_map, $block->id, $newBlock->id);
            $newBlockList[$block->id] = $newBlock;
        }

        return [$blocks_map, $newBlockList];
    }

}