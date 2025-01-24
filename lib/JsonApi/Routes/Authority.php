<?php

namespace CoursewareFlow\JsonApi\Routes;

class Authority
{
    public static function canIndexFlows($user) : Bool
    {
        return $GLOBALS['perm']->have_perm('root', $user->id);
    }

    public static function canIndexUnitFlows($user, $course) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
        return self::canCreateFlow($user, $course);
    }

    public static function canIndexCourseFlows($user, $course) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
        return self::canCreateFlow($user, $course);
    }

    public static function canShowFlow($user, $flow) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
        return $GLOBALS['perm']->have_studip_perm('tutor', $flow->source_course_id, $user->id);
    }

    public static function canCreateFlow($user, $course) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
        return $GLOBALS['perm']->have_studip_perm('tutor', $course->id, $user->id);
    }

    public static function canUpdateFlow($user, $flow) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
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
}