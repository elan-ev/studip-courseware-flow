<script setup>
import { computed, onBeforeMount, ref } from 'vue';
import FlowsTable from './flow/FlowsTable.vue';
import FlowsCards from './flow/FlowsCards.vue';
import DialogCreateFlow from './flow/dialog/CreateFlow.vue';

import { useUnitsStore } from './../stores/units.js';

const unitStore = useUnitsStore();

const openCreateDialog = ref(false);

const updateOpenCreateDialog = (state) => {
    openCreateDialog.value = state;
};

onBeforeMount(() => {
    console.log('mounted');

    // unitStore.fetchCoursesUnits();
});
</script>

<template>
    <div class="flow-overview">
        <flows-table v-if="showTable" @create-flow="updateOpenCreateDialog(true)" />
        <flows-cards v-if="showCards" @create-flow="updateOpenCreateDialog(true)" />
        <button @click="updateOpenCreateDialog(true)">Create Flow</button>
        <dialog-create-flow :open="openCreateDialog" @update:open="updateOpenCreateDialog"> </dialog-create-flow>
    </div>
</template>
