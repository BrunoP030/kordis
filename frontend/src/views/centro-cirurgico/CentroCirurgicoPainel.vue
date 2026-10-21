<script setup>
import { ref, reactive, computed, nextTick, provide } from 'vue'
import {
  ScheduleComponent as EjsSchedule,
  ViewsDirective as EViews,
  ViewDirective as EView,
  ResourcesDirective as EResources,
  ResourceDirective as EResource,
  Day,
} from '@syncfusion/ej2-vue-schedule'
import { DialogComponent as EjsDialog } from '@syncfusion/ej2-vue-popups'
import { DropDownListComponent as EjsDropdownlist } from '@syncfusion/ej2-vue-dropdowns'
import { TextBoxComponent as EjsTextbox } from '@syncfusion/ej2-vue-inputs'
import { DatePickerComponent as EjsDatepicker, TimePickerComponent as EjsTimepicker } from '@syncfusion/ej2-vue-calendars'
import { ButtonComponent as EjsButton } from '@syncfusion/ej2-vue-buttons'
import { useAuthStore } from '../../stores/auth'
import { useSurgicalBoardPresenter } from '../../presenters/useSurgicalBoardPresenter'

provide('schedule', [Day])

const auth = useAuthStore()
const podeGerenciar = computed(() => auth.tem('centro-cirurgico.gerenciar'))
const {
  data, reservas, cirurgias, salas, todasSalas, resumo, carregando, erro,
  mudarDia, irParaHoje, definirData,
  criarCirurgia, atualizarCirurgia, executarAcao, criarSala, alternarSala,
} = useSurgicalBoardPresenter()

// ---------- Catálogos ----------
const LEGENDA = [
  { chave: 'agendado', texto: 'Agendado' },
  { chave: 'em_andamento', texto: 'Em andamento' },
  { chave: 'atrasado', texto: 'Atrasado' },
  { chave: 'limpeza', texto: 'Limpeza' },
  { chave: 'concluido', texto: 'Concluído' },
]
const ROTULO_STATUS = { ...Object.fromEntries(LEGENDA.map((l) => [l.chave, l.texto])), cancelado: 'Cancelado' }

const TIPOS = [
  { valor: 'eletiva', texto: 'Eletiva' },
  { valor: 'urgencia', texto: 'Urgência' },
  { valor: 'emergencia', texto: 'Emergência' },
]
const ROTULO_TIPO = Object.fromEntries(TIPOS.map((t) => [t.valor, t.texto]))

const PORTES = [
  { valor: 'pequeno', texto: 'Pequeno porte' },
  { valor: 'medio', texto: 'Médio porte' },
  { valor: 'grande', texto: 'Grande porte' },
  { valor: 'especial', texto: 'Porte especial' },
]
const ANESTESIAS = [
  { valor: 'geral', texto: 'Geral' },
  { valor: 'raquidiana', texto: 'Raquidiana' },
  { valor: 'peridural', texto: 'Peridural' },
  { valor: 'sedacao', texto: 'Sedação' },
  { valor: 'bloqueio', texto: 'Bloqueio regional' },
  { valor: 'local', texto: 'Local' },
]
const rotulo = (lista, valor) => lista.find((i) => i.valor === valor)?.texto ?? '—'
const camposLista = { text: 'texto', value: 'valor' }

// ---------- Agenda ----------
const agenda = ref(null)
const dataSelecionada = computed(() => new Date(`${data.value}T00:00:00`))
const dataFormatada = computed(() =>
  dataSelecionada.value.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
)
const ehHoje = computed(() => dataSelecionada.value.toDateString() === new Date().toDateString())

const eventSettings = computed(() => ({
  dataSource: reservas.value,
  template: 'eventTemplate',
  fields: {
    id: 'Id',
    subject: { name: 'Subject' },
    startTime: { name: 'StartTime' },
    endTime: { name: 'EndTime' },
  },
}))

const hora = (iso) => (iso ? iso.slice(11, 16) : '—')

function aoCriarAgenda() {
  nextTick(() => agenda.value?.scrollTo?.('07:00'))
}

function aoRenderizarEvento(args) {
  args.element.classList.add(`status-${args.data.Status}`, `tipo-${args.data.Tipo}`)
}

function aoAbrirPopup(args) {
  // A agenda é só visual: tudo é criado/alterado pelos botões e pelo painel de detalhes.
  if (args.type === 'Editor' || args.type === 'QuickInfo' || args.type === 'DeleteAlert') args.cancel = true
}

function aoClicarEvento(args) {
  args.cancel = true
  abrirDetalhes(args.event)
}

// Clique numa célula vazia abre o agendamento já na sala e no horário escolhidos.
function aoClicarCelula(args) {
  args.cancel = true
  if (!podeGerenciar.value) return
  const sala = salas.value[args.groupIndex ?? 0]
  abrirFormulario(null, { sala: sala?.Id ?? null, inicio: args.startTime })
}

