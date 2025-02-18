<? if ($isTeacher): ?>
<div
    id="courseware-flow-app"
    is-teacher='<?= var_export($isTeacher) ?>'
    preferred-language='<?= $preferredLanguage ?>'
    course-search='<?= $courseSearch ?>'
></div>
<? else: ?>
    <?= MessageBox::info(sprintf( _('Mit Ihrer Rechtestufe steht Ihnen dieses Werkzeug nicht zur Verfügung.'))) ?>
<? endif ?>