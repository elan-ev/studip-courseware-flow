<?php
/**
 * FlowCourseSearch
 *
 * Klasse für die Suche nach Veranstaltungen im CoursewareFlow-Plugin.
 * Führt unterschiedliche SQL-Abfragen aus, abhängig von den Berechtigungen des Benutzers.
 *
 * @package   CoursewareFlow\Classes
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 * @link      https://elan-ev.de
 */

namespace CoursewareFlow\Classes;

class FlowCourseSearch extends \StandardSearch
{
    public function __construct($search, $search_settings = []) {
        parent::__construct($search, $search_settings = []);       
        $this->search_settings['user_id'] = $GLOBALS['user']->id;
        if (!$GLOBALS['perm']->have_perm("admin")) {
            $this->sql = $this->getFlowSQL();
        } else {
            $this->sql = $this->getFlowAdminSQL();
        }
        
    }

    private function getFlowSQL()
    {
        $semester = " CONCAT('(',IFNULL(GROUP_CONCAT(DISTINCT sem1.name ORDER BY sem1.beginn SEPARATOR '-'),'" . _('unbegrenzt') . "'),')')";

        switch ($this->search) {
            case "Seminar_id":
                return "SELECT seminare.Seminar_id, CONCAT_WS(' ', seminare.VeranstaltungsNummer, seminare.Name,  ".$semester.") " .
                    "FROM seminare " .
                    "LEFT JOIN semester_courses ON (semester_courses.course_id = seminare.Seminar_id) " .
                    "LEFT JOIN `semester_data` sem1 ON (semester_courses.semester_id = sem1.semester_id) " .
                    "LEFT JOIN seminar_user ON (seminar_user.Seminar_id = seminare.Seminar_id AND seminar_user.status = 'dozent') " .
                    "LEFT JOIN auth_user_md5 ON (auth_user_md5.user_id = seminar_user.user_id) " .
                    "WHERE (seminare.Name LIKE :input " .
                    "OR CONCAT(auth_user_md5.Vorname, ' ', auth_user_md5.Nachname) LIKE :input " .
                    "OR seminare.VeranstaltungsNummer LIKE :input " .
                    "OR seminare.Untertitel LIKE :input " .
                    "OR seminare.Beschreibung LIKE :input " .
                    "OR seminare.Ort LIKE :input " .
                    "OR seminare.Sonstiges LIKE :input) " .
                    "AND seminare.visible = 1 " .
                    "AND seminar_user.user_id = '" . $this->search_settings['user_id'] . "'" .
                    "AND seminare.status NOT IN ('".implode("', '", studygroup_sem_types())."') " .
                    " GROUP BY seminare.seminar_id ORDER BY sem1.`beginn` DESC, " .
                    (\Config::get()->IMPORTANT_SEMNUMBER ? "seminare.`VeranstaltungsNummer`, " : "") .
                    "seminare.`Name`";
            default:
            throw new \UnexpectedValueException("Invalid search type {$this->search}");
        }
    }

    private function getFlowAdminSQL()
    {
        $semester = " CONCAT('(',IFNULL(GROUP_CONCAT(DISTINCT sem1.name ORDER BY sem1.beginn SEPARATOR '-'),'" . _('unbegrenzt') . "'),')')";

        switch ($this->search) {
            case "Seminar_id":
                return "SELECT seminare.Seminar_id, CONCAT_WS(' ', seminare.VeranstaltungsNummer, seminare.Name,  ".$semester.") " .
                    "FROM seminare " .
                    "LEFT JOIN semester_courses ON (semester_courses.course_id = seminare.Seminar_id) " .
                    "LEFT JOIN `semester_data` sem1 ON (semester_courses.semester_id = sem1.semester_id) " .
                    "LEFT JOIN seminar_user ON (seminar_user.Seminar_id = seminare.Seminar_id AND seminar_user.status = 'dozent') " .
                    "LEFT JOIN auth_user_md5 ON (auth_user_md5.user_id = seminar_user.user_id) " .
                    "WHERE (seminare.Name LIKE :input " .
                    "OR CONCAT(auth_user_md5.Vorname, ' ', auth_user_md5.Nachname) LIKE :input " .
                    "OR seminare.VeranstaltungsNummer LIKE :input " .
                    "OR seminare.Untertitel LIKE :input " .
                    "OR seminare.Beschreibung LIKE :input " .
                    "OR seminare.Ort LIKE :input " .
                    "OR seminare.Sonstiges LIKE :input) " .
                    "AND seminare.visible = 1 " .
                    "AND seminare.status NOT IN ('".implode("', '", studygroup_sem_types())."') " .
                    " GROUP BY seminare.seminar_id ORDER BY sem1.`beginn` DESC, " .
                    (\Config::get()->IMPORTANT_SEMNUMBER ? "seminare.`VeranstaltungsNummer`, " : "") .
                    "seminare.`Name`";
            default:
            throw new \UnexpectedValueException("Invalid search type {$this->search}");
        }
    }

}