<script setup>
import { computed, ref, watch } from 'vue';

import StudipActionMenu from '@/components/studip/StudipActionMenu.vue';
import StudipDialog from '@/components/studip/StudipDialog.vue';
import StudipQuicksearch from '@/components/studip/StudipQuicksearch.vue';
import DeleteFlow from '@/components/flow/dialog/DeleteFlow.vue';

import { useDateFormatter } from '@/composables/useDateFormatter';
const { formatDate } = useDateFormatter();

import { useContextStore } from '@/stores/context';
import { useCoursesStore } from '@/stores/courses';
import { useFlowsStore } from '@/stores/flows';
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
const openDeleteDialog = ref(false);

const courseSearch = computed(() => contextStore.courseSearch);
const currentUnit = computed(() => contextStore.selectedUnit);
const currentFlow = computed(() => contextStore.selectedFlow);
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

    updateExcludedCourses();

    for (const flow of relevantFlows) {
        await coursesStore.fetchById(flow.target_course.data.id);
    }
};

const updateExcludedCourses = () => {
    const relevantFlows = flowsStore.all.filter((flow) => flow.source_unit.data.id === currentUnit.value.id);

    excludedCourses.value = [...relevantFlows.map((flow) => flow.target_course.data.id), contextStore.cid];
}

const addCourse = async (value) => {
    if (value instanceof Event || !value) {
        return;
    }
    const data = {
        source_unit_id: currentUnit.value.id,
        target_course_id: value,
    };
    await flowsStore.createFlow(data);
    quicksearchRef.value.clear();
    updateExcludedCourses();
};

const updateOpen = (value) => {
    emit('update:open', value);
};

const toggleActiveFlow = (flow, newStatus) => {
    flow.active = newStatus;
    flowsStore.updateFlow(flow);
};

const toggleAutoSyncFlow = (flow, newStatus) => {
    flow.auto_sync = newStatus;
    flowsStore.updateFlow(flow);
};

const updateOpenDeleteDialog = (state) => {
    openDeleteDialog.value = state;

    if (!state) {
        contextStore.setSelectedFlow(null);
    }
};

const showDeleteFlow = (flow) => {
    contextStore.setSelectedFlow(flow);
    updateOpenDeleteDialog(true);
};

const deleteFlow = async (withUnit) => {
    await flowsStore.deleteFlow( currentFlow.value, withUnit);
    updateExcludedCourses();
};

watch(
    () => props.open,
    async (newValue) => {
        if (newValue) {
            await fetchCourses();
        }
    }
);
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
                        <td><input type="checkbox" :checked="flow.active" @change="toggleActiveFlow(flow, $event.target.checked)" /></td>
                        <td><input type="checkbox" :checked="flow.auto_sync" @change="toggleAutoSyncFlow(flow, $event.target.checked)" /></td>
                        <td>{{ formatDate(flow.chdate) }}</td>
                        <td>{{ formatDate(flow.mkdate) }}</td>
                        <td class="actions">
                            <StudipActionMenu
                                :context="$gettext('Verteiltes Lernmaterial')"
                                :items="
                                    unitFlows.length > 1
                                        ? [{ id: 1, label: $gettext('Löschen'), icon: 'trash', emit: 'delete' }]
                                        : []
                                "
                                @delete="showDeleteFlow(flow)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
            <form class="default cw-flow-dialog-edit">
                <label>
                    {{ $gettext('Veranstaltung hinzufügen') }}
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
            <DeleteFlow :open="openDeleteDialog" @update:open="updateOpenDeleteDialog" @delete="deleteFlow"/>
            
        </template>
    </StudipDialog>
</template>
