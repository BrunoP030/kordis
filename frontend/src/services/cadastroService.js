import api from './api'

// CRUD genérico da tela "Cadastro" (backend: App\Cadastros\RegistroCadastros).
export default {
  // [{ chave, titulo, singular, protegidos, campos: [{ nome, rotulo, tipo, obrigatorio, max, grid, recurso, opcoes }] }]
  async definicoes() {
    const { data } = await api.get('/cadastros')
    return data
  },

  async listar(recurso) {
    const { data } = await api.get(`/cadastros/${recurso}`)
    return data
  },

  // Só os ativos; "incluir" acrescenta o valor atual mesmo que esteja inativo (para edição).
  async opcoes(recurso, incluir = null) {
    const { data } = await api.get(`/cadastros/${recurso}/opcoes`, { params: incluir ? { incluir } : {} })
    return data
  },

  async criar(recurso, dados) {
    const { data } = await api.post(`/cadastros/${recurso}`, dados)
    return data
  },

  async atualizar(recurso, id, dados) {
    const { data } = await api.put(`/cadastros/${recurso}/${id}`, dados)
    return data
  },

  async excluir(recurso, id) {
    await api.delete(`/cadastros/${recurso}/${id}`)
  },
}
