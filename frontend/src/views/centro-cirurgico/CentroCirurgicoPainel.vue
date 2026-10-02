<script setup>
import { ref, reactive, provide } from 'vue'
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
const { data, reservas, salas, erro, criarReserva } = useSurgicalBoardPresenter()

const LEGENDA = [
  { chave: 'agendado', texto: 'Agendado' },
  { chave: 'em_andamento', texto: 'Em Andamento' },
  { chave: 'atrasado', texto: 'Atrasado' },
  { chave: 'limpeza', texto: 'Limpeza' },
]
const ROTULO_STATUS = Object.fromEntries(LEGENDA.map((l) => [l.chave, l.texto]))

const eventSettings = (dataSource) => ({
  dataSource,
  template: 'eventTemplate',
  fields: {
    id: 'Id',
    subject: { name: 'Subject' },
    startTime: { name: 'StartTime' },
    endTime: { name: 'EndTime' },
  },
})

const dataSelecionada = () => new Date(`${data.value}T00:00:00`)

function aoRenderizarEvento(args) {
  args.element.classList.add(`status-${args.data.Status}`)
}

function aoAbrirPopup(args) {
  // Agenda somente leitura: reservas são criadas só pelo botão "Nova Reserva".
  if (args.type === 'Editor' || args.type === 'QuickInfo' || args.type === 'DeleteAlert') args.cancel = true
}

// ---------- Modal de reserva ----------
const dialogo = ref(null)
const enviando = ref(false)
const erros = reactive({})
const form = reactive(novoForm())

function novoForm() {
  return {
    surgical_room_id: null,
    procedimento: '',
    cirurgiao: '',
    paciente: '',
    data: dataSelecionada(),
    inicio: null,
    fim: null,
    observacoes: '',
  }
}

function abrirModal() {
  Object.assign(form, novoForm())
  Object.keys(erros).forEach((k) => delete erros[k])
  dialogo.value?.show()
}

const dois = (n) => String(n).padStart(2, '0')
const paraIso = (dia, hora) =>
  `${dia.getFullYear()}-${dois(dia.getMonth() + 1)}-${dois(dia.getDate())} ${dois(hora.getHours())}:${dois(hora.getMinutes())}:00`

// Espelha as regras do backend (SurgicalBookingRequest).
function validar() {
  Object.keys(erros).forEach((k) => delete erros[k])
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
  const resultado = await criarReserva({
    surgical_room_id: form.surgical_room_id,
    procedimento: form.procedimento.trim(),
    cirurgiao: form.cirurgiao.trim(),
    paciente: form.paciente.trim(),
    inicio_previsto: paraIso(form.data, form.inicio),
    fim_previsto: paraIso(form.data, form.fim),
    observacoes: form.observacoes.trim() || null,
  })
  enviando.value = false

  if (resultado.ok) {
    dialogo.value?.hide()
    return
  }
  const mapa = { inicio_previsto: 'inicio', fim_previsto: 'fim' }
  for (const [campo, msg] of Object.entries(resultado.erros)) erros[mapa[campo] ?? campo] = msg
}
</script>

