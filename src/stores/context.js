import { ref, computed } from 'vue';
import { defineStore } from 'pinia';

export const useContextStore = defineStore('courseware-flow-context', () => {
    const isTeacher = ref(false);
    const preferredLanguage = ref('de_DE');
    const courseSearch = ref(null);

    const selectedUnit = ref(null);
    const selectedFlow = ref(null);

    const viewMode = ref('cards');

    const isGerman = computed(() => preferredLanguage.value === 'de_DE');

    const cid = computed(() => {
        return window.STUDIP.URLHelper.parameters.cid;
    });

    function setTeacherStatus(status) {
        isTeacher.value = status;
    }
    function setPreferredLanguage(language) {
        preferredLanguage.value = language;
    }
    function setCourseSearch(search) {
        courseSearch.value = search;
    }

    function setSelectedUnit(unit) {
        selectedUnit.value = unit;
    }

    function setSelectedFlow(flow) {
        selectedFlow.value = flow;
    }

    function setViewMode(mode) {
        viewMode.value = mode;
    }
    

    return {
        cid,
        courseSearch,
        isTeacher,
        preferredLanguage,
        selectedUnit,
        selectedFlow,
        viewMode,

        setTeacherStatus,
        setPreferredLanguage,
        setCourseSearch,
        setViewMode,

        setSelectedUnit,
        setSelectedFlow,

    };
});

