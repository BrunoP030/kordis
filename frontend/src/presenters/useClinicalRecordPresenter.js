import { ref, onMounted } from 'vue'
import clinicalRecordService from '../services/clinicalRecordService'

export function useClinicalRecordPresenter() {
  const pacientes = ref([])
  const paciente = ref(null)
  const registros = ref([])
  const carregandoPacientes = ref(false)
  const carregandoHistorico = ref(false)
  const erro = ref(null)

  async function buscarPacientes(busca = '') {
    carregandoPacientes.value = true
    erro.value = null
    try {
      pacientes.value = await clinicalRecordService.listarPacientes(busca)
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar os pacientes.'
    } finally {
      carregandoPacientes.value = false
    }
  }

  async function selecionarPaciente(id) {
    carregandoHistorico.value = true
    erro.value = null
    try {
      const dados = await clinicalRecordService.historico(id)
      paciente.value = dados.Paciente
      registros.value = dados.Registros
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar o prontuário.'
    } finally {
      carregandoHistorico.value = false
    }
  }

  // Retorna { ok: true } ou { ok: false, erros: { campo: 'mensagem' } }.
  async function criarRegistro(dados) {
    try {
      const novo = await clinicalRecordService.criar(paciente.value.Id, dados)
      registros.value = [novo, ...registros.value]
      return { ok: true }
    } catch (e) {
      const erros = {}
      for (const [campo, msgs] of Object.entries(e.response?.data?.errors ?? {})) {
        erros[campo] = msgs[0]
      }
      if (!Object.keys(erros).length) {
        erros.geral = e.response?.data?.message ?? 'Não foi possível salvar o registro.'
      }
      return { ok: false, erros }
    }
  }

  onMounted(() => buscarPacientes())

  return {
    pacientes, paciente, registros, carregandoPacientes, carregandoHistorico, erro,
    buscarPacientes, selecionarPaciente, criarRegistro,
  }
}
