<script setup>
import { computed } from 'vue';

import { useContextStore } from './../../../stores/context';
import StudipQuicksearch from './../../studip/StudipQuicksearch.vue';
import StudipDialog from './../../studip/StudipDialog.vue';

const contextStore = useContextStore();

const emit = defineEmits(['update:open']);

const courseSearch = computed(() => contextStore.courseSearch);
const currentFlow = computed(() => contextStore.selectedFlow);

const addCourse = (value) => {
    console.log(value);
};

const updateFlow = () => {
    console.log('update flow');
    console.log(currentFlow.value);
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
        @confirm="updateFlow"
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