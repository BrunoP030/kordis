<template>
  <header class="topbar">
    <span class="topbar__titulo">SISTEMA OPERACIONAL</span>

    <div class="topbar__usuario" v-if="authStore.usuario">
      <div class="topbar__usuario-texto">
        <span class="topbar__nome">{{ authStore.usuario.name }}</span>
        <span class="topbar__perfil">{{ authStore.perfis[0] ?? '—' }}</span>
      </div>
      <div class="topbar__avatar">{{ authStore.iniciais }}</div>
    </div>
  </header>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth'
const authStore = useAuthStore()
</script>

<style scoped>
.topbar {
  height: var(--kordis-topbar-height);
  background: var(--kordis-navy-800);
  color: var(--kordis-text-on-navy);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.25rem;
}
.topbar__titulo {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  opacity: 0.85;
}
.topbar__usuario {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.topbar__usuario-texto {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  line-height: 1.2;
}
.topbar__nome {
  font-size: 0.8rem;
  font-weight: 600;
}
.topbar__perfil {
  font-size: 0.7rem;
  opacity: 0.75;
}
.topbar__avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--kordis-teal-500);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}
</style>
