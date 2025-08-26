<script setup>
import { useColors } from '@/composables/useColors';
import { useFlows } from '@/composables/useFlows';
import StudipActionMenu from '@/components/studip/StudipActionMenu.vue';
import StudipIcon from '@/components/studip/StudipIcon.vue';
import DialogDeleteFlows from '@/components/flow/dialog/DeleteFlows.vue';
import DialogEditFlows from '@/components/flow/dialog/EditFlows.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
const { formatDate } = useDateFormatter();

const emit = defineEmits(['create-flow']);

const { getHexByColorName } = useColors();

const {
    openEditDialog, openDeleteDialog,
    distributedUnits, noneDistributedUnits,
    updateOpenEditDialog, editUnitFlows,
    updateOpenDeleteDialog, deleteUnitFlows,
    syncUnitFlows, distributeUnit
} = useFlows(emit);
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
                <col width="10" />
                <col width="100" />
                <col width="*" />
                <col width="200" />
                <col width="40" />
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th>{{ $gettext('Name') }}</th>
                    <th>{{ $gettext('letzte Synchronisation') }}</th>
                    <th class="actions">{{ $gettext('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="unit in distributedUnits" :key="unit.id">
                    <td :style="{ backgroundColor: getHexByColorName(unit['structural-element'].data.payload.color)}"></td>
                    <td>
                        <img
                            v-if="unit['structural-element'].data.image.meta"
                            :src="unit['structural-element'].data.image.meta['download-url']"
                            height="40"
                        />
                        <div v-else class="cw-element-image-placeholder">
                            <StudipIcon shape="courseware" :size="24" />
                        </div>
                    </td>
                    <td>{{ unit['structural-element'].data.title }}</td>
                    <td>
                        <template v-if="unit.status !== 'copying'">
                            {{ unit.syncDate ? formatDate(unit.syncDate) : '---' }}
                        </template>
                    </td>
                    <td class="actions">
                        <StudipActionMenu
                            :context="$gettext('Verteiltes Lernmaterial')"
                            :items="[
                                { id: 1, label: $gettext('Synchronisieren'), icon: 'refresh', emit: 'sync' },
                                { id: 2, label: $gettext('Bearbeiten'), icon: 'edit', emit: 'edit' },
                                { id: 3, label: $gettext('Löschen'), icon: 'trash', emit: 'delete' },
                            ]"
                            @edit="editUnitFlows(unit)"
                            @delete="deleteUnitFlows(unit)"
                            @sync="syncUnitFlows(unit)"
                        />
                    </td>
                </tr>
                <tr v-if="distributedUnits.length === 0">
                    <td colspan="5">
                        {{ $gettext('Keine verteilten Lernmaterialien') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="default">
            <caption>
                {{
                    $gettext('Nicht verteilte Lernmaterialien')
                }}
            </caption>
            <colgroup>
                <col width="10" />
                <col width="100" />
                <col width="*" />
                <col width="40" />
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th>{{ $gettext('Name') }}</th>
                    <th class="actions">{{ $gettext('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="unit in noneDistributedUnits" :key="unit.id">
                    <td :style="{ backgroundColor: getHexByColorName(unit['structural-element'].data.payload.color)}"></td>
                    <td>
                        <img
                            v-if="unit['structural-element'].data.image.meta"
                            :src="unit['structural-element'].data.image.meta['download-url']"
                            height="40"
                        />
                        <div v-else class="cw-element-image-placeholder">
                            <StudipIcon shape="courseware" :size="24" />
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
                <tr v-if="noneDistributedUnits.length === 0">
                    <td colspan="4">
                        {{ $gettext('Keine nicht verteilten Lernmaterialien') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <DialogDeleteFlows :open="openDeleteDialog" @update:open="updateOpenDeleteDialog" />
        <DialogEditFlows :open="openEditDialog" @update:open="updateOpenEditDialog" />
    </div>
</template>

<style lang="scss">
.flow-tables {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 1200px;

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
    width: 60px;
    height: 40px;
    background-color: #f0f0f0;
    text-align: center;

    img {
        margin: 8px 0;
    }
}
</style>
