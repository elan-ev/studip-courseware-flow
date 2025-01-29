<?php

namespace CoursewareFlow\JsonApi;

trait Schemas
{
    public function registerSchemas(): array
    {
        return [
            \CoursewareFlow\Models\Flow::class => Schemas\Flow::class,
        ];
    }
}