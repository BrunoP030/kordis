import { ref, onMounted } from 'vue'
import bedService from '../services/bedService'

export function useBedPresenter() {
  const leitos = ref([])
  const carregando = ref(false)
  const erro = ref(null)

  async function carregar() {
    carregando.value = true
    erro.value = null
    try {
      leitos.value = await bedService.listar()
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar os leitos.'
    } finally {
      carregando.value = false
    }
  }

  // Retorna { ok: true } ou { ok: false, erros: { campo: 'mensagem' } }.
  async function mudarStatus(id, status, dadosExtras = {}) {
    try {
      const atualizado = await bedService.atualizarStatus(id, status, dadosExtras)
      leitos.value = leitos.value.map((l) => (l.Id === id ? atualizado : l))
      return { ok: true }
    } catch (e) {
      const erros = {}
      for (const [campo, msgs] of Object.entries(e.response?.data?.errors ?? {})) {
        erros[campo] = msgs[0]
      }
      if (!Object.keys(erros).length) {
        erros.geral = e.response?.data?.message ?? 'Não foi possível alterar o status.'
      }
      return { ok: false, erros }
    }
  }

  onMounted(carregar)

  return { leitos, carregando, erro, mudarStatus }
}
