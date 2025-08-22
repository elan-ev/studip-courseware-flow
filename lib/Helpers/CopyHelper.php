<?php
/**
 * CopyHelper
 *
 * Hilfsklasse für das Kopieren von Courseware-Einheiten, Strukturelementen,
 * Containern, Blocks und zugehörigen Dateien zwischen Veranstaltungen.
 *
 * @package   CoursewareFlow
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 * @link      https://elan-ev.de
 */

namespace CoursewareFlow\Helpers;

use \Courseware\Block;
use \Courseware\Container;
use \Courseware\StructuralElement;
use \Courseware\Unit;

class CopyHelper
{
    private static function addToMap(array &$map, ?string $key, ?string $value): void
    {
        if ($key === null || trim($key) === '' || $value === null) {
            return;
        }
    
        if (!isset($map[$key])) {
            $map[$key] = $value;
        }
    }
    
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

        $plugin_manager = \PluginManager::getInstance();

        if (sizeof($target_data['vips_map']) > 0) {
            $plugin_vips = $plugin_manager->getPlugin('VipsPlugin');
            if ($plugin_vips) {
                $plugin_manager->setPluginActivated($plugin_vips->getPluginId(), $target_course_id, true);
            }
        }
        if (sizeof($target_data['files_map']) > 0 || $target_data['folders_map'] > 0) {
            $core_documents = $plugin_manager->getPlugin('CoreDocuments');
            if ($core_documents) {
                $plugin_manager->setPluginActivated($core_documents->getPluginId(), $target_course_id, true);
            }
        }
        if ($target_data['has_oc_block']) {
            $opencast_v3 = $plugin_manager->getPlugin('OpencastV3');
            if ($opencast_v3) {
                $plugin_manager->setPluginActivated($opencast_v3->getPluginId(), $target_course_id, true);
            }
        }

