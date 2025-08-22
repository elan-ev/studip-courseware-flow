<?php
/**
 * CronjobMigration
 *
 * Migration zur Registrierung des CoursewareFlow-Synchronisations-Cronjobs.
 * Der Cronjob wird täglich um 23:59 Uhr ausgeführt.
 *
 * @package   CoursewareFlow
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 */

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