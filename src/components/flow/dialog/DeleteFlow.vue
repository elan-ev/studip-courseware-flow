<script setup>
import { computed } from 'vue';

import { useContextStore } from './../../../stores/context';
import StudipDialog from '../../studip/StudipDialog.vue';

const contextStore = useContextStore();

const emit = defineEmits(['update:open']);

const currentFlow = computed(() => contextStore.selectedFlow);

const deleteFlow = () => {
    console.log('delete flow');
    console.log(currentFlow.value);
    emit('update:open', false);
};
</script>

<template>
    <StudipDialog
        :height="200"
        :title="$gettext('Verteilung löschen')"
        confirm-class="trash"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Löschen')"
        :open="open"
        :question="$gettext('Möchten Sie die Verteilung unwiderruflich löschen?')"
        @update:open="updateOpen"
        @confirm="deleteFlow"
        >
        
        </StudipDialog>
</template>