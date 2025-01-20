import { ref, computed } from 'vue';
import { defineStore } from 'pinia';

export const useContextStore = defineStore('courseware-flow-context', () => {
    const isTeacher = ref(false);
    const preferredLanguage = ref('de_DE');
    const courseSearch = ref(null);

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
    

    return {
        cid,
        courseSearch,
        isTeacher,
        preferredLanguage,

        setTeacherStatus,
        setPreferredLanguage,
        setCourseSearch,
    };
});

