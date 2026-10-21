import api from './api'

export default {
  async listarPacientes(busca = '') {
    const { data } = await api.get('/clinical-records/patients', { params: busca ? { busca } : {} })
    return data
  },

  // Retorna { Paciente, Registros } com o histórico do mais recente ao mais antigo.
  async historico(pacienteId) {
    const { data } = await api.get(`/patients/${pacienteId}/clinical-records`)
    return data
  },

  async criar(pacienteId, dados) {
    const { data } = await api.post(`/patients/${pacienteId}/clinical-records`, dados)
    return data
  },
}
