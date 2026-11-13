import api from './api'

// Cadastros de apoio mudam pouco: uma busca por sessão de página basta.
let cadastros = null

export default {
  // { Prioridades, Categorias, Especialidades, TiposProntuario, Leitos } — cada item { Id, Nome }
  async cadastros({ recarregar = false } = {}) {
    if (!cadastros || recarregar) {
      cadastros = api.get('/opcoes').then(({ data }) => data)
      cadastros.catch(() => { cadastros = null })
    }
    return cadastros
  },

  // [{ Id, Nome }] — exige leitos.gerenciar ou tarefas.criar
  async pacientes(busca = '') {
    const { data } = await api.get('/opcoes/pacientes', { params: busca ? { busca } : {} })
    return data
  },
}
