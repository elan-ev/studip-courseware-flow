<script setup>
import { computed, ref, watch } from 'vue';

import StudipActionMenu from './../../studip/StudipActionMenu.vue';
import StudipDialog from './../../studip/StudipDialog.vue';
import StudipQuicksearch from './../../studip/StudipQuicksearch.vue';

import { useDateFormatter } from "@/composables/useDateFormatter";
const { formatDate } = useDateFormatter();

import { useContextStore } from '../../../stores/context';
import { useCoursesStore } from '../../../stores/courses';
import { useFlowsStore } from '../../../stores/flows';
const contextStore = useContextStore();
const coursesStore = useCoursesStore();
const flowsStore = useFlowsStore();

const emit = defineEmits(['update:open']);
const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
});

const excludedCourses = ref([]);
const quicksearchRef = ref(null);

const courseSearch = computed(() => contextStore.courseSearch);
const currentUnit = computed(() => contextStore.selectedUnit);

const unitFlows = computed(() => {
    return flowsStore.all
        .filter((flow) => flow.source_unit.data.id === currentUnit.value?.id)
        .map((flow) => ({
            ...flow,
            target_course: {
                attributes: coursesStore.byId(flow.target_course.data.id),
            },
        }));
});



// Fetch courses for the current unit flows
const fetchCourses = async () => {
    if (!currentUnit.value) {
        return;
    }
    const relevantFlows = flowsStore.all.filter((flow) => flow.source_unit.data.id === currentUnit.value.id);

    excludedCourses.value = [...relevantFlows.map(flow => flow.target_course.data.id), contextStore.cid];

    for (const flow of relevantFlows) {
        await coursesStore.fetchById(flow.target_course.data.id);
    }
};


watch(
    () => props.open,
    async (newValue) => {
        if (newValue) {
            await fetchCourses();
        }
    }
);



const addCourse = (value) => {
    console.log(value);
    quicksearchRef.value.clear();
};

const updateOpen = (value) => {
    emit('update:open', value);
};

const updateFlows = () => {
    console.log('update flow');
    console.log(currentUnit.value);
    emit('update:open', false);
};

const deleteFlow = (flow) => {
    console.log('delete flow');
    console.log(flow);
};
</script>

<template>
    <StudipDialog
        :height="600"
        :width="800"
        :title="$gettext('Verteilung bearbeiten')"
        :close-text="$gettext('Schließen')"
        :open="open"
        @update:open="updateOpen"
    >
        <template #dialogContent>
            <table class="default">
                <thead>
                    <tr>
                        <th>{{ $gettext('Veranstaltung') }}</th>
                        <th>{{ $gettext('Status') }}</th>
                        <th>{{ $gettext('Aktiviert') }}</th>
                        <th>{{ $gettext('automatisches Synchronisieren') }}</th>
                        <th>{{ $gettext('letzte Aktualisierung') }}</th>
                        <th>{{ $gettext('erstellt am') }}</th>
                        <th>{{ $gettext('Aktion') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="flow in unitFlows" :key="flow.id">
                        <td>{{ flow.target_course.attributes?.title || '---' }}</td>
                        <td>{{ flow.status }}</td>
                        <td><input type="checkbox" :checked="flow.active" disabled></td>
                        <td><input type="checkbox" :checked="flow.auto_sync" disabled></td>
                        <td>{{ formatDate(flow.chdate) }}</td>
                        <td>{{ formatDate(flow.mkdate) }}</td>
                        <td class="actions">
                            <StudipActionMenu
                                :context="$gettext('Verteiltes Lernmaterial')"
                                :items="[{ id: 1, label: $gettext('Löschen'), icon: 'trash', emit: 'delete' }]"
                                @delete="deleteFlow(flow)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
            <form class="default cw-flow-dialog-edit">
                <label>
                    {{ $gettext('Veranstaltunghinzufügen') }}
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
        </template>
    </StudipDialog>
</template>
