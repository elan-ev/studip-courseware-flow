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
        $helpbar->addPlainText('',  _('Dieses Stud.IP Plugin wurde vom elan e.V. entwickelt.'));
        $helpbar->addLink('GitHub Repository', 'https://github.com/elan-ev/studip-courseware-flow', Icon::create('link-extern', Icon::ROLE_INFO_ALT), '_blank');

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