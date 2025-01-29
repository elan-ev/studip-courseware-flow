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
    public static function copyUnit($user, $source_unit, $target_course_id)
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

        return $target_unit;
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

        $image_id = self::copyStructuralElementImage($user, $source_unit_structural_element, $target_unit_structural_element);
        $target_unit_structural_element->image_id = $image_id;
        $target_unit_structural_element->image_type = $source_unit_structural_element->image_type;
        $target_unit_structural_element->store();


        $structural_elements_image_map = [];

        // addToMap($structural_elements_image_map, $source_unit_structural_element->image_id, $target_unit_structural_element->image_id);

        //simulate copy children
        // sleep(10);
        self::copyChildren($user, $target_unit_structural_element, $source_unit_structural_element);
        self::copyContainers($user, $target_unit_structural_element, $source_unit_structural_element);
        

        return [
            'target_unit_structural_element' => $target_unit_structural_element,
        ];
    }



    private static function copyStructuralElementImage(\User $user, StructuralElement $source, StructuralElement $target) : ?string
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

    private static function copyChildren(\User $user, StructuralElement $parent, StructuralElement $source): void
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

            $image_id = self::copyStructuralElementImage($user, $child, $new_child);
            $new_child->image_id = $image_id;
            $new_child->image_type = $child->image_type;
            $new_child->store();

            self::copyChildren($user, $new_child, $child);
            self::copyContainers($user, $new_child, $child);
        }
    }

    private static function copyContainers($user, $target_element, $source_element): void
    {
        foreach ($source_element->containers as $container) {
            $new_container = Container::create([
                'parent_id' => $target_element->id,
                'range_id' => $target_element->range_id,
                'range_type' => $target_element->range_type,
                'owner_id' => $user->id,
                'editor_id' => $user->id,
                'edit_blocker_id' => null,
                'title' => $container->title,
                'purpose' => $container->purpose,
                'position' => 0,
                'payload' => $container->payload,
                'commentable' => 0
            ]);
            [$blockMapIds, $blockMapObjs] = self::copyBlocks($user, $new_container, $container);
        }     
    }

    private static function copyBlocks($user, $target_container, $source_container): array
    {
        $blockMap = [];
        $newBlockList = [];

        foreach ($source_container->blocks as $block) {
            $newBlock = $block->copy($user, $target_container); // map new file and folder ids. Each block has its own payload and way to store file id information
            $blockMap[$block->id] = $newBlock->id;
            $newBlockList[$block->id] = $newBlock;
        }

        return [$blockMap, $newBlockList];
    }

}