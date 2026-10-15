<script setup>
import { useRoute } from 'vue-router'
import LhAppLayout from './components/ui/LhAppLayout.vue'
import { useAuthStore } from './stores/auth.js'

const route = useRoute()
// Level, Rang und Prestige kommen vom Server (/me, nach jeder Lerneinheit aktualisiert)
const auth = useAuthStore()
</script>

<template>
  <div class="lh-root">
    <RouterView v-if="route.meta.layout === false" />
    <LhAppLayout v-else v-bind="auth.player" :focus="!!route.meta.focus">
      <RouterView />
    </LhAppLayout>
  </div>
</template>
