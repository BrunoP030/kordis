import { ref, onMounted, onUnmounted } from 'vue'
import surgicalBookingService from '../services/surgicalBookingService'
import { obterEcho } from '../services/echo'

const CANAL = 'centro-cirurgico'

function hojeIso() {
  const d = new Date()
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const dia = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${mes}-${dia}`
}

export function useSurgicalBoardPresenter() {
  const data = ref(hojeIso())
  const reservas = ref([])
  const salas = ref([])
  const carregando = ref(false)
  const erro = ref(null)

  async function carregar() {
    carregando.value = true
    erro.value = null
    try {
      const [listaSalas, listaReservas] = await Promise.all([
        surgicalBookingService.listarSalas(),
        surgicalBookingService.listar(data.value),
      ])
      salas.value = listaSalas
      reservas.value = listaReservas
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar o mapa cirúrgico.'
    } finally {
      carregando.value = false
    }
  }

  // Atualiza só a reserva afetada (insere ou substitui), sem recarregar a lista.
  function aplicarEvento(reserva) {
    if (!reserva.StartTime.startsWith(data.value)) return
    const existe = reservas.value.some((r) => r.Id === reserva.Id)
    reservas.value = existe
      ? reservas.value.map((r) => (r.Id === reserva.Id ? reserva : r))
      : [...reservas.value, reserva]
  }

  // Retorna { ok: true } ou { ok: false, erros: { campo: 'mensagem' } }.
  async function criarReserva(dados) {
    try {
      aplicarEvento(await surgicalBookingService.criar(dados))
      return { ok: true }
    } catch (e) {
      const erros = {}
      for (const [campo, msgs] of Object.entries(e.response?.data?.errors ?? {})) {
        erros[campo] = msgs[0]
      }
      if (!Object.keys(erros).length) {
        erros.geral = e.response?.data?.message ?? 'Não foi possível criar a reserva.'
      }
      return { ok: false, erros }
    }
  }

  onMounted(() => {
    carregar()
    obterEcho().private(CANAL).listen('.reserva.atualizada', (evento) => aplicarEvento(evento.reserva))
  })

  onUnmounted(() => {
    obterEcho().leave(CANAL)
  })

  return { data, reservas, salas, carregando, erro, criarReserva }
}
