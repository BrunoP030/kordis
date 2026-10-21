import { ref, computed, onMounted, onUnmounted } from 'vue'
import nursingTaskService from '../services/nursingTaskService'
import { obterEcho } from '../services/echo'
import { useAuthStore } from '../stores/auth'

export const CATEGORIA_HIGIENIZACAO = 'Higienização'

export function useNursingTaskPresenter() {
  const auth = useAuthStore()
  const tarefas = ref([])
  const carregando = ref(false)
  const erro = ref(null)

  // A equipe de limpeza (só esse perfil) vê apenas os cards de higienização.
  // O backend já filtra; aqui é a segunda camada, que também cobre eventos em tempo real.
  const tarefasVisiveis = computed(() =>
    auth.apenasLimpeza ? tarefas.value.filter((t) => t.Categoria === CATEGORIA_HIGIENIZACAO) : tarefas.value
  )

  // Canal do broadcast: a limpeza escuta só o canal de higienização.
  const canal = auth.apenasLimpeza ? 'tarefas.higienizacao' : 'tarefas'

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

  // Insere ou substitui o card, sem recarregar a lista.
  function aplicarTarefa(tarefa) {
    tarefas.value = tarefas.value.some((t) => t.Id === tarefa.Id)
      ? tarefas.value.map((t) => (t.Id === tarefa.Id ? tarefa : t))
      : [...tarefas.value, tarefa]
  }

  // Retorna true se o servidor aceitou a mudança; false se a View deve reverter o card.
  async function moverTarefa(id, novoStatus) {
    erro.value = null
    try {
      aplicarTarefa(await nursingTaskService.atualizarStatus(id, novoStatus))
      return true
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível mover a atividade.'
      return false
    }
  }

  // Retorna { ok: true } ou { ok: false, erros: { campo: 'mensagem' } }.
  async function criarTarefa(dados) {
    try {
      aplicarTarefa(await nursingTaskService.criar(dados))
      return { ok: true }
    } catch (e) {
      const erros = {}
      for (const [campo, msgs] of Object.entries(e.response?.data?.errors ?? {})) {
        erros[campo] = msgs[0]
      }
      if (!Object.keys(erros).length) {
        erros.geral = e.response?.data?.message ?? 'Não foi possível criar a tarefa.'
      }
      return { ok: false, erros }
    }
  }

  onMounted(() => {
    carregar()
    obterEcho().private(canal).listen('.tarefa.atualizada', (evento) => aplicarTarefa(evento.tarefa))
  })

  onUnmounted(() => {
    obterEcho().leave(canal)
  })

  return { tarefas: tarefasVisiveis, carregando, erro, moverTarefa, criarTarefa }
}
