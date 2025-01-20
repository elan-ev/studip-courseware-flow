import { createApp } from 'vue'
import App from './CoursewareFlowApp.vue'
import { createGettext } from 'vue3-gettext';
// import translations from './locales/translations.json';
import { createPinia } from 'pinia';
import { useContextStore } from './stores/context';

const app = createApp(App);

const gettext = createGettext({
    availableLanguages: {
      en: "English",
      de: "Deutsch",
    },
    defaultLanguage: "de",
});
app.use(gettext);

const pinia = createPinia();
app.use(pinia);


const elem = document.getElementById('courseware-flow-app');
const contextStore = useContextStore();
if (elem?.attributes?.['is-teacher'] !== undefined) {
  const isTeacher = JSON.parse(elem.attributes['is-teacher'].value);
  contextStore.setTeacherStatus(isTeacher);
}
if (elem?.attributes?.['preferred-language'] !== undefined) {
  const preferredLanguage = elem.attributes['preferred-language'].value;
  contextStore.setPreferredLanguage(preferredLanguage);
}
if (elem?.attributes?.['course-search'] !== undefined) {
    const courseSearch = elem.attributes['course-search'].value;
    contextStore.setCourseSearch(courseSearch);
  }



app.mount('#courseware-flow-app');