<?php

namespace CoursewareFlow\Models;

use SimpleORMap;

/**
 * Flow
 *
 * Repräsentiert einen CoursewareFlow-Eintrag, inklusive Quell- und Zielkurs,
 * Quell- und Zielunit, zugehöriger Blöcke, Container, Dateien und Ordner.
 *
 * @package   CoursewareFlow\Models
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 *
 * @property int $id
 * @property string $source_course_id
 * @property int $source_unit_id
 * @property string $target_course_id
 * @property int $target_unit_id
 * @property string $structural_elements_map
 * @property string $structural_elements_image_map
 * @property string $container_map
 * @property string $blocks_map
 * @property string $folders_map
 * @property string $files_map
 * @property string|null $target_folder_id
 * @property string $vips_map
 * @property string $status
 * @property bool $active
 * @property bool $auto_sync
 * @property int $sync_date
 * 
 * belongs_to objects:
 * @property \Course $source_course
 * @property \Course $target_course
 * @property \Courseware\Unit $source_unit
 * @property \Courseware\Unit $target_unit
 * @property \Folder $target_folder
 */

class Flow extends SimpleORMap
{
    public const STATUS_IDLE = 'idle';
    public const STATUS_COPYING = 'copying';
    public const STATUS_SYNCING = 'syncing';
    public const STATUS_FAILED = 'failed';

    protected static function configure($config = [])
    {
        $config['db_table'] = 'cw_flow';

        $config['serialized_fields']['structural_elements_map'] = \JSONArrayObject::class;
        $config['serialized_fields']['container_map'] = \JSONArrayObject::class;
        $config['serialized_fields']['blocks_map'] = \JSONArrayObject::class;
        $config['serialized_fields']['folders_map'] = \JSONArrayObject::class;
        $config['serialized_fields']['files_map'] = \JSONArrayObject::class;
        $config['serialized_fields']['vips_map'] = \JSONArrayObject::class;

        $config['belongs_to']['source_course'] = [
            'class_name'  => \Course::class,
            'foreign_key' => 'source_course_id',
            'assoc_foreign_key' => 'seminar_id',
        ];

        $config['belongs_to']['target_course'] = [
            'class_name'  => \Course::class,
            'foreign_key' => 'target_course_id',
            'assoc_foreign_key' => 'seminar_id',
        ];

        $config['belongs_to']['source_unit'] = [
            'class_name'  => \Courseware\Unit::class,
            'foreign_key' => 'source_unit_id',
        ];

        $config['belongs_to']['target_unit'] = [
            'class_name'  => \Courseware\Unit::class,
            'foreign_key' => 'target_unit_id',
        ];

        $config['belongs_to']['target_folder'] = [
            'class_name'  => \Folder::class,
            'foreign_key' => 'target_folder_id',
        ];

        parent::configure($config);
    }

    public function createTargetFolder($user): void
    {
        $rootFolder = \Folder::findTopFolder($this->target_course_id);
        $targetFolderName = 'Courseware - ' . str_replace('🔄 ', '', $this->source_unit->structural_element->title);

        $targetFolder = \FileManager::createSubFolder(
            \FileManager::getTypedFolder($rootFolder->id),
            $user,
            'MaterialFolder',
            $targetFolderName,
            ''
        );

        $this->target_folder_id = $targetFolder->id;
        $this->store();
    }
}