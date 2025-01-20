<!-- <?= QuickSearch::get('seminar', new StandardSearch('Seminar_id'))
                    ->setInputStyle('width: 240px')
                    ->setInputClass('target-seminar')
                    ->render() ?>
<?= var_dump($courseSearch) ?> -->
<div
    id="courseware-flow-app"
    is-teacher='<?= var_export($isTeacher) ?>'
    preferred-language='<?= $preferredLanguage ?>'
    course-search='<?= $courseSearch ?>'
>

</div>