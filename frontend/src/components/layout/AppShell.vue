<template>
  <div class="shell" v-if="authStore.estaAutenticado">
    <AppSidebar />
    <div class="shell__main">
      <AppTopbar />
      <main class="shell__conteudo">
        <router-view />
      </main>
    </div>
  </div>
  <router-view v-else />
</template>

<script setup>
import { useAuthStore } from '../../stores/auth'
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'

const authStore = useAuthStore()
</script>

<style scoped>
.shell {
  display: flex;
  min-height: 100vh;
}
.shell__main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.shell__conteudo {
  flex: 1;
  padding: 2rem;
  background: var(--kordis-bg-page);
}
</style>
