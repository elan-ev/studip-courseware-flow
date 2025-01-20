<script setup>

import { computed, onBeforeMount } from 'vue';

import { useContextStore } from './../../../stores/context';
import StudipQuicksearch from './../../studip/StudipQuicksearch.vue';
import StudipDialog from './../../studip/StudipDialog.vue';

const contextStore = useContextStore();

const courseSearch = computed(() => contextStore.courseSearch);

const addCourse = (value) => {
    console.log(value);
};

</script>

<template>
        <StudipDialog
        :height="780"
        :width="500"
        :title="$gettext('Flow hinzufügen')"
        confirm-class="accept"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Erstellen')"
        :open="open"
        @update:open="updateOpen"
        @confirm="addFlow"
    >
        <template #dialogContent>
            <StudipQuicksearch :searchtype="courseSearch" name="qs" @select="addCourse" :placeholder="$gettext('Suchen')"></StudipQuicksearch>
        </template>
    </StudipDialog>
    

</template>