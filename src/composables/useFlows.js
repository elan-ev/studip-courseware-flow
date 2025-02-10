import { computed, ref } from 'vue';
import { useContextStore } from '@/stores/context.js';
import { useFlowsStore } from '@/stores/flows.js';
import { useUnitsStore } from '@/stores/units.js';

export function useFlows() {
    const contextStore = useContextStore();
    const flowsStore = useFlowsStore();
    const unitStore = useUnitsStore();

    const openEditDialog = ref(false);
    const openDeleteDialog = ref(false);

    const flows = computed(() => flowsStore.all);
    const units = computed(() => unitStore.all);

    const distributedUnits = computed(() =>
        units.value.filter((unit) => flows.value.some((flow) => flow.source_unit.data.id === unit.id))
    );

    const noneDistributedUnits = computed(() =>
        units.value.filter((unit) => !flows.value.some((flow) => flow.source_unit.data.id === unit.id))
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

    return {
        contextStore,
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
    };
}
