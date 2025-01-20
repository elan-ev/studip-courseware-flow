<?php

namespace CoursewareFlow\Flow;

use SimpleORMap;

/**
 * CoursewareFlow Flow
 *
 * @author  Ron Lucke <lucke@elan-ev.de>
 * @license GPL2 or any later version
 *
 * @property int $id database column
 * @property string $source_course_id database column
 * @property string $source_unit_id database column
 * @property string $target_course_id database column
 * @property string $target_unit_id database column
 * @property string $structural_elements_map database column
 * @property string $container_map database column
 * @property string $blocks_map database column
 * @property bool $auto_sync database column
 * 
 */
class Flow extends SimpleORMap
{

    protected static function configure($config = [])
    {
        $config['db_table'] = 'cw_flow';

        $config['serialized_fields']['structural_elements_map'] = JSONArrayObject::class;
        $config['serialized_fields']['container_map'] = JSONArrayObject::class;
        $config['serialized_fields']['blocks_map'] = JSONArrayObject::class;

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

        $config['belongs_to']['target_course'] = [
            'class_name'  => \Courseware\Unit::class,
            'foreign_key' => 'source_unit_id',
        ];

        $config['belongs_to']['source_course'] = [
            'class_name'  => \Courseware\Unit::class,
            'foreign_key' => 'source_unit_id',
        ];


    }
}