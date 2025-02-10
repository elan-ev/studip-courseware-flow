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

        if (Navigation::hasItem('course/coursewareflow')) {
            Navigation::activateItem('course/coursewareflow/index');
            PageLayout::setBodyElementId('coursewareflow-index');
            PageLayout::setTitle('Courseware Flow');
            $this->isTeacher = $perm->have_studip_perm('tutor', Context::getId(), $user->id);
            $this->preferredLanguage = str_replace('_', '-', $_SESSION['_language']);
            $this->courseSearch = new StandardSearch('Seminar_id', ['simple_name' => true]);

            $sidebar = \Sidebar::Get();
            $sidebar->addWidget(new VueWidget('courseware-flow-view-widget'));
        }
    }
}