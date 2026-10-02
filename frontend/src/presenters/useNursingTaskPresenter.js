import { ref, onMounted } from 'vue'
import nursingTaskService from '../services/nursingTaskService'

export function useNursingTaskPresenter() {
  const tarefas = ref([])
  const carregando = ref(false)
  const erro = ref(null)

  async function carregar() {
    carregando.value = true
    erro.value = null
    try {
      tarefas.value = await nursingTaskService.listar()
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar as atividades.'
    } finally {
      carregando.value = false
    }
  }

  // Retorna true se o servidor aceitou a mudança; false se a View deve reverter o card.
  async function moverTarefa(id, novoStatus) {
    erro.value = null
    try {
      const atualizada = await nursingTaskService.atualizarStatus(id, novoStatus)
      tarefas.value = tarefas.value.map((t) => (t.Id === id ? atualizada : t))
      return true
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível mover a atividade.'
      return false
    }
  }

  onMounted(carregar)

  return { tarefas, carregando, erro, moverTarefa }
}
