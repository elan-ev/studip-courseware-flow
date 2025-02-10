<script setup>
import TheOverview from './components/TheOverview.vue';
import SidebarViews from './components/sidebar/SidebarViews.vue';
</script>

<template>
    <TheOverview />

    <Teleport to="#courseware-flow-view-widget">
        <SidebarViews />
    </Teleport>
</template>