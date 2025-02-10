<script setup>
import { computed } from 'vue';

import { useContextStore } from '@/stores/context';
const contextStore = useContextStore();

const viewMode = computed(() => contextStore.viewMode);

const setView = (mode) => {
    contextStore.setViewMode(mode);
};

</script>

<template>
    <div id="sidebar-views" class="sidebar-widget">
        <div class="sidebar-widget-header">{{ $gettext('Ansichten') }}</div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-views" aria-label="Ansichten">
                <li :class="{ active: viewMode === 'tiles' }"><a href="#" @click="setView('tiles')">{{ $gettext('Kachelansicht') }}</a></li>
                <li :class="{ active: viewMode === 'list' }"><a href="#" @click="setView('list')">{{ $gettext('Listenansicht') }}</a></li>
            </ul>
        </div>
    </div>
</template>