<template>
  <section class="painel">
    <header class="cabecalho">
      <h1 class="titulo">Mapa Cirúrgico Diário</h1>

      <ul class="legenda" aria-label="Legenda de status">
        <li v-for="item in LEGENDA" :key="item.chave" class="legenda-item">
          <span class="bolinha" :class="`status-${item.chave}`" aria-hidden="true"></span>
          {{ item.texto }}
        </li>
      </ul>

      <ejs-button
        v-if="auth.tem('centro-cirurgico.gerenciar')"
        is-primary
        title="Criar uma nova reserva de sala cirúrgica"
        @click="abrirModal"
      >+ Nova Reserva</ejs-button>
    </header>

    <p v-if="erro" class="erro" role="alert">{{ erro }}</p>

    <ejs-schedule
      class="agenda"
      height="650px"
      currentView="Day"
      :selectedDate="dataSelecionada()"
      :showHeaderBar="false"
      :readonly="true"
      :startHour="'08:00'"
      :endHour="'18:00'"
      :timeScale="{ enable: true, interval: 60, slotCount: 2 }"
      :group="{ resources: ['Salas'] }"
      :eventSettings="eventSettings(reservas)"
      :eventRendered="aoRenderizarEvento"
      :popupOpen="aoAbrirPopup"
    >
      <e-views><e-view option="Day" /></e-views>
      <e-resources>
        <e-resource
          field="RoomId"
          title="Sala"
          name="Salas"
          :dataSource="salas"
          textField="Nome"
          idField="Id"
        />
      </e-resources>

      <template v-slot:eventTemplate="{ data: reserva }">
        <div
          class="reserva"
          :title="`${reserva.Subject} — ${reserva.Cirurgiao} (${ROTULO_STATUS[reserva.Status]})`"
        >
          <strong class="reserva-procedimento">{{ reserva.Subject }}</strong>
          <span class="reserva-cirurgiao">{{ reserva.Cirurgiao }}</span>
        </div>
      </template>
    </ejs-schedule>

    <ejs-dialog
      ref="dialogo"
      header="Nova Reserva"
      width="480px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <form class="formulario" novalidate @submit.prevent="salvar">
        <p v-if="erros.geral" class="erro" role="alert">{{ erros.geral }}</p>

        <label class="campo">Sala
          <ejs-dropdownlist
            v-model="form.surgical_room_id"
            :dataSource="salas"
            :fields="{ text: 'Nome', value: 'Id' }"
            placeholder="Selecione a sala"
          />
          <span v-if="erros.surgical_room_id" class="campo-erro">{{ erros.surgical_room_id }}</span>
        </label>

        <label class="campo">Procedimento
          <ejs-textbox v-model="form.procedimento" />
          <span v-if="erros.procedimento" class="campo-erro">{{ erros.procedimento }}</span>
        </label>

        <label class="campo">Cirurgião
          <ejs-textbox v-model="form.cirurgiao" />
          <span v-if="erros.cirurgiao" class="campo-erro">{{ erros.cirurgiao }}</span>
        </label>

        <label class="campo">Paciente (iniciais ou nº do prontuário)
          <ejs-textbox v-model="form.paciente" placeholder="Ex.: M.S." />
          <span v-if="erros.paciente" class="campo-erro">{{ erros.paciente }}</span>
        </label>

        <label class="campo">Data
          <ejs-datepicker v-model="form.data" format="dd/MM/yyyy" />
          <span v-if="erros.data" class="campo-erro">{{ erros.data }}</span>
        </label>

        <div class="linha">
          <label class="campo">Início
            <ejs-timepicker v-model="form.inicio" format="HH:mm" :step="30" />
            <span v-if="erros.inicio" class="campo-erro">{{ erros.inicio }}</span>
          </label>
          <label class="campo">Fim previsto
            <ejs-timepicker v-model="form.fim" format="HH:mm" :step="30" />
            <span v-if="erros.fim" class="campo-erro">{{ erros.fim }}</span>
          </label>
        </div>

        <label class="campo">Observações (opcional)
          <ejs-textbox v-model="form.observacoes" :multiline="true" />
        </label>

        <div class="acoes">
          <ejs-button type="button" @click="dialogo?.hide()">Cancelar</ejs-button>
          <ejs-button type="submit" is-primary :disabled="enviando">
            {{ enviando ? 'Salvando...' : 'Reservar' }}
          </ejs-button>
        </div>
      </form>
    </ejs-dialog>
  </section>
</template>

<style scoped>
.cabecalho {
  display: flex;
  align-items: center;
  gap: 24px;
  margin-bottom: 16px;
}

.titulo {
  margin: 0;
  font-size: 20px;
  color: var(--kordis-navy-900);
}

.legenda {
  display: flex;
  gap: 16px;
  margin: 0 auto 0 0;
  padding: 0;
  list-style: none;
  font-size: 13px;
  color: var(--kordis-text-secondary);
}

.legenda-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.bolinha {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.bolinha.status-agendado { background: var(--kordis-status-agendado-dot); }
.bolinha.status-em_andamento { background: var(--kordis-status-andamento-dot); }
.bolinha.status-atrasado { background: var(--kordis-task-urgente); }
.bolinha.status-limpeza { background: var(--kordis-text-secondary); }

.erro { color: var(--kordis-danger-text); }

.reserva {
  display: flex;
  flex-direction: column;
  padding: 4px 6px;
  color: var(--kordis-text-primary);
}

.reserva-procedimento { font-weight: 700; }
.reserva-cirurgiao { font-size: 12px; color: var(--kordis-text-secondary); }

/* Blocos do Scheduler por status (classe aplicada em eventRendered). */
.agenda :deep(.e-appointment) {
  border: 0;
  border-left: 4px solid transparent;
  color: var(--kordis-text-primary);
}
.agenda :deep(.e-appointment.status-agendado) {
  background: var(--kordis-status-agendado-bg);
  border-left-color: var(--kordis-status-agendado-dot);
}
.agenda :deep(.e-appointment.status-em_andamento) {
  background: var(--kordis-status-andamento-bg);
  border-left-color: var(--kordis-status-andamento-dot);
}
.agenda :deep(.e-appointment.status-atrasado) {
  background: color-mix(in srgb, var(--kordis-task-urgente) 15%, var(--kordis-bg-page));
  border-left-color: var(--kordis-task-urgente);
}
.agenda :deep(.e-appointment.status-limpeza) {
  background: var(--kordis-bg-sidebar);
  border-left-color: var(--kordis-border);
}

.formulario {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.campo {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  font-size: 13px;
  color: var(--kordis-text-secondary);
}

.campo-erro {
  color: var(--kordis-danger-text);
  font-size: 12px;
}

.linha { display: flex; gap: 12px; }

.acoes {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
