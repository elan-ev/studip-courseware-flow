<?php
/**
 * Authority
 *
 * Berechtigungsprüfungen für JSON-API-Routen im CoursewareFlow-Modul.
 * Diese Klasse stellt Methoden bereit, um zu prüfen, ob ein Benutzer
 * bestimmte Aktionen auf Flows oder Einheiten durchführen darf.
 *
 * @package   CoursewareFlow\JsonApi\Routes
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 * @link      https://elan-ev.de
 */

namespace CoursewareFlow\JsonApi\Routes;

class Authority
{
    public static function canIndexFlows($user) : Bool
    {
        return $GLOBALS['perm']->have_perm('root', $user->id);
    }

    public static function canIndexUnitFlows($user, $course) : Bool
    {
        return self::canCreateFlow($user, $course);
    }

    public static function canIndexCourseFlows($user, $course) : Bool
    {
        return self::canCreateFlow($user, $course);
    }

    public static function canShowFlow($user, $flow) : Bool
    {
        return $GLOBALS['perm']->have_studip_perm('tutor', $flow->source_course_id, $user->id);
    }

    public static function canCreateFlow($user, $course) : Bool
    {
        return $GLOBALS['perm']->have_studip_perm('tutor', $course->id, $user->id);
    }

    public static function canUpdateFlow($user, $flow) : Bool
    {
        return self::canCreateFlow($user, $flow->source_course);
    }

    public static function canDeleteFlow($user, $flow) : Bool
    {
        return self::canUpdateFlow($user, $flow);
    }

    public static function canDeleteUnitFlows($user, $unit) : Bool
    {
        return self::canCreateFlow($user, $unit->course);
    }

    public static function canUpdateUnitFlows($user, $unit) : Bool
    {
        return self::canCreateFlow($user, $unit->course);
    }
}