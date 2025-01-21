<script setup>
import { computed, ref } from 'vue';
import StudipActionMenu from './../studip/StudipActionMenu.vue';
import StudipIcon from './../studip/StudipIcon.vue';
import DialogDeleteFlow from './dialog/DeleteFlow.vue';
import DialogEditFlow from './dialog/EditFlow.vue';
import { useUnitsStore } from './../../stores/units.js';
import { useContextStore } from './../../stores/context.js';

const unitStore = useUnitsStore();
const contextStore = useContextStore();

const emit = defineEmits(['create-flow']);

const openEditDialog = ref(false);
const openDeleteDialog = ref(false);

const selectedFlow = computed(() => contextStore.selectedFlow);
const selectedUnit = computed(() => contextStore.selectedUnit);

const units = computed(() => unitStore.all);

const flows = computed(() => [
    {
        id: 1,
        unit: {
            id: 1,
            name: 'Test',
        },
    },
    {
        id: 2,
        unit: {
            id: 2,
            name: 'Test 2',
        },
    },
]);

const noneDistributedUnits = computed(() =>
    units.value.filter((unit) => !flows.value.some((flow) => flow.unit.id === unit.id))
);

const updateOpenEditDialog = (state) => {
    openEditDialog.value = state;
    if (!state) {
        contextStore.setSelectedFlow(null);
    }
};
const editFlow = (flow) => {
    contextStore.setSelectedFlow(flow);
    updateOpenEditDialog(true);
};

const updateOpenDeleteDialog = (state) => {
    openDeleteDialog.value = state;
    if (!state) {
        contextStore.setSelectedFlow(null);
    }
};
const deleteFlow = (flow) => {
    contextStore.setSelectedFlow(flow);
    updateOpenDeleteDialog(true);
};

const distributeUnit = (unit) => {
    if (!unit) {
        console.error('Kein gültiges Unit-Objekt übergeben');
        return;
    }
    contextStore.setSelectedUnit(unit);
    emit('create-flow');
};

const showData = () => {
    console.log('show data');
    console.log(noneDistributedUnits.value);
};
</script>

<template>
    <div class="flow-tables">
        <table class="default">
            <caption>
                {{
                    $gettext('Verteilte Lernmaterialien')
                }}
            </caption>
            <colgroup>
                <col width="100" />
                <col width="*" />
                <col width="40" />
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th>{{ $gettext('Name') }}</th>
                    <th class="actions">{{ $gettext('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="flow in flows" :key="flow.id">
                    <td></td>
                    <td>{{ flow.unit.name }}</td>
                    <td class="actions">
                        <StudipActionMenu
                            :context="$gettext('Verteiltes Lernmaterial')"
                            :items="[
                                { id: 1, label: $gettext('Bearbeiten'), icon: 'edit', emit: 'edit' },
                                { id: 2, label: $gettext('Löschen'), icon: 'trash', emit: 'delete' },
                            ]"
                            @edit="editFlow(flow)"
                            @delete="deleteFlow(flow)"
                        />
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="default" @click="showData">
            <caption>
                {{
                    $gettext('Nicht verteilte Lernmaterialien')
                }}
            </caption>
            <colgroup>
                <col width="100" />
                <col width="*" />
                <col width="40" />
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th>{{ $gettext('Name') }}</th>
                    <th class="actions">{{ $gettext('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="unit in noneDistributedUnits" :key="unit.id">
                    <td>
                        <img
                            v-if="unit['structural-element'].data.image.meta"
                            :src="unit['structural-element'].data.image.meta['download-url']"
                            height="40"
                        />
                        <div v-else class="cw-element-image-placeholder">
                            <StudipIcon shape="courseware" :size="36" />
                        </div>
                    </td>
                    <td>{{ unit['structural-element'].data.title }}</td>
                    <td class="actions">
                        <StudipActionMenu
                            :context="$gettext('Nicht verteiltes Lernmaterial')"
                            :items="[{ id: 1, label: $gettext('Verteilen'), icon: 'add', emit: 'distribute' }]"
                            @distribute="distributeUnit(unit)"
                        />
                    </td>
                </tr>
            </tbody>
        </table>

        <dialog-delete-flow :open="openDeleteDialog" @update:open="updateOpenDeleteDialog" />
        <dialog-edit-flow :open="openEditDialog" @update:open="updateOpenEditDialog" />
    </div>
</template>

<style lang="scss">
.flow-tables {
    display: flex;
    flex-direction: column;
    gap: 20px;

    table.default {
        width: 100%;
        tbody {
            tr {
                td {
                    height: 40px;
                }
            }
        }
    }
}

.cw-element-image-placeholder {
    width: 72px;
    height: 40px;
    background-color: #f0f0f0;
    text-align: center;

    img {
        margin: 2px 0;
    }
}
</style>
