import api from './api'

export default {
  async listar() {
    const { data } = await api.get('/beds')
    return data
  },

  async atualizarStatus(id, status, dadosExtras = {}) {
    const { data } = await api.put(`/beds/${id}/status`, { status, ...dadosExtras })
    return data
  },
}
