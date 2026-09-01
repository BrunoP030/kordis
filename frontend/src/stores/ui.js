import { defineStore } from 'pinia'

const CHAVE_STORAGE = 'kordis:sidebar-collapsed'

export const useUiStore = defineStore('ui', {
  state: () => ({
    // lê a preferência salva no navegador, se existir
    sidebarCollapsed: localStorage.getItem(CHAVE_STORAGE) === '1',
  }),

  actions: {
    alternarSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
      localStorage.setItem(CHAVE_STORAGE, this.sidebarCollapsed ? '1' : '0')
    },
  },
})
