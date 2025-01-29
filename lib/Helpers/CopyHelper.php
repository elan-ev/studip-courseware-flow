<?php

namespace CoursewareFlow\Helpers;

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

        $target_unit = \Courseware\Unit::build([
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

        $target_unit_structural_element = \Courseware\StructuralElement::build([
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
        sleep(10);
        

        return [
            'target_unit_structural_element' => $target_unit_structural_element,
        ];
    }



    private static function copyStructuralElementImage(\User $user, \Courseware\StructuralElement $source, \Courseware\StructuralElement $target) : ?string
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


}