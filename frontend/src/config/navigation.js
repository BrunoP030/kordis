/**
 * ============================================================================
 *  MENU LATERAL DO KORDIS — fonte única de verdade
 * ============================================================================
 * A Sidebar (components/layout/AppSidebar.vue) só faz um `v-for` nesta lista.
 * Pra adicionar um item novo de menu, este é o ÚNICO arquivo que você edita
 * pra fazer ele aparecer — o resto (rota, view, permissão) é o de sempre no
 * Vue Router, documentado no README.md na seção "Como adicionar um menu novo".
 *
 * Cada entrada:
 * - label:      texto exibido
 * - icon:       nome do ícone da fonte Material Icons do Google
 *               (catálogo completo: https://fonts.google.com/icons?icon.set=Material+Icons)
 * - routeName:  precisa bater com o `name` de uma rota em router/index.js
 * - permission: precisa bater com uma permissão do backend (Spatie) —
 *               ver database/seeders/RolesAndPermissionsSeeder.php no backend.
 *               Se o usuário logado não tiver essa permissão, o item some
 *               sozinho (checado em AppSidebar.vue via authStore.tem()).
 */
export default [
  {
    label: 'Dashboard',
    icon: 'dashboard',
    routeName: 'dashboard',
    permission: 'dashboard.visualizar',
  },
  {
    label: 'Centro Cirúrgico',
    icon: 'medical_services',
    routeName: 'centro-cirurgico.painel',
    permission: 'centro-cirurgico.visualizar',
  },
  {
    label: 'Leitos',
    icon: 'king_bed',
    routeName: 'leitos.painel',
    permission: 'leitos.visualizar',
  },
  {
    label: 'Prontuários',
    icon: 'folder_shared',
    routeName: 'prontuarios.painel',
    permission: 'prontuarios.visualizar',
  },
]
