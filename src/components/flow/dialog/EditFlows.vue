<script setup>
import { computed } from 'vue';

import { useContextStore } from '../../../stores/context';
import StudipQuicksearch from './../../studip/StudipQuicksearch.vue';
import StudipDialog from './../../studip/StudipDialog.vue';

const contextStore = useContextStore();

const emit = defineEmits(['update:open']);

const courseSearch = computed(() => contextStore.courseSearch);
const currentUnit = computed(() => contextStore.selectedUnit);

const addCourse = (value) => {
    console.log(value);
};

const updateOpen = (value) => {
    emit('update:open', value);
};

const updateFlows = () => {
    console.log('update flow');
    console.log(currentUnit.value);
    emit('update:open', false);
};

</script>

<template>
    <StudipDialog
        :height="780"
        :width="500"
        :title="$gettext('Verteilung bearbeiten')"
        confirm-class="accept"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Speichern')"
        :open="open"
        @update:open="updateOpen"
        @confirm="updateFlows"
    >
        <template #dialogContent>
            <form class="default cw-flow-dialog-edit">
                <label>
                    {{ $gettext('Veranstaltungssuche') }}
                    <StudipQuicksearch :searchtype="courseSearch" name="qs" @select="addCourse" :placeholder="$gettext('Suchen')"></StudipQuicksearch>
                </label>
            </form>
        </template>
    </StudipDialog>
</template>