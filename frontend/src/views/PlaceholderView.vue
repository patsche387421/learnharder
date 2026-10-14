<script setup>
// Platzhalter für die fünf Hauptziele, bis die echten Screens stehen. Titel kommt aus meta.title der Route.
import { useRoute } from 'vue-router'
import LhEmptyState from '../components/ui/LhEmptyState.vue'

const route = useRoute()
</script>

<template>
  <LhEmptyState :title="route.meta.title" text="Im Aufbau." />
</template>
