import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { api } from '../api.js';

export const useCoursesStore = defineStore(
    'courses',
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
                const { data } = await api.fetch(`courses/${id}`, {
                    params: {
                    },
                });
                storeRecord(data);
            } catch (err) {
                console.error('fetching course', err);
                errors.value = err;
            }
            isLoading.value = false;
        }

        return {
            records,
            clearRecords,
            isLoading,
            errors,
            all,
            byId,
            fetchById,
        };
    }
);