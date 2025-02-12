<script setup>
import { useColors } from '@/composables/useColors';
import { useFlows } from '@/composables/useFlows';
import StudipActionMenu from '@/components/studip/StudipActionMenu.vue';
import StudipIcon from '@/components/studip/StudipIcon.vue';
import DialogDeleteFlows from '@/components/flow/dialog/DeleteFlows.vue';
import DialogEditFlows from '@/components/flow/dialog/EditFlows.vue';

const emit = defineEmits(['create-flow']);

const { getHexByColorName } = useColors();

const {
    openEditDialog,
    openDeleteDialog,
    distributedUnits,
    noneDistributedUnits,
    updateOpenEditDialog,
    editUnitFlows,
    updateOpenDeleteDialog,
    deleteUnitFlows,
    syncUnitFlows,
    distributeUnit,
} = useFlows(emit);
</script>

<template>
    <div class="flows-grid">
        <h2>{{ $gettext('Verteilte Lernmaterialien') }}</h2>
        <div class="grid">
            <div v-for="unit in distributedUnits" :key="unit.id" class="flow-card">
                <div class="image-section">
                    <img
                        v-if="unit['structural-element'].data.image.meta"
                        :src="unit['structural-element'].data.image.meta['download-url']"
                        alt=""
                    />
                    <div
                        v-else
                        class="cw-placeholder"
                        :style="{ backgroundColor: getHexByColorName(unit['structural-element'].data.payload.color) }"
                    >
                        <StudipIcon shape="courseware" role="info_alt" :size="110" />
                    </div>
                </div>
                <div
                    class="info-section"
                    :style="{
                        borderTopColor: getHexByColorName(unit['structural-element'].data.payload.color),
                        borderBottomColor: getHexByColorName(unit['structural-element'].data.payload.color),
                    }"
                >
                    <div class="header">
                        <h3>{{ unit['structural-element'].data.title }}</h3>
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
                    </div>
                </div>
            </div>
        </div>

        <h2>{{ $gettext('Nicht verteilte Lernmaterialien') }}</h2>
        <div class="grid">
            <div v-for="unit in noneDistributedUnits" :key="unit.id" class="flow-card">
                <div class="image-section">
                    <img
                        v-if="unit['structural-element'].data.image.meta"
                        :src="unit['structural-element'].data.image.meta['download-url']"
                        alt=""
                    />
                    <div
                        v-else
                        class="cw-placeholder"
                        :style="{ backgroundColor: getHexByColorName(unit['structural-element'].data.payload.color) }"
                    >
                        <StudipIcon shape="courseware" role="info_alt" :size="110" />
                    </div>
                </div>
                <div
                    class="info-section"
                    :style="{
                        borderTopColor: getHexByColorName(unit['structural-element'].data.payload.color),
                        borderBottomColor: getHexByColorName(unit['structural-element'].data.payload.color),
                    }"
                >
                    <div class="header">
                        <h3>{{ unit['structural-element'].data.title }}</h3>
                        <StudipActionMenu
                            :context="$gettext('Nicht verteiltes Lernmaterial')"
                            :items="[{ id: 1, label: $gettext('Verteilen'), icon: 'add', emit: 'distribute' }]"
                            @distribute="distributeUnit(unit)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <DialogDeleteFlows :open="openDeleteDialog" @update:open="updateOpenDeleteDialog" />
        <DialogEditFlows :open="openEditDialog" @update:open="updateOpenEditDialog" />
    </div>
</template>

<style lang="scss">
.flows-grid {
    display: flex;
    flex-direction: column;

    h2:first-of-type {
        margin-top: 0;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(270px, 0));
        gap: 10px;
        margin-bottom: 3em;

        .flow-card {
            max-width: 270px;
            height: 270px;

            .image-section {
                height: 180px;
                img {
                    width: 100%;
                    height: auto;
                    object-fit: cover;
                }

                .cw-placeholder {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 180px;
                    img {
                        height: 110px;
                        object-fit: contain;
                    }
                }
            }

            .info-section {
                height: calc(100% - 180px);
                padding: 1em;
                border: solid thin #d8d8d8;
                border-top-width: 4px;
                border-bottom-width: 4px;

                .header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;

                    h3 {
                        margin: 0;
                    }
                }
            }
        }
    }
}
</style>
