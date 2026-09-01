<template>
  <aside class="sidebar" :class="{ 'sidebar--collapsed': ui.sidebarCollapsed }">
    <div class="sidebar__brand">
      <button class="sidebar__toggle" @click="ui.alternarSidebar" aria-label="Recolher menu">
        <span class="material-icons">menu</span>
      </button>
      <img
        v-if="!ui.sidebarCollapsed"
        class="sidebar__logo"
        src="../../assets/brand/kordis-logo.png"
        alt="Kordis"
      />
    </div>

    <nav class="sidebar__nav">
      <!--
        Este v-for é a ÚNICA coisa que precisa existir aqui.
        Novos itens de menu: edite src/config/navigation.js, não este arquivo.
      -->
      <router-link
        v-for="item in itensVisiveis"
        :key="item.routeName"
        :to="{ name: item.routeName }"
        class="sidebar__item"
        :title="ui.sidebarCollapsed ? item.label : null"
      >
        <span class="material-icons">{{ item.icon }}</span>
        <span v-if="!ui.sidebarCollapsed" class="sidebar__label">{{ item.label }}</span>
      </router-link>
    </nav>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useUiStore } from '../../stores/ui'
import { useAuthStore } from '../../stores/auth'
import navigation from '../../config/navigation'

const ui = useUiStore()
const authStore = useAuthStore()

// some sozinho o item cujo perfil logado não tem a permissão exigida
const itensVisiveis = computed(() => navigation.filter((item) => authStore.tem(item.permission)))
</script>

<style scoped>
.sidebar {
  width: var(--kordis-sidebar-width);
  background: var(--kordis-bg-sidebar);
  border-right: 1px solid var(--kordis-border);
  display: flex;
  flex-direction: column;
  transition: width 0.2s ease;
  overflow: hidden;
}
.sidebar--collapsed {
  width: var(--kordis-sidebar-width-collapsed);
}

.sidebar__brand {
  height: var(--kordis-topbar-height);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 0.75rem;
  border-bottom: 1px solid var(--kordis-border);
  flex-shrink: 0;
}
.sidebar__toggle {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--kordis-navy-900);
  display: flex;
  align-items: center;
  padding: 0.25rem;
  border-radius: 6px;
}
.sidebar__toggle:hover {
  background: var(--kordis-border);
}
.sidebar__logo {
  height: 22px;
  object-fit: contain;
}

.sidebar__nav {
  display: flex;
  flex-direction: column;
  padding: 0.75rem 0.5rem;
  gap: 0.25rem;
}
.sidebar__item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  border-radius: 8px;
  color: var(--kordis-text-secondary);
  text-decoration: none;
  white-space: nowrap;
}
.sidebar__item:hover {
  background: var(--kordis-border);
}
.sidebar__item.router-link-active {
  background: var(--kordis-navy-800);
  color: var(--kordis-text-on-navy);
}
.sidebar__label {
  font-size: 0.9rem;
}
</style>
