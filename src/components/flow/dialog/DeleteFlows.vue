<script setup>
import { computed, ref } from 'vue';

import { useContextStore } from '@/stores/context';
import { useFlowsStore } from '@/stores/flows';
import StudipDialog from '@/components/studip/StudipDialog.vue';

const contextStore = useContextStore();
const flowsStore = useFlowsStore();

const emit = defineEmits(['update:open']);

const withUnits = ref(false);

const currentUnit = computed(() => contextStore.selectedUnit);

const updateOpen = (value) => {
    emit('update:open', value);
};

const deleteUnitFlows = () => {
    flowsStore.deleteUnitFlows(currentUnit.value.id, withUnits.value);
    emit('update:open', false);
    withUnits.value = false;
};
</script>

<template>
    <StudipDialog
        class="cw-flow-dialog-delete"
        :height="280"
        :title="$gettext('Verteilungen aufheben')"
        confirm-class="accept"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Bestätigen')"
        :open="open"
        :question="$gettext('Möchten Sie die Verteilungen für dieses Lernmaterial unwiderruflich aufheben?')"
        @update:open="updateOpen"
        @confirm="deleteUnitFlows"
        >
        <template #dialogContent>
            <p class="cw-flow-dialog-option">
                <label>
                    <input type="checkbox" v-model="withUnits" />
                    {{ $gettext('Löschen des Lernmaterials in den Zielveranstaltungen') }}
                </label>
            </p>
        </template>
        </StudipDialog>
</template>