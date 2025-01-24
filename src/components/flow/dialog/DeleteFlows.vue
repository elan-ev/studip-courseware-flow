<script setup>
import { computed } from 'vue';

import { useContextStore } from '../../../stores/context';
import { useFlowsStore } from '../../../stores/flows';
import StudipDialog from '../../studip/StudipDialog.vue';

const contextStore = useContextStore();
const flowsStore = useFlowsStore();

const emit = defineEmits(['update:open']);

const currentUnit = computed(() => contextStore.selectedUnit);

const updateOpen = (value) => {
    emit('update:open', value);
};

const deleteUnitFlows = () => {
    flowsStore.deleteUnitFlows(currentUnit.value.id);
    emit('update:open', false);
};
</script>

<template>
    <StudipDialog
        :height="200"
        :title="$gettext('Verteilungen löschen')"
        confirm-class="trash"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Löschen')"
        :open="open"
        :question="$gettext('Möchten Sie die Verteilungen für dieses Lernmaterial unwiderruflich löschen?')"
        @update:open="updateOpen"
        @confirm="deleteUnitFlows"
        >
        
        </StudipDialog>
</template>