// ---------- Utilitários de formulário ----------
const dois = (n) => String(n).padStart(2, '0')
const paraIso = (dia, h) =>
  `${dia.getFullYear()}-${dois(dia.getMonth() + 1)}-${dois(dia.getDate())} ${dois(h.getHours())}:${dois(h.getMinutes())}:00`
const limpar = (obj) => Object.keys(obj).forEach((k) => delete obj[k])

// ---------- Formulário de cirurgia (criar / editar) ----------
const dlgForm = ref(null)
const enviando = ref(false)
const erros = reactive({})
const form = reactive({})
const editandoId = ref(null)

function novoForm() {
  return {
    surgical_room_id: null,
    procedimento: '',
    cirurgiao: '',
    paciente: '',
    tipo: 'eletiva',
    porte: null,
    anestesia: null,
    anestesista: '',
    equipe_apoio: '',
    equipamentos: '',
    reserva_uti: false,
    reserva_sangue: false,
    observacoes: '',
    data: dataSelecionada.value,
    inicio: null,
    fim: null,
  }
}

function abrirFormulario(cirurgia = null, sugestao = {}) {
  limpar(erros)
  editandoId.value = cirurgia?.Id ?? null
  const base = novoForm()
  if (cirurgia) {
    const inicio = new Date(cirurgia.StartTime)
    Object.assign(base, {
      surgical_room_id: cirurgia.RoomId,
      procedimento: cirurgia.Subject,
      cirurgiao: cirurgia.Cirurgiao,
      paciente: cirurgia.Paciente ?? '',
      tipo: cirurgia.Tipo,
      porte: cirurgia.Porte,
      anestesia: cirurgia.Anestesia,
      anestesista: cirurgia.Anestesista ?? '',
      equipe_apoio: cirurgia.EquipeApoio ?? '',
      equipamentos: cirurgia.Equipamentos ?? '',
      reserva_uti: cirurgia.ReservaUti,
      reserva_sangue: cirurgia.ReservaSangue,
      observacoes: cirurgia.Observacoes ?? '',
      data: inicio,
      inicio,
      fim: new Date(cirurgia.EndTime),
    })
  } else if (sugestao.inicio) {
    base.surgical_room_id = sugestao.sala
    base.inicio = sugestao.inicio
    base.fim = new Date(sugestao.inicio.getTime() + 60 * 60 * 1000)
  } else {
    base.surgical_room_id = salas.value.length === 1 ? salas.value[0].Id : null
  }
  limpar(form)
  Object.assign(form, base)
  dlgDetalhes.value?.hide()
  dlgForm.value?.show()
}

// Espelha as regras do backend (SurgicalBookingRequest).
function validar() {
  limpar(erros)
  if (!form.surgical_room_id) erros.surgical_room_id = 'Selecione a sala.'
  if (!form.procedimento.trim()) erros.procedimento = 'Informe o procedimento.'
  if (!form.cirurgiao.trim()) erros.cirurgiao = 'Informe o cirurgião.'
  if (!form.paciente.trim()) erros.paciente = 'Informe as iniciais ou o nº do prontuário do paciente.'
  else if (form.paciente.length > 30) erros.paciente = 'Use apenas iniciais ou nº do prontuário (máx. 30 caracteres).'
  if (!form.data) erros.data = 'Informe a data.'
  if (!form.inicio) erros.inicio = 'Informe o horário de início.'
  if (!form.fim) erros.fim = 'Informe o horário de fim.'
  if (form.data && form.inicio && form.fim && paraIso(form.data, form.fim) <= paraIso(form.data, form.inicio)) {
    erros.fim = 'O fim deve ser depois do início.'
  }
  return Object.keys(erros).length === 0
}

async function salvar() {
  if (!validar()) return
  enviando.value = true
  const texto = (v) => v?.trim() || null
  const dados = {
    surgical_room_id: form.surgical_room_id,
    procedimento: form.procedimento.trim(),
    cirurgiao: form.cirurgiao.trim(),
    paciente: form.paciente.trim(),
    tipo: form.tipo,
    porte: form.porte,
    anestesia: form.anestesia,
    anestesista: texto(form.anestesista),
    equipe_apoio: texto(form.equipe_apoio),
    equipamentos: texto(form.equipamentos),
    reserva_uti: form.reserva_uti,
    reserva_sangue: form.reserva_sangue,
    observacoes: texto(form.observacoes),
    inicio_previsto: paraIso(form.data, form.inicio),
    fim_previsto: paraIso(form.data, form.fim),
  }
  const resultado = editandoId.value
    ? await atualizarCirurgia(editandoId.value, dados)
    : await criarCirurgia(dados)
  enviando.value = false

  if (resultado.ok) {
    dlgForm.value?.hide()
    // Se a cirurgia foi para outro dia, acompanha a data para o usuário vê-la.
    if (!resultado.cirurgia.StartTime.startsWith(data.value)) definirData(form.data)
    return
  }
  const mapa = { inicio_previsto: 'inicio', fim_previsto: 'fim' }
  for (const [campo, msg] of Object.entries(resultado.erros)) erros[mapa[campo] ?? campo] = msg
}

