import { computed, ref } from 'vue';
import { useContextStore } from '@/stores/context.js';
import { useFlowsStore } from '@/stores/flows.js';
import { useUnitsStore } from '@/stores/units.js';

export function useFlows(emit) {
    const contextStore = useContextStore();
    const flowsStore = useFlowsStore();
    const unitStore = useUnitsStore();

    const openEditDialog = ref(false);
    const openDeleteDialog = ref(false);

    const flows = computed(() => flowsStore.all);
    const units = computed(() => unitStore.all);

    const distributedUnits = computed(() =>
        units.value
            .filter((unit) => flows.value.some((flow) => flow.source_unit_id === unit.id))
            .map((unit) => {
                const relatedFlows = flows.value.filter((flow) => flow.source_unit_id === unit.id);
    
                const syncDate = relatedFlows.length
                    ? relatedFlows.reduce((earliest, flow) =>
                        earliest && earliest < flow.sync_date ? earliest : flow.sync_date
                    , null)
                    : null;
    
                return {
                    ...unit,
                    syncDate,
                    status: relatedFlows.some(flow => flow.status === 'copying') ? 'copying' : 'synced',
                };
            })
    );
    

    const noneDistributedUnits = computed(() =>
        units.value.filter((unit) => !flows.value.some((flow) => flow.source_unit_id === unit.id))
    );

    const updateOpenEditDialog = (state) => {
        openEditDialog.value = state;
        if (!state) {
            contextStore.setSelectedUnit(null);
        }
    };

    const editUnitFlows = (unit) => {
        contextStore.setSelectedUnit(unit);
        updateOpenEditDialog(true);
    };

    const updateOpenDeleteDialog = (state) => {
        openDeleteDialog.value = state;
        if (!state) {
            contextStore.setSelectedUnit(null);
        }
    };

    const deleteUnitFlows = (unit) => {
        contextStore.setSelectedUnit(unit);
        updateOpenDeleteDialog(true);
    };

    const syncUnitFlows = (unit) => {
        flowsStore.syncUnitFlows(unit);
    };

    const distributeUnit = (unit) => {
        if (!unit) {
            console.error('Kein gültiges Unit-Objekt übergeben');
            return;
        }
        contextStore.setSelectedUnit(unit);
        emit('create-flow');
    };
    

    return {
        openEditDialog,
        openDeleteDialog,
        flows,
        distributedUnits,
        noneDistributedUnits,
        updateOpenEditDialog,
        editUnitFlows,
        updateOpenDeleteDialog,
        deleteUnitFlows,
        syncUnitFlows,
        distributeUnit
    };
}
