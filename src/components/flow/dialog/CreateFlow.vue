<script setup>
import { computed, ref } from 'vue';

import { useContextStore } from '@/stores/context';
import { useCoursesStore } from '@/stores/courses';
import { useFlowsStore } from '@/stores/flows';
import StudipQuicksearch from '@/components/studip/StudipQuicksearch.vue';
import StudipDialog from '@/components/studip/StudipDialog.vue';
import StudipIcon from '@/components/studip/StudipIcon.vue';

const contextStore = useContextStore();
const coursesStore = useCoursesStore();
const flowsStore = useFlowsStore();

const emit = defineEmits(['update:open']);

const courses = ref([]);
const quicksearchRef = ref(null);

const courseSearch = computed(() => contextStore.courseSearch);
const currentUnit = computed(() => contextStore.selectedUnit);
const excludedCourses = computed(() => {
    return [...courses.value.map(course => course.id), contextStore.cid];
});

const addCourse = async (value) => {
    if (value instanceof Event || !value) {
        return;
    }
    await coursesStore.fetchById(value);
    const course = coursesStore.byId(value);
    courses.value.push(course);
    quicksearchRef.value.clear();
};

const removeCourse = (id) => {
    courses.value = courses.value.filter((course) => course.id !== id);
};

const updateOpen = (value) => {
    emit('update:open', value);
    if (!value) {
        courses.value = [];
    }
};

const addFlow = () => {
    const data = {
        'source-unit-id': currentUnit.value.id,
        'target-course-ids': courses.value.map((course) => course.id),
    };
    flowsStore.createFlows(data);
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
                    <StudipQuicksearch
                        ref="quicksearchRef"
                        :searchtype="courseSearch"
                        :excluded-ids="excludedCourses"
                        name="qs"
                        @select="addCourse"
                        :placeholder="$gettext('Suchen')"
                    ></StudipQuicksearch>
                </label>
                <label>
                    {{ $gettext('Veranstaltungen') }}
                    <ul v-for="course in courses" :key="course.id">
                        <li class="cw-flow-course-selected">
                            <img class="cw-flow-course-avatar" :src="course.meta.avatar.small" alt="avatar" />
                            <span class="cw-flow-course-title">{{ course.title }}</span>
                            <button @click="removeCourse(course.id)">
                                <StudipIcon shape="trash" :size="20" />
                            </button>
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

        input[type='text'] {
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
        button {
            border: none;
            background: none;
        }
    }
}
</style>
