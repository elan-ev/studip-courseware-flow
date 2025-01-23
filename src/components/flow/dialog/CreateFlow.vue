<script setup>
import { computed, ref } from 'vue';

import { useContextStore } from './../../../stores/context';
import { useCoursesStore } from './../../../stores/courses';
import StudipQuicksearch from './../../studip/StudipQuicksearch.vue';
import StudipDialog from './../../studip/StudipDialog.vue';

const contextStore = useContextStore();
const coursesStore = useCoursesStore();

const emit = defineEmits(['update:open']);

const courses = ref([]);

const courseSearch = computed(() => contextStore.courseSearch);
const currentUnit = computed(() => contextStore.selectedUnit);

const addCourse = async (value) => {
    if (value instanceof Event || !value) {
        return;
    }
    
    console.log(value);
    await coursesStore.fetchById(value);
    const course = coursesStore.byId(value);
    courses.value.push(course);
    console.log(courses.value);
};

const updateOpen = (value) => {
    emit('update:open', value);
};

const addFlow = () => {
    console.log('add flow');
    console.log(currentUnit.value);
    emit('update:open', false);
};

</script>

<template>
        <StudipDialog
        :height="780"
        :width="500"
        :title="$gettext('Verteilung hinzufügen')"
        confirm-class="accept"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Erstellen')"
        :open="open"
        @update:open="updateOpen"
        @confirm="addFlow"
    >
        <template #dialogContent>
            <form class="default cw-flow-dialog-create">
                <label>
                    {{ $gettext('Veranstaltungssuche') }}
                    <StudipQuicksearch :searchtype="courseSearch" name="qs" @select="addCourse" :placeholder="$gettext('Suchen')"></StudipQuicksearch>
                </label>
                <label>
                    {{ $gettext('Veranstaltungen') }}
                    <ul v-for="(course, index) in courses" :key="'course'-index">
                        <li class="cw-flow-course-selected">
                            <img class="cw-flow-course-avatar" :src="course.meta.avatar.small" alt="avatar" />
                            <span class="cw-flow-course-title">{{ course.title }}</span>
                        </li>
                    </ul>
                </label>
            </form>
        </template>
    </StudipDialog>
    

</template>

<style lang="scss">
.cw-flow-dialog-create {
    .quicksearch_container {
        display: flex;
        flex-direction: column;

        input[type="text"] {
            width: 100%;
            outline: none;
        }
        .dropdownmenu {
            max-width: unset;
            max-height: unset;
            width: 100%;
            top: 1px;

            .autocomplete__results {
                width: calc(100% - 4px);
            }
        }
    }
    ul {
        padding: 0;
    }
    .cw-flow-course-selected {
        list-style: none;
        display: flex;
        flex-direction: row;

        .cw-flow-course-avatar {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
        .cw-flow-course-title {
            line-height: 24px;
        }
    }
}

</style>