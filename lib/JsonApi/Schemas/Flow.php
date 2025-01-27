<?php
namespace CoursewareFlow\JsonApi\Schemas;

use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Schema\Link;

class Flow extends \JsonApi\Schemas\SchemaProvider
{
    public const TYPE = 'courseware-flows';

    const REL_SOURCE_COURSE = 'source_course';

    const REL_TARGET_COURSE = 'target_course';

    const REL_SOURCE_UNIT = 'source_unit';

    const REL_TARGET_UNIT = 'target_unit';

    public function getId($resource): string
    {
        return $resource->id;
    }

    public function getAttributes($resource, ContextInterface $context): iterable
    {
        $attributes =  [
            'active' =>  (bool) $resource['active'],
            'auto_sync' => (bool) $resource['auto_sync'],
            'status' => (string) $resource['status'],
            'source_course_id' => (string) $resource['source_course_id'],
            'source_unit_id' => (string) $resource['source_unit_id'],
            'target_course_id' => (string) $resource['target_course_id'],
            'target_unit_id' => (string) $resource['target_unit_id'],
            'mkdate' => date('c', $resource['mkdate']),
            'chdate' => date('c', $resource['chdate']),
        ];

        return $attributes;
    }

    public function getRelationships($resource, ContextInterface $context): iterable
    {
        $relationships = [];
        
        $source_course = $resource->source_course;

        $relationships[self::REL_SOURCE_COURSE] = $source_course
        ? [
            self::RELATIONSHIP_LINKS => [
                Link::RELATED => $this->createLinkToResource($source_course),
            ],
            self::RELATIONSHIP_DATA => $source_course,
        ]
        : [self::RELATIONSHIP_DATA => null];

        $source_unit = $resource->source_unit;

        $relationships[self::REL_SOURCE_UNIT] = $source_unit
        ? [
            self::RELATIONSHIP_LINKS => [
                Link::RELATED => $this->createLinkToResource($source_unit),
            ],
            self::RELATIONSHIP_DATA => $source_unit,
        ]
        : [self::RELATIONSHIP_DATA => null];


        $target_course = $resource->target_course;

        $relationships[self::REL_TARGET_COURSE] = $target_course
        ? [
            self::RELATIONSHIP_LINKS => [
                Link::RELATED => $this->createLinkToResource($target_course),
            ],
            self::RELATIONSHIP_DATA => $target_course,
        ]
        : [self::RELATIONSHIP_DATA => null];

        $target_unit = $resource->target_unit;

        $relationships[self::REL_TARGET_UNIT] = $target_unit
        ? [
            self::RELATIONSHIP_LINKS => [
                Link::RELATED => $this->createLinkToResource($target_unit),
            ],
            self::RELATIONSHIP_DATA => $target_unit,
        ]
        : [self::RELATIONSHIP_DATA => null];

        

        return $relationships;
    }
}