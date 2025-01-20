import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { api } from '../api.js';
import { useContextStore } from './context';

export const useUnitsStore = defineStore(
    'courseware-units',
    () => {
        const contextStore = useContextStore();

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
                const { data } = await api.fetch(`courseware-units/${id}`, {
                    params: {
                        include: 'structural-element',
                    },
                });
                storeRecord(data);
            } catch (err) {
                console.error('fetching unit', err);
                errors.value = err;
            }
            isLoading.value = false;
        }

        async function fetchCoursesUnits() {
            isLoading.value = true;
            return api
            .fetch(`courses/${contextStore.cid}/courseware-units`, {
                params: {
                    include: 'structural-element',
                    'page[limit]': 1000,
                },
            })
            .then(({ data }) => {
                data.forEach(storeRecord);
                isLoading.value = false;
            })
            .catch((err) => {
                console.error('fetching courses units', err);
                errors.value = err;
            })
            .finally(() => {
                isLoading.value = false;
            });
        }
        
        return {
            all,
            byId,
            fetchById,
            fetchCoursesUnits,
            clearRecords,
        }
    }
)