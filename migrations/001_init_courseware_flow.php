<?php

final class InitCoursewareFlow extends Migration
{
    public function up()
    {
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `cw_flow` (
            `id`                        INT(11) NOT NULL AUTO_INCREMENT,
            `source_course_id`          CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            `source_unit_id`            INT(11) NOT NULL,
            `target_course_id`          CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            `target_unit_id`            INT(11) NOT NULL,
            `structural_elements_map`   MEDIUMTEXT NOT NULL,
            `container_map`             MEDIUMTEXT NOT NULL,
            `blocks_map`                MEDIUMTEXT NOT NULL,
            `folders_map`               MEDIUMTEXT NOT NULL,
            `files_map`                 MEDIUMTEXT NOT NULL,
            `active`                    TINYINT(1) NOT NULL DEFAULT '1',
            `auto_sync`                 TINYINT(1) NOT NULL DEFAULT '0',
            `mkdate`                    INT(11) UNSIGNED NOT NULL,
            `chdate`                    INT(11) UNSIGNED NOT NULL,

             PRIMARY KEY (`id`),
             INDEX index_source_unit_id (`source_unit_id`),
            )"
        );
    }

    public function down()
    {
        DBManager::get()->exec("DROP TABLE IF EXISTS `cw_flow`");
    }
}