// ---------- Detalhes + ações de fluxo ----------
const dlgDetalhes = ref(null)
const detalheId = ref(null)
const acaoEmCurso = ref(false)
const erroAcao = ref(null)
const cancelando = ref(false)
const motivo = ref('')

// Sempre lê a versão mais recente (atualizada em tempo real pelo broadcast).
const detalhe = computed(() => cirurgias.value.find((c) => c.Id === detalheId.value) ?? null)
const nomeSala = (id) => todasSalas.value.concat(salas.value).find((s) => s.Id === id)?.Nome ?? '—'
const emAgendamento = computed(() => ['agendado', 'atrasado'].includes(detalhe.value?.Status))

function abrirDetalhes(cirurgia) {
  detalheId.value = cirurgia.Id
  erroAcao.value = null
  cancelando.value = false
  motivo.value = ''
  dlgDetalhes.value?.show()
}

async function rodarAcao(acao, corpo) {
  acaoEmCurso.value = true
  erroAcao.value = null
  const r = await executarAcao(detalheId.value, acao, corpo)
  acaoEmCurso.value = false
  if (!r.ok) {
    erroAcao.value = Object.values(r.erros)[0]
    return false
  }
  return true
}

async function confirmarCancelamento() {
  if (!motivo.value.trim()) {
    erroAcao.value = 'Informe o motivo do cancelamento.'
    return
  }
  if (await rodarAcao('cancelar', { motivo: motivo.value.trim() })) dlgDetalhes.value?.hide()
}

// ---------- Cadastro de salas ----------
const dlgSalas = ref(null)
const formSala = reactive({ nome: '', especialidade: '' })
const errosSala = reactive({})

function abrirSalas() {
  limpar(errosSala)
  Object.assign(formSala, { nome: '', especialidade: '' })
  dlgSalas.value?.show()
}

async function adicionarSala() {
  limpar(errosSala)
  if (!formSala.nome.trim()) {
    errosSala.nome = 'Informe o nome da sala.'
    return
  }
  const r = await criarSala({ nome: formSala.nome.trim(), especialidade: formSala.especialidade.trim() || null, ativa: true })
  if (r.ok) Object.assign(formSala, { nome: '', especialidade: '' })
  else Object.assign(errosSala, r.erros)
}

async function trocarSala(sala) {
  limpar(errosSala)
  const r = await alternarSala(sala)
  if (!r.ok) Object.assign(errosSala, r.erros)
}
</script>

