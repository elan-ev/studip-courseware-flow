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

    public function getId($resource): ?string
    {
        return $resource->id;
    }

    public function getAttributes($resource, ContextInterface $context): iterable
    {
        $attributes =  [];

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


        $target_course = $resource->target_course;

        $relationships[self::REL_TARGET_COURSE] = $target_course
        ? [
            self::RELATIONSHIP_LINKS => [
                Link::RELATED => $this->createLinkToResource($target_course),
            ],
            self::RELATIONSHIP_DATA => $target_course,
        ]
        : [self::RELATIONSHIP_DATA => null];

        

        return $relationships;
    }
}