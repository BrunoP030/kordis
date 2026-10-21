import { defineStore } from 'pinia'
import api, { obterCsrfCookie } from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    usuario: null,
    perfis: [],
    permissoes: [],
    carregado: false,
  }),

  getters: {
    estaAutenticado: (state) => !!state.usuario,
    tem: (state) => (permissao) => state.permissoes.includes(permissao),
    temPerfil: (state) => (perfil) => state.perfis.includes(perfil),
    // Compara sem diferenciar maiúsculas e aceita vários perfis: isRole('medico', 'enfermeiro').
    isRole: (state) => (...perfis) =>
      perfis.some((p) => state.perfis.some((atual) => atual.toLowerCase() === p.toLowerCase())),
    // Só o perfil de limpeza e nenhum outro (espelha User::apenasLimpeza() do backend).
    apenasLimpeza() {
      return this.perfis.length === 1 && this.isRole('limpeza')
    },
    iniciais: (state) => {
      if (!state.usuario?.name) return ''
      return state.usuario.name
        .split(' ')
        .slice(0, 2)
        .map((p) => p[0])
        .join('')
        .toUpperCase()
    },
  },

  actions: {
    definirSessao(dados) {
      this.usuario = dados.usuario
      this.perfis = dados.perfis ?? []
      this.permissoes = dados.permissoes ?? []
    },

    async login(email, password) {
      await obterCsrfCookie()
      const { data } = await api.post('/login', { email, password })
      this.definirSessao(data)
    },

    async logout() {
      await api.post('/logout')
      this.usuario = null
      this.perfis = []
      this.permissoes = []
    },

    async restaurarSessao() {
      try {
        const { data } = await api.get('/me')
        this.definirSessao(data)
      } catch {
        this.usuario = null
      } finally {
        this.carregado = true
      }
    },
  },
})
