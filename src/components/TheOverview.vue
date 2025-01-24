<script setup>
import { computed, onBeforeMount, ref } from 'vue';
import FlowsTable from './flow/FlowsTable.vue';
import FlowsCards from './flow/FlowsCards.vue';
import DialogCreateFlow from './flow/dialog/CreateFlow.vue';

import { useContextStore } from './../stores/context.js';
import { useFlowsStore } from './../stores/flows.js';
import { useUnitsStore } from './../stores/units.js';

const contextStore = useContextStore();
const flowsStore = useFlowsStore();
const unitStore = useUnitsStore();

const openCreateDialog = ref(false);
const showCards = ref(false);
const showTable = ref(true);

const updateOpenCreateDialog = (state) => {
    openCreateDialog.value = state;

    if (!state) {
        contextStore.setSelectedUnit(null);
    }
};

onBeforeMount(() => {
    unitStore.fetchCourseUnits();
    flowsStore.fetchCourseFlows();

});
</script>

<template>
    <div class="flow-overview">
        <flows-table v-if="showTable" @create-flow="updateOpenCreateDialog(true)" />
        <flows-cards v-if="showCards" @create-flow="updateOpenCreateDialog(true)" />
        <dialog-create-flow :open="openCreateDialog" @update:open="updateOpenCreateDialog"> </dialog-create-flow>
    </div>
</template>
