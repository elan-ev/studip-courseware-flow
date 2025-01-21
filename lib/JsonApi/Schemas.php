<?php

namespace CoursewareFlow\JsonApi;

trait Schemas
{
    public function registerSchemas(): array
    {
        return [
            \CoursewareFlow\models\Flow::class => Schemas\Flow::class,
        ];
    }
}