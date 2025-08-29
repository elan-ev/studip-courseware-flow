<script setup>
import { computed, onMounted, ref, watch } from 'vue';

import StudipActionMenu from '@/components/studip/StudipActionMenu.vue';
import StudipDialog from '@/components/studip/StudipDialog.vue';
import StudipIcon from '@/components/studip/StudipIcon.vue';
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
        .filter((flow) => flow.source_unit_id === currentUnit.value?.id)
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
    const relevantFlows = flowsStore.all.filter((flow) => flow.source_unit_id === currentUnit.value.id);

    updateExcludedCourses();

    for (const flow of relevantFlows) {
        await coursesStore.fetchById(flow.target_course.data.id);
    }
};

const updateExcludedCourses = () => {
    const relevantFlows = flowsStore.all.filter((flow) => flow.source_unit_id === currentUnit.value.id);

    excludedCourses.value = [...relevantFlows.map((flow) => flow.target_course.data.id), contextStore.cid];
};

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
    if (!newStatus) {
        flow.auto_sync = false;
    }
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
    await flowsStore.deleteFlow(currentFlow.value, withUnit);
    updateExcludedCourses();
};

const syncFlow = (flow) => {
    flowsStore.syncFlow(flow);
};

watch(
    () => props.open,
    async (newValue) => {
        if (newValue) {
            await fetchCourses();
        }
    }
);

onMounted(() => {
    if (useContextStore.currentUnit) {
        useFlowsStore.fetchUnitFlows(useContextStore.currentUnit.id);
    }
});
</script>

<template>
    <StudipDialog
        :height="768"
        :width="1200"
        :title="$gettext('Verteilung bearbeiten')"
        :close-text="$gettext('Schließen')"
        :open="open"
        @update:open="updateOpen"
    >
        <template #dialogContent>
            <div class="cw-flow-edit-unit-data">
                <div class="cw-flow-edit-unit-image">
                    <img
                        v-if="currentUnit['structural-element'].data.image.meta"
                        :src="currentUnit['structural-element'].data.image.meta['download-url']"
                        height="40"
                    />
                    <div v-else class="cw-element-image-placeholder">
                        <StudipIcon shape="courseware" :size="24" />
                    </div>
                </div>
                <div class="cw-flow-edit-unit-title">
                    {{ currentUnit['structural-element'].data.title }}
                </div>
            </div>
            <table class="default">
                <colgroup>
                    <col width="*" />
                    <col width="250" />
                    <col width="100" />
                    <col width="100" />
                    <col width="150" />
                    <col width="150" />
                    <col width="50" />
                </colgroup>
                <thead>
                    <tr>
                        <th>{{ $gettext('Veranstaltung') }}</th>
                        <th>{{ $gettext('Status') }}</th>
                        <th>{{ $gettext('Aktiviert') }}</th>
                        <th>{{ $gettext('automatisches Synchronisieren') }}</th>
                        <th>{{ $gettext('letzte Synchronisation') }}</th>
                        <th>{{ $gettext('erstellt am') }}</th>
                        <th class="actions">{{ $gettext('Aktion') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="flow in unitFlows" :key="flow.id">
                        <td>{{ flow.target_course.attributes?.title || '---' }}</td>
                        <td>
                            {{
                                !flow.target_unit || Object.keys(flow.target_unit).length === 0
                                    ? $gettext('⚠️ Ziel-Lernmaterial nicht gefunden')
                                    : flow.active
                                    ? $gettext(
                                          flow.status === 'idle'
                                              ? 'bereit'
                                              : flow.status === 'syncing'
                                              ? 'synchronisiere'
                                              : flow.status === 'copying'
                                              ? 'kopiere'
                                              : flow.status === 'failed'
                                              ? 'fehlgeschlagen'
                                              : 'unbekannt'
                                      )
                                    : $gettext('deaktiviert')
                            }}
                        </td>

                        <td>
                            <input
                                type="checkbox"
                                :checked="flow.active"
                                @change="toggleActiveFlow(flow, $event.target.checked)"
                            />
                        </td>
                        <td>
                            <input
                                type="checkbox"
                                :checked="flow.auto_sync"
                                :disabled="!flow.active"
                                @change="toggleAutoSyncFlow(flow, $event.target.checked)"
                            />
                        </td>
                        <td>{{ formatDate(flow.sync_date) }}</td>
                        <td>{{ formatDate(flow.mkdate) }}</td>
                        <td class="actions">
                            <StudipActionMenu
                                :context="$gettext('Verteiltes Lernmaterial')"
                                :items="
                                    [
                                        ...(flow.active
                                            ? [
                                                  {
                                                      id: 2,
                                                      label: $gettext('Synchronisieren'),
                                                      icon: 'refresh',
                                                      emit: 'sync',
                                                  },
                                              ]
                                            : []),
                                    ].concat([{ id: 1, label: $gettext('Löschen'), icon: 'trash', emit: 'delete' }])
                                "
                                @delete="showDeleteFlow(flow)"
                                @sync="syncFlow(flow)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
            <form class="default cw-flow-dialog-edit-form">
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
            <DeleteFlow :open="openDeleteDialog" @update:open="updateOpenDeleteDialog" @delete="deleteFlow" />
        </template>
    </StudipDialog>
</template>

<style lang="scss">
.cw-flow-dialog-edit-form {
    .quicksearch_container {
        display: flex;
    }
}

.cw-flow-edit-unit-data {
    display: flex;
    flex-direction: row;
    gap: 10px;
    height: 40px;
    margin-bottom: 16px;
    overflow: hidden;

    .cw-flow-edit-unit-title {
        flex-grow: 1;
        line-height: 40px;
        font-size: 16px;
    }
}
</style>
