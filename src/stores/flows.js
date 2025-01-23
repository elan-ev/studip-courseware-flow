import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { api } from '../api.js';

export const useFlowsStore = defineStore(
    'courseware-flows',
    () => {
        const records = ref(new Map());
        const isLoading = ref(false);
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
            isLoading.value = true;
            try {
                const { data } = await api.fetch(`courseware-flows/${id}`, {
                    params: {
                        include: 'structural-element',
                    },
                });
                storeRecord(data);
            } catch (err) {
                console.error('fetching flow', err);
                errors.value = err;
            }
            isLoading.value = false;
        }

        async function fetchUnitsFlows(unitId) {
            isLoading.value = true;
            return api
            .fetch(`units/${unitId}/courseware-flows`, {
                params: {
                    'page[limit]': 1000,
                },
            })
            .then(({ data }) => {
                data.forEach(storeRecord);
                isLoading.value = false;
            })
            .catch((err) => {
                console.error('fetching units flows', err);
                errors.value = err;
            });
        }

        return {
            records,
            isLoading,
            errors,
            all,
            byId,
            fetchById,
            fetchUnitsFlows,
        };
    }
);