<template>
  <section class="painel">
    <header class="cabecalho">
      <div>
        <h1 class="titulo">Centro Cirúrgico</h1>
        <p class="subtitulo">Mapa cirúrgico diário · atualização em tempo real</p>
      </div>

      <div class="acoes-topo">
        <ejs-button v-if="podeGerenciar" title="Cadastrar e ativar salas cirúrgicas" @click="abrirSalas">
          <span class="material-icons">meeting_room</span> Salas
        </ejs-button>
        <ejs-button
          v-if="podeGerenciar"
          is-primary
          :disabled="!salas.length"
          :title="salas.length ? 'Agendar uma nova cirurgia' : 'Cadastre uma sala antes de agendar'"
          @click="abrirFormulario()"
        >
          <span class="material-icons">add</span> Nova Cirurgia
        </ejs-button>
      </div>
    </header>

    <!-- Resumo do dia -->
    <div class="kpis">
      <article class="kpi"><span class="kpi-valor">{{ resumo.total }}</span><span class="kpi-rotulo">Cirurgias no dia</span></article>
      <article class="kpi kpi-andamento"><span class="kpi-valor">{{ resumo.em_andamento }}</span><span class="kpi-rotulo">Em andamento</span></article>
      <article class="kpi kpi-agendado"><span class="kpi-valor">{{ resumo.agendado }}</span><span class="kpi-rotulo">Agendadas</span></article>
      <article class="kpi kpi-atrasado"><span class="kpi-valor">{{ resumo.atrasado }}</span><span class="kpi-rotulo">Atrasadas</span></article>
      <article class="kpi"><span class="kpi-valor">{{ resumo.limpeza }}</span><span class="kpi-rotulo">Em limpeza</span></article>
      <article class="kpi kpi-urgente"><span class="kpi-valor">{{ resumo.urgentes }}</span><span class="kpi-rotulo">Urgência / emergência</span></article>
      <article class="kpi">
        <span class="kpi-valor">{{ resumo.ocupacao }}%</span>
        <span class="kpi-rotulo">Ocupação das salas</span>
        <span class="barra" aria-hidden="true"><span class="barra-preenchida" :style="{ width: `${resumo.ocupacao}%` }"></span></span>
      </article>
    </div>

    <!-- Navegação de data + legenda -->
    <div class="barra-agenda">
      <div class="navegacao">
        <ejs-button title="Dia anterior" aria-label="Dia anterior" @click="mudarDia(-1)"><span class="material-icons">chevron_left</span></ejs-button>
        <ejs-datepicker
          class="seletor-data"
          :value="dataSelecionada"
          format="dd/MM/yyyy"
          :showClearButton="false"
          @change="(e) => definirData(e.value)"
        />
        <ejs-button title="Próximo dia" aria-label="Próximo dia" @click="mudarDia(1)"><span class="material-icons">chevron_right</span></ejs-button>
        <ejs-button :disabled="ehHoje" @click="irParaHoje">Hoje</ejs-button>
        <span class="data-extenso">{{ dataFormatada }}</span>
      </div>

      <ul class="legenda" aria-label="Legenda de status">
        <li v-for="item in LEGENDA" :key="item.chave" class="legenda-item">
          <span class="bolinha" :class="`status-${item.chave}`" aria-hidden="true"></span>
          {{ item.texto }}
        </li>
      </ul>
    </div>

    <p v-if="erro" class="erro" role="alert">{{ erro }}</p>

    <div v-if="!carregando && !salas.length" class="vazio">
      <span class="material-icons vazio-icone">meeting_room</span>
      <h2>Nenhuma sala cirúrgica cadastrada</h2>
      <p v-if="podeGerenciar">Cadastre as salas do centro cirúrgico para começar a agendar cirurgias.</p>
      <p v-else>Peça a um responsável pelo centro cirúrgico para cadastrar as salas.</p>
      <ejs-button v-if="podeGerenciar" is-primary @click="abrirSalas">Cadastrar salas</ejs-button>
    </div>

    <template v-else>
      <p v-if="!carregando && !cirurgias.length" class="dica">
        Nenhuma cirurgia agendada para este dia.
        <template v-if="podeGerenciar">Use <strong>Nova Cirurgia</strong> ou clique em um horário livre da sala.</template>
      </p>

      <ejs-schedule
        ref="agenda"
        class="agenda"
        height="620px"
        currentView="Day"
        :selectedDate="dataSelecionada"
        :showHeaderBar="false"
        :readonly="true"
        startHour="00:00"
        endHour="24:00"
        :timeScale="{ enable: true, interval: 60, slotCount: 2 }"
        :group="{ resources: ['Salas'] }"
        :eventSettings="eventSettings"
        :created="aoCriarAgenda"
        :eventRendered="aoRenderizarEvento"
        :eventClick="aoClicarEvento"
        :cellClick="aoClicarCelula"
        :popupOpen="aoAbrirPopup"
      >
        <e-views><e-view option="Day" /></e-views>
        <e-resources>
          <e-resource field="RoomId" title="Sala" name="Salas" :dataSource="salas" textField="Nome" idField="Id" />
        </e-resources>

        <template v-slot:eventTemplate="{ data: c }">
          <div class="reserva" :title="`${c.Subject} — ${c.Cirurgiao} (${ROTULO_STATUS[c.Status]})`">
            <div class="reserva-topo">
              <span class="reserva-hora">{{ hora(c.StartTime) }}–{{ hora(c.EndTime) }}</span>
              <span v-if="c.Tipo !== 'eletiva'" class="selo" :class="`selo-${c.Tipo}`">{{ ROTULO_TIPO[c.Tipo] }}</span>
              <span v-if="c.ReservaUti" class="material-icons reserva-icone" title="Leito de UTI reservado">local_hospital</span>
              <span v-if="c.ReservaSangue" class="material-icons reserva-icone" title="Hemoderivados reservados">bloodtype</span>
            </div>
            <strong class="reserva-procedimento">{{ c.Subject }}</strong>
            <span class="reserva-cirurgiao">{{ c.Cirurgiao }} · {{ c.Paciente }}</span>
          </div>
        </template>
      </ejs-schedule>
    </template>

    <!-- ============ Modal: nova / editar cirurgia ============ -->
    <ejs-dialog
      ref="dlgForm"
      :header="editandoId ? 'Editar Cirurgia' : 'Nova Cirurgia'"
      width="720px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <form class="formulario" novalidate @submit.prevent="salvar">
        <p v-if="erros.geral || erros.status" class="erro" role="alert">{{ erros.geral || erros.status }}</p>

        <fieldset class="grupo">
          <legend>Caráter da cirurgia</legend>
          <div class="segmentos" role="radiogroup" aria-label="Caráter da cirurgia">
            <button
              v-for="t in TIPOS"
              :key="t.valor"
              type="button"
              role="radio"
              :aria-checked="form.tipo === t.valor"
              class="segmento"
              :class="[`segmento-${t.valor}`, { ativo: form.tipo === t.valor }]"
              @click="form.tipo = t.valor"
            >{{ t.texto }}</button>
          </div>
        </fieldset>

        <fieldset class="grupo">
          <legend>Procedimento</legend>
          <label class="campo">Procedimento *
            <ejs-textbox v-model="form.procedimento" placeholder="Ex.: Colecistectomia videolaparoscópica" />
            <span v-if="erros.procedimento" class="campo-erro">{{ erros.procedimento }}</span>
          </label>
          <div class="linha">
            <label class="campo">Paciente (iniciais ou nº do prontuário) *
              <ejs-textbox v-model="form.paciente" placeholder="Ex.: M.S." />
              <span class="campo-ajuda">LGPD: nunca informe o nome completo.</span>
              <span v-if="erros.paciente" class="campo-erro">{{ erros.paciente }}</span>
            </label>
            <label class="campo">Porte
              <ejs-dropdownlist v-model="form.porte" :dataSource="PORTES" :fields="camposLista" placeholder="Selecione" :showClearButton="true" />
            </label>
          </div>
        </fieldset>

        <fieldset class="grupo">
          <legend>Sala e horário</legend>
          <div class="linha">
            <label class="campo">Sala *
              <ejs-dropdownlist v-model="form.surgical_room_id" :dataSource="salas" :fields="{ text: 'Nome', value: 'Id' }" placeholder="Selecione a sala" />
              <span v-if="erros.surgical_room_id" class="campo-erro">{{ erros.surgical_room_id }}</span>
            </label>
            <label class="campo">Data *
              <ejs-datepicker v-model="form.data" format="dd/MM/yyyy" />
              <span v-if="erros.data" class="campo-erro">{{ erros.data }}</span>
            </label>
          </div>
          <div class="linha">
            <label class="campo">Início *
              <ejs-timepicker v-model="form.inicio" format="HH:mm" :step="15" />
              <span v-if="erros.inicio" class="campo-erro">{{ erros.inicio }}</span>
            </label>
            <label class="campo">Fim previsto *
              <ejs-timepicker v-model="form.fim" format="HH:mm" :step="15" />
              <span v-if="erros.fim" class="campo-erro">{{ erros.fim }}</span>
            </label>
          </div>
        </fieldset>

        <fieldset class="grupo">
          <legend>Equipe</legend>
          <div class="linha">
            <label class="campo">Cirurgião *
              <ejs-textbox v-model="form.cirurgiao" placeholder="Ex.: Dra. Helena" />
              <span v-if="erros.cirurgiao" class="campo-erro">{{ erros.cirurgiao }}</span>
            </label>
            <label class="campo">Anestesista
              <ejs-textbox v-model="form.anestesista" />
            </label>
          </div>
          <div class="linha">
            <label class="campo">Tipo de anestesia
              <ejs-dropdownlist v-model="form.anestesia" :dataSource="ANESTESIAS" :fields="camposLista" placeholder="Selecione" :showClearButton="true" />
            </label>
            <label class="campo">Equipe de apoio
              <ejs-textbox v-model="form.equipe_apoio" placeholder="Auxiliares, instrumentador, enfermagem" />
            </label>
          </div>
        </fieldset>

        <fieldset class="grupo">
          <legend>Preparo e recursos</legend>
          <label class="campo">Materiais e equipamentos especiais (OPME)
            <ejs-textbox v-model="form.equipamentos" :multiline="true" placeholder="Ex.: Torre de vídeo, intensificador de imagem, prótese..." />
          </label>
          <div class="linha opcoes">
            <label class="opcao"><input v-model="form.reserva_uti" type="checkbox" /> Reservar leito de UTI no pós-operatório</label>
            <label class="opcao"><input v-model="form.reserva_sangue" type="checkbox" /> Reservar hemoderivados</label>
          </div>
          <label class="campo">Observações
            <ejs-textbox v-model="form.observacoes" :multiline="true" />
          </label>
        </fieldset>

        <div class="acoes">
          <ejs-button type="button" @click="dlgForm?.hide()">Cancelar</ejs-button>
          <ejs-button type="submit" is-primary :disabled="enviando">
            {{ enviando ? 'Salvando...' : editandoId ? 'Salvar alterações' : 'Agendar cirurgia' }}
          </ejs-button>
        </div>
      </form>
    </ejs-dialog>

    <!-- ============ Modal: detalhes da cirurgia ============ -->
    <ejs-dialog
      ref="dlgDetalhes"
      header="Detalhes da Cirurgia"
      width="560px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <div v-if="detalhe" class="detalhe">
        <div class="detalhe-topo">
          <h2 class="detalhe-titulo">{{ detalhe.Subject }}</h2>
          <span class="status-selo" :class="`status-${detalhe.Status}`">{{ ROTULO_STATUS[detalhe.Status] }}</span>
        </div>

        <div class="selos">
          <span class="selo" :class="`selo-${detalhe.Tipo}`">{{ ROTULO_TIPO[detalhe.Tipo] }}</span>
          <span v-if="detalhe.Porte" class="selo selo-neutro">{{ rotulo(PORTES, detalhe.Porte) }}</span>
          <span v-if="detalhe.ReservaUti" class="selo selo-neutro">UTI reservada</span>
          <span v-if="detalhe.ReservaSangue" class="selo selo-neutro">Hemoderivados reservados</span>
        </div>

        <dl class="dados">
          <div><dt>Sala</dt><dd>{{ nomeSala(detalhe.RoomId) }}</dd></div>
          <div><dt>Horário previsto</dt><dd>{{ hora(detalhe.StartTime) }} às {{ hora(detalhe.EndTime) }}</dd></div>
          <div><dt>Paciente</dt><dd>{{ detalhe.Paciente }}</dd></div>
          <div><dt>Cirurgião</dt><dd>{{ detalhe.Cirurgiao }}</dd></div>
          <div><dt>Anestesista</dt><dd>{{ detalhe.Anestesista || '—' }}</dd></div>
          <div><dt>Anestesia</dt><dd>{{ rotulo(ANESTESIAS, detalhe.Anestesia) }}</dd></div>
          <div v-if="detalhe.InicioReal"><dt>Início real</dt><dd>{{ hora(detalhe.InicioReal) }}</dd></div>
          <div v-if="detalhe.FimReal"><dt>Fim real</dt><dd>{{ hora(detalhe.FimReal) }}</dd></div>
          <div v-if="detalhe.EquipeApoio" class="largo"><dt>Equipe de apoio</dt><dd>{{ detalhe.EquipeApoio }}</dd></div>
          <div v-if="detalhe.Equipamentos" class="largo"><dt>Materiais e equipamentos</dt><dd>{{ detalhe.Equipamentos }}</dd></div>
          <div v-if="detalhe.Observacoes" class="largo"><dt>Observações</dt><dd>{{ detalhe.Observacoes }}</dd></div>
          <div v-if="detalhe.MotivoCancelamento" class="largo"><dt>Motivo do cancelamento</dt><dd>{{ detalhe.MotivoCancelamento }}</dd></div>
        </dl>

        <p v-if="erroAcao" class="erro" role="alert">{{ erroAcao }}</p>

        <div v-if="podeGerenciar && cancelando" class="cancelamento">
          <label class="campo">Motivo do cancelamento *
            <ejs-textbox v-model="motivo" :multiline="true" placeholder="Ex.: Paciente sem jejum, exames pendentes..." />
          </label>
          <div class="acoes">
            <ejs-button :disabled="acaoEmCurso" @click="cancelando = false">Voltar</ejs-button>
            <ejs-button class="e-danger" :disabled="acaoEmCurso" @click="confirmarCancelamento">Confirmar cancelamento</ejs-button>
          </div>
        </div>

        <div v-else-if="podeGerenciar" class="acoes acoes-detalhe">
          <template v-if="emAgendamento">
            <ejs-button class="e-danger e-outline" :disabled="acaoEmCurso" @click="cancelando = true">Cancelar cirurgia</ejs-button>
            <ejs-button :disabled="acaoEmCurso" @click="abrirFormulario(detalhe)">Editar</ejs-button>
            <ejs-button is-primary :disabled="acaoEmCurso" @click="rodarAcao('iniciar')">Iniciar cirurgia</ejs-button>
          </template>
          <ejs-button v-else-if="detalhe.Status === 'em_andamento'" is-primary :disabled="acaoEmCurso" @click="rodarAcao('finalizar')">
            Finalizar cirurgia
          </ejs-button>
          <ejs-button v-else-if="detalhe.Status === 'limpeza'" is-primary :disabled="acaoEmCurso" @click="rodarAcao('liberar-sala')">
            Liberar sala (limpeza concluída)
          </ejs-button>
        </div>
      </div>
    </ejs-dialog>

    <!-- ============ Modal: salas ============ -->
    <ejs-dialog
      ref="dlgSalas"
      header="Salas Cirúrgicas"
      width="480px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <form class="formulario" novalidate @submit.prevent="adicionarSala">
        <p v-if="errosSala.geral" class="erro" role="alert">{{ errosSala.geral }}</p>
        <div class="linha">
          <label class="campo">Nome da sala *
            <ejs-textbox v-model="formSala.nome" placeholder="Ex.: Sala 05" />
            <span v-if="errosSala.nome" class="campo-erro">{{ errosSala.nome }}</span>
          </label>
          <label class="campo">Especialidade
            <ejs-textbox v-model="formSala.especialidade" placeholder="Ex.: Ortopedia" />
          </label>
        </div>
        <div class="acoes"><ejs-button type="submit" is-primary>Adicionar sala</ejs-button></div>
      </form>

      <ul class="lista-salas">
        <li v-for="s in todasSalas" :key="s.Id" class="sala" :class="{ inativa: !s.Ativa }">
          <div>
            <strong>{{ s.Nome }}</strong>
            <span v-if="s.Especialidade" class="sala-esp">{{ s.Especialidade }}</span>
          </div>
          <ejs-button @click="trocarSala(s)">{{ s.Ativa ? 'Desativar' : 'Ativar' }}</ejs-button>
        </li>
        <li v-if="!todasSalas.length" class="sala-vazia">Nenhuma sala cadastrada ainda.</li>
      </ul>
    </ejs-dialog>
  </section>
