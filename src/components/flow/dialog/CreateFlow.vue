<script setup>
import { computed } from 'vue';

import { useContextStore } from './../../../stores/context';
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

const addFlow = () => {
    console.log('add flow');
    console.log(currentUnit.value);
    emit('update:open', false);
};

</script>

<template>
        <StudipDialog
        :height="780"
        :width="500"
        :title="$gettext('Verteilung hinzufügen')"
        confirm-class="accept"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Erstellen')"
        :open="open"
        @update:open="updateOpen"
        @confirm="addFlow"
    >
        <template #dialogContent>
            <form class="default cw-flow-dialog-create">
                <label>
                    {{ $gettext('Veranstaltungssuche') }}
                    <StudipQuicksearch :searchtype="courseSearch" name="qs" @select="addCourse" :placeholder="$gettext('Suchen')"></StudipQuicksearch>
                </label>
            </form>
        </template>
    </StudipDialog>
    

</template>

<style lang="scss">
.cw-flow-dialog-create {
    .quicksearch_container {
        display: flex;
        flex-direction: column;

        input[type="text"] {
            width: 100%;
            outline: none;
        }
        .dropdownmenu {
            max-width: unset;
            max-height: unset;
            width: 100%;
            top: 1px;

            .autocomplete__results {
                width: calc(100% - 4px);
            }
        }
    }
}

</style>