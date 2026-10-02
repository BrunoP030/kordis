import api from './api'

export default {
  async listar(data) {
    const { data: reservas } = await api.get('/surgical-bookings', { params: { data } })
    return reservas
  },

  async criar(dados) {
    const { data } = await api.post('/surgical-bookings', dados)
    return data
  },

  async listarSalas() {
    const { data } = await api.get('/surgical-rooms')
    return data
  },
}