</template>

<style scoped>
.cabecalho {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}
.titulo { margin: 0; font-size: 22px; color: var(--kordis-navy-900); }
.subtitulo { margin: 2px 0 0; font-size: 13px; color: var(--kordis-text-secondary); }
.acoes-topo { display: flex; gap: 8px; }
.acoes-topo .material-icons { margin-right: 4px; }

/* KPIs */
.kpis {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}
.kpi {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 12px 14px;
  background: var(--kordis-bg-page);
  border: 1px solid var(--kordis-border);
  border-top: 3px solid var(--kordis-border);
  border-radius: 8px;
}
.kpi-valor { font-size: 24px; font-weight: 700; color: var(--kordis-navy-900); line-height: 1.1; }
.kpi-rotulo { font-size: 12px; color: var(--kordis-text-secondary); }
.kpi-andamento { border-top-color: var(--kordis-status-andamento-dot); }
.kpi-agendado { border-top-color: var(--kordis-status-agendado-dot); }
.kpi-atrasado, .kpi-urgente { border-top-color: var(--kordis-task-urgente); }
.barra { height: 6px; margin-top: 6px; background: var(--kordis-bg-sidebar); border-radius: 3px; overflow: hidden; }
.barra-preenchida { display: block; height: 100%; background: var(--kordis-teal-500); }

