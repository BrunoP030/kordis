import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import surgicalBookingService from '../services/surgicalBookingService'
import { obterEcho } from '../services/echo'

const CANAL = 'centro-cirurgico'

const dois = (n) => String(n).padStart(2, '0')
const paraIso = (d) => `${d.getFullYear()}-${dois(d.getMonth() + 1)}-${dois(d.getDate())}`

// Converte erros de validação do Laravel em { campo: 'mensagem' }.
function extrairErros(e, padrao) {
  const erros = {}
  for (const [campo, msgs] of Object.entries(e.response?.data?.errors ?? {})) {
    erros[campo] = msgs[0]
  }
  if (!Object.keys(erros).length) erros.geral = e.response?.data?.message ?? padrao
  return erros
}

export function useSurgicalBoardPresenter() {
  const data = ref(paraIso(new Date()))
  const cirurgias = ref([])
  const salas = ref([])
  const carregando = ref(false)
  const erro = ref(null)

  // Canceladas saem da grade, mas continuam contadas no resumo do dia.
  const reservas = computed(() => cirurgias.value.filter((c) => c.Status !== 'cancelado'))

  const resumo = computed(() => {
    const conta = (status) => cirurgias.value.filter((c) => c.Status === status).length
    const ativas = reservas.value
    const urgentes = ativas.filter((c) => c.Tipo !== 'eletiva').length
    const minutos = ativas.reduce((t, c) => t + (new Date(c.EndTime) - new Date(c.StartTime)) / 60000, 0)
    // Ocupação sobre uma janela de 12h por sala ativa (07h–19h).
    const capacidade = salas.value.length * 12 * 60
    return {
      total: ativas.length,
      agendado: conta('agendado'),
      em_andamento: conta('em_andamento'),
      atrasado: conta('atrasado'),
      limpeza: conta('limpeza'),
      concluido: conta('concluido'),
      cancelado: conta('cancelado'),
      urgentes,
      ocupacao: capacidade ? Math.min(100, Math.round((minutos / capacidade) * 100)) : 0,
    }
  })

  async function carregar() {
    carregando.value = true
    erro.value = null
    try {
      const [listaSalas, lista] = await Promise.all([
        surgicalBookingService.listarSalas(),
        surgicalBookingService.listar(data.value),
      ])
      salas.value = listaSalas
      cirurgias.value = lista
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar o mapa cirúrgico.'
    } finally {
      carregando.value = false
    }
  }

  // Atualiza só a cirurgia afetada (insere, substitui ou remove do dia), sem recarregar a lista.
  function aplicarEvento(cirurgia) {
    const existe = cirurgias.value.some((c) => c.Id === cirurgia.Id)
    if (!cirurgia.StartTime.startsWith(data.value)) {
      if (existe) cirurgias.value = cirurgias.value.filter((c) => c.Id !== cirurgia.Id)
      return
    }
    cirurgias.value = existe
      ? cirurgias.value.map((c) => (c.Id === cirurgia.Id ? cirurgia : c))
      : [...cirurgias.value, cirurgia]
  }

  function mudarDia(dias) {
    const d = new Date(`${data.value}T00:00:00`)
    d.setDate(d.getDate() + dias)
    data.value = paraIso(d)
  }

  const irParaHoje = () => (data.value = paraIso(new Date()))
  const definirData = (d) => d && (data.value = paraIso(d))

  // Cada operação retorna { ok: true, cirurgia? } ou { ok: false, erros: { campo: 'mensagem' } }.
  async function executar(fn, mensagemPadrao) {
    try {
      const cirurgia = await fn()
      aplicarEvento(cirurgia)
      return { ok: true, cirurgia }
    } catch (e) {
      return { ok: false, erros: extrairErros(e, mensagemPadrao) }
    }
  }

  const criarCirurgia = (dados) =>
    executar(() => surgicalBookingService.criar(dados), 'Não foi possível agendar a cirurgia.')

  const atualizarCirurgia = (id, dados) =>
    executar(() => surgicalBookingService.atualizar(id, dados), 'Não foi possível atualizar a cirurgia.')

  const executarAcao = (id, acao, corpo) =>
    executar(() => surgicalBookingService.executarAcao(id, acao, corpo), 'Não foi possível executar a ação.')

  async function criarSala(dados) {
    try {
      const sala = await surgicalBookingService.criarSala(dados)
      await recarregarSalas()
      return { ok: true, sala }
    } catch (e) {
      return { ok: false, erros: extrairErros(e, 'Não foi possível cadastrar a sala.') }
    }
  }

  async function alternarSala(sala) {
    try {
      await surgicalBookingService.atualizarSala(sala.Id, { nome: sala.Nome, especialidade: sala.Especialidade, ativa: !sala.Ativa })
      await recarregarSalas()
      return { ok: true }
    } catch (e) {
      return { ok: false, erros: extrairErros(e, 'Não foi possível atualizar a sala.') }
    }
  }

  // Lista completa (com inativas) para o cadastro; a agenda usa só as ativas.
  const todasSalas = ref([])
  async function recarregarSalas() {
    const [ativas, todas] = await Promise.all([
      surgicalBookingService.listarSalas(),
      surgicalBookingService.listarSalas({ todas: true }),
    ])
    salas.value = ativas
    todasSalas.value = todas
  }

  watch(data, carregar)

  onMounted(async () => {
    await carregar()
    recarregarSalas().catch(() => {})
    obterEcho().private(CANAL).listen('.reserva.atualizada', (evento) => aplicarEvento(evento.reserva))
  })

  onUnmounted(() => {
    obterEcho().leave(CANAL)
  })

  return {
    data, cirurgias, reservas, salas, todasSalas, resumo, carregando, erro,
    mudarDia, irParaHoje, definirData,
    criarCirurgia, atualizarCirurgia, executarAcao, criarSala, alternarSala,
  }
}
