import api from './api'

export default {
  async listar(data) {
    const { data: cirurgias } = await api.get('/surgical-bookings', { params: { data } })
    return cirurgias
  },

  async criar(dados) {
    const { data } = await api.post('/surgical-bookings', dados)
    return data
  },

  async atualizar(id, dados) {
    const { data } = await api.put(`/surgical-bookings/${id}`, dados)
    return data
  },

  // acao: 'iniciar' | 'finalizar' | 'liberar-sala' | 'cancelar'
  async executarAcao(id, acao, corpo = {}) {
    const { data } = await api.post(`/surgical-bookings/${id}/${acao}`, corpo)
    return data
  },

  async listarSalas({ todas = false } = {}) {
    const { data } = await api.get('/surgical-rooms', { params: todas ? { todas: 1 } : {} })
    return data
  },

  async criarSala(dados) {
    const { data } = await api.post('/surgical-rooms', dados)
    return data
  },

  async atualizarSala(id, dados) {
    const { data } = await api.put(`/surgical-rooms/${id}`, dados)
    return data
  },
}
