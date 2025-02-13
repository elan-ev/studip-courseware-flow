import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { api } from '../api.js';
import { useContextStore } from './context';
import { useCoursesStore } from './courses';
import { useUnitsStore } from './units';

export const useFlowsStore = defineStore('courseware-flows', () => {
    const contextStore = useContextStore();
    const coursesStore = useCoursesStore();
    const unitsStore = useUnitsStore();

    const records = ref(new Map());
    const inProgress = ref(false);
    const errors = ref(false);

    function storeRecord(newRecord) {
        records.value.set(newRecord.id, newRecord);
    }

    function clearRecords() {
        records.value = new Map();
    }

    const all = computed(() => {
        return [...records.value.values()];
    });

    function byId(id) {
        return records.value.get(id);
    }

    async function fetchById(id) {
        inProgress.value = true;
        try {
            const { data } = await api.fetch(`courseware-flows/${id}`, {
                params: {},
            });
            storeRecord(data);
        } catch (err) {
            console.error('fetching flow', err);
            errors.value = err;
        }
        inProgress.value = false;
    }

    async function fetchUnitFlows(unitId) {
        inProgress.value = true;
        return api
            .fetch(`units/${unitId}/courseware-flows`, {
                params: {
                    'page[limit]': 1000,
                },
            })
            .then(({ data }) => {
                data.forEach(storeRecord);
                inProgress.value = false;
            })
            .catch((err) => {
                console.error('fetching unit flows', err);
                errors.value = err;
            });
    }

    async function fetchCourseFlows(courseId = contextStore.cid) {
        inProgress.value = true;
        return api
            .fetch(`courses/${courseId}/courseware-flows`, {
                params: {
                    'page[limit]': 1000,
                },
            })
            .then(({ data }) => {
                data.forEach(storeRecord);
                inProgress.value = false;
            })
            .catch((err) => {
                console.error('fetching course flows', err);
                errors.value = err;
            });
    }

    async function createFlow(flow) {
        inProgress.value = true;
        const { data } = await api.post('courseware-flows', flow);
        await coursesStore.fetchById(data.target_course.data.id);
        storeRecord(data);
        inProgress.value = false;
    }

    async function createFlows(data) {
        inProgress.value = true;
        const sourceUnitId = data['source-unit-id'];
        api
            .create(`/courseware-flows/create-flows`, {
                'source-unit-id': sourceUnitId,
                'target-course-ids': data['target-course-ids'],
            })
            .then(({ data }) => {
                data.forEach(storeRecord);
                unitsStore.fetchById(sourceUnitId);
                inProgress.value = false;
            })
            .catch((err) => {
                // console.error('creating flows', err);
                // errors.value = err;
            });
        
        setTimeout(() => {
            fetchCourseFlows();
            unitsStore.fetchById(sourceUnitId);
        }, 1000);
    }

    async function updateFlow(flow) {
        return api.patch('courseware-flows', { id: flow.id, ...flow }).then(() => {
            fetchById(flow.id);
        });
    }

    async function deleteFlow(flow, withUnit = false) {
        if (!flow.id) return;
        const targetUnitId = flow.target_unit_id;
        inProgress.value = true;
        api.delete('courseware-flows', flow.id).then(() => {
            records.value.delete(flow.id);
            if (withUnit && targetUnitId) {
                try {
                    api.delete('courseware-units', targetUnitId).then(() => {
                        inProgress.value = false;
                    });
                } catch (err) {}
            } else {
                inProgress.value = false;
            }
        });
    }

    async function deleteUnitFlows(unitId, withUnits = false) {
        inProgress.value = true;
        return api
            .post(`units/${unitId}/courseware-flows`, {
                 'with-units': withUnits,
            })
            .then(() => {
                records.value.forEach((record) => {
                    if (record.source_unit.data.id === unitId) {
                        records.value.delete(record.id);
                    }
                    unitsStore.fetchById(unitId);
                });

                inProgress.value = false;
            })
            .catch((err) => {
                console.error('deleting unit flows', err);
                errors.value = err;
            });
    }

    function syncFlow(flow) {
        inProgress.value = true;
        api.post(`courseware-flows/${flow.id}/sync`);
        setTimeout(() => {
            fetchById(flow.id);
            inProgress.value = false;
        }, 1000);
    }

    function syncUnitFlows(unit) {
        inProgress.value = true;
        api.post(`units/${unit.id}/courseware-flows/sync`);
        setTimeout(() => {
            fetchUnitFlows(unit.id);
            inProgress.value = false;
        }, 1000);
    }

    return {
        records,
        inProgress,
        errors,
        all,
        byId,
        fetchById,
        fetchUnitFlows,
        fetchCourseFlows,
        createFlow,
        createFlows,
        updateFlow,
        deleteFlow,
        deleteUnitFlows,
        syncFlow,
        syncUnitFlows,
    };
});
