import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import surgicalBookingService from '../services/surgicalBookingService'
import { obterEcho } from '../services/echo'

const CANAL = 'centro-cirurgico'

const dois = (n) => String(n).padStart(2, '0')
const paraIso = (d) => `${d.getFullYear()}-${dois(d.getMonth() + 1)}-${dois(d.getDate())}`
const paraIsoHora = (d) => `${paraIso(d)}T${dois(d.getHours())}:${dois(d.getMinutes())}:00`

// Status que continuam ocupando a sala enquanto não terminam: o bloco cresce até "agora".
const STATUS_QUE_ESTENDEM = ['em_andamento', 'atrasado']
const minutoAtual = () => Math.floor(Date.now() / 60000) * 60000

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
  const brutas = ref([]) // como vieram do servidor
  const salas = ref([])
  const carregando = ref(false)
  const erro = ref(null)

  // Relógio com resolução de minuto: só dispara reatividade quando o minuto vira.
  const agora = ref(minutoAtual())
  let relogio = null

  // Status efetivo calculado no cliente também, para "agendado" virar "atrasado" sem recarregar.
  const cirurgias = computed(() =>
    brutas.value.map((c) =>
      c.Status === 'agendado' && new Date(c.StartTime).getTime() <= agora.value ? { ...c, Status: 'atrasado' } : c
    )
  )

  // Blocos da grade. Canceladas saem da grade, mas continuam contadas no resumo do dia.
  // Em andamento/atrasada que passou do fim previsto estica até agora; finalizada que
  // estourou o horário mantém o bloco até o fim real.
  const reservas = computed(() =>
    cirurgias.value
      .filter((c) => c.Status !== 'cancelado')
      .map((c) => {
        const previsto = new Date(c.EndTime).getTime()
        let fim = previsto
        if (STATUS_QUE_ESTENDEM.includes(c.Status)) fim = Math.max(previsto, agora.value)
        else if (c.FimReal) fim = Math.max(previsto, new Date(c.FimReal).getTime())
        const excedente = Math.round((fim - previsto) / 60000)
        // O bloco nunca passa das 23:59 do dia da cirurgia: se atravessar a meia-noite,
        // a agenda trata como evento de vários dias e o joga na faixa de "dia inteiro".
        const limiteDia = new Date(`${c.StartTime.slice(0, 10)}T23:59:00`).getTime()
        const fimBloco = Math.max(previsto, Math.min(fim, limiteDia))
        return {
          ...c,
          FimPrevisto: c.EndTime,
          EndTime: fimBloco > previsto ? paraIsoHora(new Date(fimBloco)) : c.EndTime,
          Excedente: excedente,
        }
      })
  )

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
      brutas.value = lista
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar o mapa cirúrgico.'
    } finally {
      carregando.value = false
    }
  }

  // Atualiza só a cirurgia afetada (insere, substitui ou remove do dia), sem recarregar a lista.
  function aplicarEvento(cirurgia) {
    const existe = brutas.value.some((c) => c.Id === cirurgia.Id)
    if (!cirurgia.StartTime.startsWith(data.value)) {
      if (existe) brutas.value = brutas.value.filter((c) => c.Id !== cirurgia.Id)
      return
    }
    brutas.value = existe
      ? brutas.value.map((c) => (c.Id === cirurgia.Id ? cirurgia : c))
      : [...brutas.value, cirurgia]
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
      // status: 0 = Não (inativa), 1 = Sim (ativa)
      await surgicalBookingService.atualizarSala(sala.Id, {
        nome: sala.Nome,
        especialidade_id: sala.EspecialidadeId,
        status: sala.Ativa ? 0 : 1,
      })
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
    relogio = setInterval(() => {
      const m = minutoAtual()
      if (m !== agora.value) agora.value = m
    }, 15000)
    await carregar()
    recarregarSalas().catch(() => {})
    obterEcho()?.private(CANAL).listen('.reserva.atualizada', (evento) => aplicarEvento(evento.reserva))
  })

  onUnmounted(() => {
    clearInterval(relogio)
    obterEcho()?.leave(CANAL)
  })

  return {
    data, cirurgias, reservas, salas, todasSalas, resumo, carregando, erro,
    mudarDia, irParaHoje, definirData,
    criarCirurgia, atualizarCirurgia, executarAcao, criarSala, alternarSala,
  }
}
