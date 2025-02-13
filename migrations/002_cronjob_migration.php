<?php

require_once __DIR__.'/../lib/Cronjobs/SyncCronjob.php';

class CronjobMigration extends Migration
{
    public function up()
    {
        SyncCronjob::register()->schedulePeriodic(59, 23)->activate();
    }

    public function down()
    {
        SyncCronjob::unregister();
    }
}