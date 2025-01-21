<script setup>
import { computed, onBeforeMount, ref } from 'vue';
import FlowsTable from './flow/FlowsTable.vue';
import FlowsCards from './flow/FlowsCards.vue';
import DialogCreateFlow from './flow/dialog/CreateFlow.vue';

import { useUnitsStore } from './../stores/units.js';
import { useContextStore } from './../stores/context.js';

const unitStore = useUnitsStore();
const contextStore = useContextStore();

const openCreateDialog = ref(false);
const showCards = ref(false);
const showTable = ref(true);

const selectedUnit = computed(() => contextStore.selectedUnit);

const updateOpenCreateDialog = (state) => {
    openCreateDialog.value = state;

    if (!state) {
        contextStore.setSelectedUnit(null);
    }
};

onBeforeMount(() => {
    unitStore.fetchCoursesUnits();
});
</script>

<template>
    <div class="flow-overview">
        <flows-table v-if="showTable" @create-flow="updateOpenCreateDialog(true)" />
        <flows-cards v-if="showCards" @create-flow="updateOpenCreateDialog(true)" />
        <dialog-create-flow :open="openCreateDialog" @update:open="updateOpenCreateDialog"> </dialog-create-flow>
    </div>
</template>
