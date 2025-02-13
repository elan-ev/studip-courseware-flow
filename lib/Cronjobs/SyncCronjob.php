<?php 

use CoursewareFlow\Models\Flow;
use CoursewareFlow\Helpers\SyncHelper;

class SyncCronjob extends \CronJob
{
    public static function getName(): string
    {
        return 'CoursewareFlow Sync';
    }

    public static function getDescription()
    {
        return _('Synchronisiert die verteilten Lernmaterialien');
    }

    public static function getParameters()
    {
        return [];
    }

    public function setUp()
    {
    }

    public function execute($last_result, $parameters = [])
    {
        $flows = Flow::findBySQL('auto_sync = 1 AND status = ?', ['idle']);
        
        foreach ($flows as $flow) {
            $targetUnit = Courseware\Unit::find($flow->target_unit_id);
            $targetUser = User::find($targetUnit->creator_id);
            SyncHelper::syncFlow($flow, $targetUser);
        }
    }

    public function tearDown()
    {
    }
}