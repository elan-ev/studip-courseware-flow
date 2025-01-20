<?php

namespace CoursewareFlow\JsonApi\Routes;

class Authority
{
    public function canIndexFlows($user) : Bool
    {
        return $GLOBALS['perm']->have_perm('root', $user->id);
    }

    public function canIndexUnitFlows($user, $course) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
        return self::canCreateFlow($user, $course);
    }

    public function canShowFlow($user, $flow) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
        return $GLOBALS['perm']->have_studip_perm('tutor', $flow->source_course, $user->id);
    }

    public function canCreateFlow($user, $course) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
        return $GLOBALS['perm']->have_studip_perm('tutor', $course->id, $user->id);
    }

    public function canUpdateFlow($user, $flow) : Bool
    {
        //TODO: wer darf alles Flows anlegen und sehen?
        return self::canCreateFlow($user, $flow->source_course);
    }

    public function canDeleteFlow($user, $flow) : Bool
    {
        return self::canUpdateFlow($user, $flow);
    }
}