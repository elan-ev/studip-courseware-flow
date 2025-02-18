<script setup>
import { computed, ref, watch } from 'vue';

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
const emptyError = ref(true);

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
    if (emptyError.value) {
        return;
    }
    const data = {
        'source-unit-id': currentUnit.value.id,
        'target-course-ids': courses.value.map((course) => course.id),
    };
    flowsStore.createFlows(data);
    emit('update:open', false);
    courses.value = [];
};
watch(
    courses,
    async (newValue) => {
        emptyError.value = newValue.length === 0;
    },
    { deep: true }
);

</script>

<template>
    <StudipDialog
        :height="600"
        :width="500"
        :title="$gettext('Verteilung hinzufügen')"
        confirm-class="accept"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Erstellen')"
        :confirmDisabled="emptyError"
        :open="open"
        @update:open="updateOpen"
        @confirm="addFlow"
    >
        <template #dialogContent>
            <div class="cw-flow-dialog-create">
                <div class="cw-flow-create-unit-data">
                    <div class="cw-flow-create-unit-image">
                        <img
                            v-if="currentUnit['structural-element'].data.image.meta"
                            :src="currentUnit['structural-element'].data.image.meta['download-url']"
                            height="40"
                        />
                        <div v-else class="cw-element-image-placeholder">
                            <StudipIcon shape="courseware" :size="24" />
                        </div>
                    </div>
                    <div class="cw-flow-create-unit-title">
                        {{ currentUnit['structural-element'].data.title }}
                    </div>
                </div>
                <form class="default">
                    <label v-show="courses.length !== 0">
                        {{ $gettext('Veranstaltungen') }}
                        <ul v-for="course in courses" :key="course.id">
                            <li class="cw-flow-course-selected">
                                <img v-if="course.meta?.avatar" class="cw-flow-course-avatar" :src="course.meta.avatar.small" alt="avatar" />
                                <span class="cw-flow-course-title">{{ course.title }}</span>
                                <button @click="removeCourse(course.id)">
                                    <StudipIcon shape="trash" :size="20" />
                                </button>
                            </li>
                        </ul>
                    </label>
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
                </form>
            </div>
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
        margin-top: 4px;

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

    .cw-flow-create-unit-data {
        display: flex;
        flex-direction: row;
        gap: 10px;
        height: 40px;
        padding-bottom: 8px;
        border-bottom: solid thin var(--content-color-60);
        margin-bottom: 8px;
        overflow: hidden;

        .cw-flow-create-unit-title {
            flex-grow: 1;
            line-height: 40px;
            font-size: 16px;
        }
    }
}
</style>