        return $target_data;
    }

    public static function copyStructuralElement($user, $source_structural_element, $target_parent): StructuralElement
    {
        $target_structural_element = StructuralElement::build([
            'parent_id' => $target_parent->id,
            'range_id' => $target_parent->range_id,
            'range_type' => $target_parent->range_type,
            'owner_id' => $user->id,
            'editor_id' => $user->id,
            'edit_blocker_id' => null,
            'title' => $source_structural_element->title,
            'purpose' => $source_structural_element->purpose,
            'position' => $source_structural_element->position,
            'payload' => $source_structural_element->payload,
        ]);

        if (isset($source_structural_element->commentable)) {
            $target_structural_element->commentable = $source_structural_element->commentable;
        }

        $target_structural_element->store();

        return $target_structural_element;
    }

    protected static function copyUnitContent($user, $source_unit, $target_course_id)
    {
        $source_unit_structural_element = $source_unit->structural_element;

        if (mb_strpos( $source_unit_structural_element->title, '🔄') === false) {
            $source_unit_structural_element->title = '🔄 ' .  $source_unit_structural_element->title;
            $source_unit_structural_element->store();
        }

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
        $files_map = [];
        $folders_map = [];
        $vips_map = [];
        $has_oc_block = false;

        self::addToMap($structural_elements_map, $source_unit_structural_element->id, $target_unit_structural_element->id);

        $image_id = self::copyStructuralElementImage($user, $source_unit_structural_element, $target_unit_structural_element);

        if ($image_id) {
            $target_unit_structural_element->image_id = $image_id;
            $target_unit_structural_element->image_type = $source_unit_structural_element->image_type;
            $target_unit_structural_element->store();
            self::addToMap($structural_elements_image_map, $source_unit_structural_element->image_id, $target_unit_structural_element->image_id);
        }

        self::copyContainers($user, $target_unit_structural_element, $source_unit_structural_element, $container_map, $blocks_map, $files_map, $folders_map, $vips_map, $has_oc_block);
        self::copyChildren($user, $target_unit_structural_element, $source_unit_structural_element, $structural_elements_map, $structural_elements_image_map, $container_map, $blocks_map, $files_map, $folders_map, $vips_map, $has_oc_block);

        // rename filesystem folder
        $parent_folder = null;
        if (sizeof($files_map) > 0) {
            $first_file = array_key_first($files_map);
            $parent_folder = \FileRef::find($files_map[$first_file])->folder;
        }
        if (!$parent_folder  && sizeof($folders_map) > 0) {
            $first_folder = array_key_first($folders_map);
            $parent_folder = \Folder::find($folders_map[$first_folder])->parentfolder;
        }
        if ($parent_folder) {
            $parent_folder->__set('name',  'Courseware - ' . str_replace('🔄 ', '', $source_unit_structural_element->title));
            $parent_folder->__set('folder_type', 'MaterialFolder');
            $parent_folder->store();
        }

        return [
            'target_unit_structural_element' => $target_unit_structural_element,
            'structural_elements_map' => $structural_elements_map,
            'structural_elements_image_map' => $structural_elements_image_map,
            'container_map' => $container_map,
            'blocks_map' => $blocks_map,
            'files_map' => $files_map,
            'folders_map' => $folders_map,
            'target_folder_id' => $parent_folder ? $parent_folder->id : null,
            'vips_map' => $vips_map,
            'has_oc_block' => $has_oc_block
        ];
    }



    public static function copyStructuralElementImage(\User $user, StructuralElement $source, StructuralElement $target): ?string
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

    protected static function copyChildren(\User $user, StructuralElement $parent, StructuralElement $source, &$structural_elements_map, &$structural_elements_image_map,  &$container_map, &$blocks_map, &$files_map, &$folders_map, &$vips_map, &$has_oc_block): array
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

            self::addToMap($structural_elements_map, $child->id, $new_child->id);

            $image_id = self::copyStructuralElementImage($user, $child, $new_child);

            if ($image_id) {
                $new_child->image_id = $image_id;
                $new_child->image_type = $child->image_type;
                $new_child->store();
            }

            self::copyChildren($user, $new_child, $child, $structural_elements_map, $structural_elements_image_map, $container_map, $blocks_map, $files_map, $folders_map, $vips_map, $has_oc_block);
            self::copyContainers($user, $new_child, $child, $container_map, $blocks_map, $files_map, $folders_map, $vips_map, $has_oc_block);
        }

        return [
            'structural_elements_map' => $structural_elements_map,
            'structural_elements_image_map' => $structural_elements_image_map,
            'container_map' => $container_map,
            'blocks_map' => $blocks_map,
        ];
    }

    protected static function copyContainers($user, $target_element, $source_element, &$container_map, &$blocks_map, &$files_map, &$folders_map, &$vips_map, &$has_oc_block): void
    {
        foreach ($source_element->containers as $container) {
            self::copyContainer($user, $target_element, $container, $container_map, $blocks_map, $files_map, $folders_map, $vips_map, $has_oc_block);
        }
    }

    public static function copyContainer($user, $target_element, $source_container, &$container_map, &$blocks_map, &$files_map, &$folders_map, &$vips_map, &$has_oc_block): void
    {
        $new_container = Container::create([
            'structural_element_id' => $target_element->id,
            'owner_id' => $user->id,
            'editor_id' => $user->id,
            'edit_blocker_id' => null,
            'position' => $source_container->position,
            'container_type' => $source_container->type->getType(),
            'payload' => $source_container->payload,
        ]);

        self::addToMap($container_map, $source_container->id, $new_container->id);
        self::copyBlocks($user, $new_container, $source_container, $blocks_map, $files_map, $folders_map, $vips_map, $has_oc_block);
        self::updateSections($user, $new_container, $source_container, $blocks_map);
    }

    protected static function copyBlocks($user, $target_container, $source_container, &$blocks_map, &$files_map, &$folders_map, &$vips_map, &$has_oc_block): void
    {
        foreach ($source_container->blocks as $block) {
            $newBlock = $block->copy($user, $target_container); // map new file and folder ids. Each block has its own payload and way to store file id information
            self::mapFiles($files_map, $newBlock, $block);
            self::mapFolders($folders_map, $newBlock, $block);
            self::mapVips($vips_map, $newBlock, $block);
            self::addToMap($blocks_map, $block->id, $newBlock->id);

            if ($block->block_type === 'plugin-opencast-video') {
                $has_oc_block = true;
            }
        }
    }

    protected static function updateSections($user, $target_container, $source_container, $blocks_map): void
    {
        $source_payload = json_decode($source_container->payload, true);
        $target_payload = json_decode($target_container->payload, true);

        foreach ($source_payload['sections'] as $index => $source_section) {
            $block_list = [];
            foreach ($source_section['blocks'] as $block_id) {
                array_push($block_list, $blocks_map[$block_id]);
            }
            $target_payload['sections'][$index]['blocks'] = $block_list;
        }
        $target_container->payload = json_encode($target_payload);
        $target_container->store();

    }

    public static function copyBlock($user, $target_container, $source_block, &$blocks_map, &$files_map, &$folders_map): Block
    {
        $newBlock = $source_block->copy($user, $target_container);
        self::mapFiles($files_map, $newBlock, $source_block);
        self::mapFolders($folders_map, $newBlock, $source_block);
        self::addToMap($blocks_map, $source_block->id, $newBlock->id);

        return $newBlock;
    }
    protected static function mapFiles(&$files_map, $target_block, $source_block): void
    {
        $source_payload = $source_block->type->getPayload();
        $target_payload = $target_block->type->getPayload();

        switch ($source_block->block_type) {
            case 'audio':
            case 'canvas':
            case 'document':
            case 'download':
            case 'image-map':
            case 'video':
                self::addToMap($files_map, $source_payload['file_id'], $target_payload['file_id']);
                break;
            case 'before-after':
                self::addToMap($files_map, $source_payload['before_file_id'], $target_payload['before_file_id']);
                self::addToMap($files_map, $source_payload['after_file_id'], $target_payload['after_file_id']);
                break;
            case 'dialog-cards':
                foreach ($source_payload['cards'] as $index => $card) {
                    self::addToMap($files_map, $card['front_file_id'], $target_payload['cards'][$index]['front_file_id']);
                    self::addToMap($files_map, $card['back_file_id'], $target_payload['cards'][$index]['back_file_id']);
                }
                break;
            case 'headline':
                self::addToMap($files_map, $source_payload['background_image_id'], $target_payload['background_image_id']);
                break;
            case 'text':
                $source_files = $source_block->type->getFiles();
                $target_files = $target_block->type->getFiles();

                foreach ($source_files as $index => $source_file) {
                    self::addToMap($files_map, $source_file->id, $target_files[$index]->id);
                }
                break;
        }
    }

    protected static function mapFolders(&$folders_map, $target_block, $source_block): void
    {
        $source_payload = $source_block->type->getPayload();
        $target_payload = $target_block->type->getPayload();

        switch ($source_block->block_type) {
            case 'folder':
            case 'gallery':
                self::addToMap($folders_map, $source_payload['folder_id'], $target_payload['folder_id']);
                    break;

        }
    }

    protected static function mapVips(&$vips_map, $target_block, $source_block): void
    {
        $source_payload = $source_block->type->getPayload();
        $target_payload = $target_block->type->getPayload();
        switch ($source_block->block_type) {
            case 'test':
                self::addToMap($vips_map, $source_payload['assignment'], $target_payload['assignment']);
                break;
        }
    }
}