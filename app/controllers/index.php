<?php

class IndexController extends StudipController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        global $perm, $user;

        $helpbar = Helpbar::get();
        $helpbar->addPlainText('', 
            _('Mit CoursewareFlow können Sie Lernmaterialien in andere Veranstaltungen Verteilen. Die Lernmaterialien werden entweder automatisch oder manuell mit dem Quellmaterial synchronisiert. Bei der Verteilung werden sowohl die Dateien aus dem Dateibereich, also auch Vips Aufgaben und OpenCast Videos in die Zielveranstaltung übertragen.')
        );
        $helpbar->addPlainText('',  _('Dieses Plugin wurde vom elan e.V. entwickelt. Es steht unter der GNU Affero General Public License, Version 3. Der vollständige Quellcode ist öffentlich zugänglich im GitHub-Repository.'));
        $helpbar->addLink('GNU Affero General Public License', 'https://www.gnu.org/licenses/agpl-3.0.de.html', Icon::create('link-extern', Icon::ROLE_INFO_ALT), '_blank');
        $helpbar->addLink('GitHub-Repository', 'https://github.com/elan-ev/studip-courseware-flow', Icon::create('link-extern', Icon::ROLE_INFO_ALT), '_blank');
        $helpbar->addLink('elan e.V.', 'https://elan-ev.de', Icon::create('link-extern', Icon::ROLE_INFO_ALT), '_blank');

        if (Navigation::hasItem('course/coursewareflow')) {
            Navigation::activateItem('course/coursewareflow/index');
            PageLayout::setBodyElementId('coursewareflow-index');
            PageLayout::setTitle('Courseware Flow');
            $this->isTeacher = $perm->have_studip_perm('tutor', Context::getId(), $user->id);
            $this->preferredLanguage = str_replace('_', '-', $_SESSION['_language']);
            $this->courseSearch = new CoursewareFlow\Classes\FlowCourseSearch('Seminar_id', ['simple_name' => true]);

            $sidebar = \Sidebar::Get();
            $sidebar->addWidget(new VueWidget('courseware-flow-view-widget'));
        }
    }
}