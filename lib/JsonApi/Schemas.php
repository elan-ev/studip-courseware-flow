<?php
/**
 * Schemas
 *
 * Trait zum Registrieren von JSON-API-Schemata für CoursewareFlow-Modelle.
 * Momentan wird nur das Flow-Schema registriert.
 *
 * @package   CoursewareFlow\JsonApi
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 * @link      https://elan-ev.de
 */

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