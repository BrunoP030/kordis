import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/auth/Login.vue'),
    meta: { publica: true },
  },
  {
    path: '/',
    name: 'dashboard',
    component: () => import('../views/dashboard/Dashboard.vue'),
    meta: { permissao: 'dashboard.visualizar' },
  },
  {
    path: '/centro-cirurgico',
    name: 'centro-cirurgico.painel',
    component: () => import('../views/centro-cirurgico/CentroCirurgicoPainel.vue'),
    meta: { permissao: 'centro-cirurgico.visualizar' },
  },
  {
    path: '/leitos',
    name: 'leitos.painel',
    component: () => import('../views/leitos/LeitosPainel.vue'),
    meta: { permissao: 'leitos.visualizar' },
  },
  {
    path: '/chat-interno',
    name: 'chat-interno.painel',
    component: () => import('../views/chat-interno/ChatInternoPainel.vue'),
    meta: { permissao: 'chat-interno.visualizar' },
  },
  {
    path: '/prontuarios',
    name: 'prontuarios.painel',
    component: () => import('../views/prontuarios/ProntuariosPainel.vue'),
    meta: { permissao: 'prontuarios.visualizar' },
  },
  {
    path: '/acesso-negado',
    name: 'acesso-negado',
    component: () => import('../views/AcessoNegado.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (!authStore.carregado) {
    await authStore.restaurarSessao()
  }

  if (!to.meta.publica && !authStore.estaAutenticado) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.permissao && !authStore.tem(to.meta.permissao)) {
    return { name: 'acesso-negado' }
  }

  return true
})

export default router