/* Navegação + legenda */
.barra-agenda {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}
.navegacao { display: flex; align-items: center; gap: 8px; }
.seletor-data { width: 150px; }
.data-extenso { margin-left: 8px; font-size: 14px; font-weight: 600; text-transform: capitalize; color: var(--kordis-text-primary); }

.legenda { display: flex; gap: 14px; margin: 0; padding: 0; list-style: none; font-size: 13px; color: var(--kordis-text-secondary); }
.legenda-item { display: flex; align-items: center; gap: 6px; }
.bolinha { width: 10px; height: 10px; border-radius: 50%; }
.bolinha.status-agendado { background: var(--kordis-status-agendado-dot); }
.bolinha.status-em_andamento { background: var(--kordis-status-andamento-dot); }
.bolinha.status-atrasado { background: var(--kordis-task-urgente); }
.bolinha.status-limpeza { background: var(--kordis-task-progresso); }
.bolinha.status-concluido { background: var(--kordis-text-secondary); }

.erro { color: var(--kordis-danger-text); }
.dica { margin: 0 0 8px; font-size: 13px; color: var(--kordis-text-secondary); }

.vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 56px 16px;
  text-align: center;
  color: var(--kordis-text-secondary);
  border: 1px dashed var(--kordis-border);
  border-radius: 8px;
}
.vazio h2 { margin: 0; font-size: 18px; color: var(--kordis-navy-900); }
.vazio p { margin: 0 0 8px; }
.vazio-icone { font-size: 44px; color: var(--kordis-teal-500); }

