<script setup>
import { ref } from 'vue';

import StudipDialog from '@/components/studip/StudipDialog.vue';

const withUnit = ref(false);

const emit = defineEmits(['update:open']);

const updateOpen = (value) => {
    emit('update:open', value);
};

const deleteFlow = () => {
    emit('delete', withUnit.value);
    emit('update:open', false);
    withUnit.value = false;
};
</script>

<template>
    <StudipDialog
        class="cw-flow-dialog-delete"
        :height="280"
        :title="$gettext('Verteilungen löschen')"
        confirm-class="trash"
        :close-text="$gettext('Abbrechen')"
        :confirm-text="$gettext('Löschen')"
        :question="$gettext('Sind Sie sicher, dass Sie diese Verteilung unwiderruflich löschen möchten?')"
        :open="open"
        @update:open="updateOpen"
        @confirm="deleteFlow"
    >
        <template #dialogContent>
            <p class="cw-flow-dialog-option">
                <label>
                    <input type="checkbox" v-model="withUnit" />
                    {{ $gettext('Löschen inklusive Lernmaterial in der Zielveranstaltung') }}
                </label>
            </p>
        </template>
    </StudipDialog>
</template>

<style lang="scss">
.cw-flow-dialog-delete {
    .studip-dialog-content {
        flex-direction: column;
        .cw-flow-dialog-option {
            border-top: 1px solid var(--dark-gray-color-10);
            padding-top: 10px;
            margin-top: 10px;
        }
    }
}
</style>
