import api from './api'

export default {
  async listar() {
    const { data } = await api.get('/nursing-tasks')
    return data
  },

  async atualizarStatus(id, status) {
    const { data } = await api.put(`/nursing-tasks/${id}/status`, { status })
    return data
  },
}