/* Blocos na agenda */
.reserva { display: flex; flex-direction: column; padding: 4px 6px; color: var(--kordis-text-primary); cursor: pointer; }
.reserva-topo { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--kordis-text-secondary); }
.reserva-icone { font-size: 14px; }
.reserva-procedimento { font-weight: 700; }
.reserva-cirurgiao { font-size: 12px; color: var(--kordis-text-secondary); }

.agenda :deep(.e-appointment) { border: 0; border-left: 4px solid transparent; color: var(--kordis-text-primary); }
.agenda :deep(.e-appointment.status-agendado) { background: var(--kordis-status-agendado-bg); border-left-color: var(--kordis-status-agendado-dot); }
.agenda :deep(.e-appointment.status-em_andamento) { background: var(--kordis-status-andamento-bg); border-left-color: var(--kordis-status-andamento-dot); }
.agenda :deep(.e-appointment.status-atrasado) {
  background: color-mix(in srgb, var(--kordis-task-urgente) 15%, var(--kordis-bg-page));
  border-left-color: var(--kordis-task-urgente);
}
.agenda :deep(.e-appointment.status-limpeza) {
  background: color-mix(in srgb, var(--kordis-task-progresso) 18%, var(--kordis-bg-page));
  border-left-color: var(--kordis-task-progresso);
}
.agenda :deep(.e-appointment.status-concluido) { background: var(--kordis-bg-sidebar); border-left-color: var(--kordis-text-secondary); }
.agenda :deep(.e-appointment.tipo-emergencia) { box-shadow: inset 0 0 0 1px var(--kordis-task-urgente); }

/* Selos */
.selo {
  display: inline-block;
  padding: 1px 8px;
  font-size: 11px;
  font-weight: 600;
  border-radius: 999px;
  color: var(--kordis-text-on-navy);
}
.selo-urgencia { background: var(--kordis-task-progresso); color: var(--kordis-text-primary); }
.selo-emergencia { background: var(--kordis-task-urgente); }
.selo-eletiva { background: var(--kordis-navy-800); }
.selo-neutro { background: var(--kordis-bg-sidebar); color: var(--kordis-text-secondary); border: 1px solid var(--kordis-border); }

/* Formulário */
.formulario { display: flex; flex-direction: column; gap: 14px; }
.grupo { display: flex; flex-direction: column; gap: 10px; margin: 0; padding: 10px 14px 14px; border: 1px solid var(--kordis-border); border-radius: 8px; }
.grupo legend { padding: 0 6px; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--kordis-navy-800); }
.campo { display: flex; flex-direction: column; gap: 4px; flex: 1; font-size: 13px; color: var(--kordis-text-secondary); }
.campo-erro { color: var(--kordis-danger-text); font-size: 12px; }
.campo-ajuda { font-size: 11px; color: var(--kordis-text-secondary); }
.linha { display: flex; gap: 12px; }
.opcoes { flex-wrap: wrap; gap: 8px 24px; }
.opcao { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--kordis-text-primary); cursor: pointer; }
.acoes { display: flex; justify-content: flex-end; gap: 8px; }

.segmentos { display: flex; gap: 8px; }
.segmento {
  flex: 1;
  padding: 8px 12px;
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  color: var(--kordis-text-secondary);
  background: var(--kordis-bg-page);
  border: 1px solid var(--kordis-border);
  border-radius: 6px;
  cursor: pointer;
}
.segmento.ativo { color: var(--kordis-text-on-navy); }
.segmento-eletiva.ativo { background: var(--kordis-navy-800); border-color: var(--kordis-navy-800); }
.segmento-urgencia.ativo { background: var(--kordis-task-progresso); border-color: var(--kordis-task-progresso); color: var(--kordis-text-primary); }
.segmento-emergencia.ativo { background: var(--kordis-task-urgente); border-color: var(--kordis-task-urgente); }

/* Detalhes */
.detalhe { display: flex; flex-direction: column; gap: 14px; }
.detalhe-topo { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.detalhe-titulo { margin: 0; font-size: 18px; color: var(--kordis-navy-900); }
.selos { display: flex; flex-wrap: wrap; gap: 6px; }
.status-selo { flex-shrink: 0; padding: 2px 10px; font-size: 12px; font-weight: 700; border-radius: 999px; color: var(--kordis-text-primary); background: var(--kordis-bg-sidebar); }
.status-selo.status-agendado { background: var(--kordis-status-agendado-bg); }
.status-selo.status-em_andamento { background: var(--kordis-status-andamento-bg); }
.status-selo.status-atrasado { background: color-mix(in srgb, var(--kordis-task-urgente) 18%, var(--kordis-bg-page)); }
.status-selo.status-limpeza { background: color-mix(in srgb, var(--kordis-task-progresso) 22%, var(--kordis-bg-page)); }

.dados { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; margin: 0; }
.dados div { display: flex; flex-direction: column; gap: 2px; }
.dados .largo { grid-column: 1 / -1; }
.dados dt { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--kordis-text-secondary); }
.dados dd { margin: 0; font-size: 14px; color: var(--kordis-text-primary); white-space: pre-wrap; }
.acoes-detalhe { padding-top: 4px; border-top: 1px solid var(--kordis-border); padding-top: 12px; }
.cancelamento { display: flex; flex-direction: column; gap: 10px; }

/* Salas */
.lista-salas { margin: 16px 0 0; padding: 0; list-style: none; border-top: 1px solid var(--kordis-border); }
.sala { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--kordis-border); }
.sala.inativa { opacity: 0.55; }
.sala-esp { margin-left: 8px; font-size: 12px; color: var(--kordis-text-secondary); }
.sala-vazia { padding: 12px 0; font-size: 13px; color: var(--kordis-text-secondary); }
</style>
