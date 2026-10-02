<script setup>
import { ref, reactive, computed, provide } from 'vue'
import {
  GridComponent as EjsGrid,
  ColumnsDirective as EColumns,
  ColumnDirective as EColumn,
  Group,
  Sort,
  Toolbar,
  Search,
} from '@syncfusion/ej2-vue-grids'
import { DialogComponent as EjsDialog } from '@syncfusion/ej2-vue-popups'
import { DropDownListComponent as EjsDropdownlist } from '@syncfusion/ej2-vue-dropdowns'
import { TextBoxComponent as EjsTextbox } from '@syncfusion/ej2-vue-inputs'
import { DateTimePickerComponent as EjsDatetimepicker, DatePickerComponent as EjsDatepicker } from '@syncfusion/ej2-vue-calendars'
import { ButtonComponent as EjsButton } from '@syncfusion/ej2-vue-buttons'
import { useAuthStore } from '../../stores/auth'
import { useBedPresenter } from '../../presenters/useBedPresenter'

provide('grid', [Group, Sort, Toolbar, Search])

const auth = useAuthStore()
const { leitos, erro, mudarStatus } = useBedPresenter()
const podeGerenciar = computed(() => auth.tem('leitos.gerenciar'))

const groupSettings = { columns: ['StatusLabel'], showDropArea: false }
const toolbar = ['Search']

const COR_STATUS = {
  ocupado: 'var(--kordis-task-urgente)',
  limpeza: 'var(--kordis-task-progresso)',
  disponivel: 'var(--kordis-task-concluido)',
}

const OPCOES_STATUS = [
  { valor: 'ocupado', texto: 'Ocupado' },
  { valor: 'limpeza', texto: 'Limpeza' },
  { valor: 'disponivel', texto: 'Disponível' },
]

// ---------- Modal "Mudar status" ----------
const dialogo = ref(null)
const enviando = ref(false)
const leitoAtual = ref(null)
const erros = reactive({})
const form = reactive({ status: null, paciente_nome: '', entrada_em: null, previsao_alta: null })

function abrirModal(leito) {
  leitoAtual.value = leito
  Object.keys(erros).forEach((k) => delete erros[k])
  Object.assign(form, { status: leito.Status, paciente_nome: '', entrada_em: new Date(), previsao_alta: null })
  dialogo.value?.show()
}

const dois = (n) => String(n).padStart(2, '0')
const paraData = (d) => `${d.getFullYear()}-${dois(d.getMonth() + 1)}-${dois(d.getDate())}`
const paraDataHora = (d) => `${paraData(d)} ${dois(d.getHours())}:${dois(d.getMinutes())}:00`

// Espelha as regras do backend (BedStatusRequest).
function validar() {
  Object.keys(erros).forEach((k) => delete erros[k])
  if (!form.status) erros.status = 'O status é obrigatório.'
  if (form.status === 'ocupado') {
    if (!form.paciente_nome.trim()) erros.paciente_nome = 'Informe o paciente para ocupar o leito.'
    if (!form.entrada_em) erros.entrada_em = 'Informe a data/hora de entrada.'
    else if (form.previsao_alta && paraData(form.previsao_alta) < paraData(form.entrada_em)) {
      erros.previsao_alta = 'A previsão de alta não pode ser antes da entrada.'
    }
  }
  return Object.keys(erros).length === 0
}

async function salvar() {
  if (!validar()) return
  enviando.value = true
  const extras = form.status === 'ocupado'
    ? {
        paciente_nome: form.paciente_nome.trim(),
        entrada_em: paraDataHora(form.entrada_em),
        previsao_alta: form.previsao_alta ? paraData(form.previsao_alta) : null,
      }
    : {}
  const resultado = await mudarStatus(leitoAtual.value.Id, form.status, extras)
  enviando.value = false

  if (resultado.ok) {
    dialogo.value?.hide()
    return
  }
  Object.assign(erros, resultado.erros)
}
</script>

<template>
  <section class="painel">
    <h1 class="titulo">Acompanhamento de Leitos</h1>

    <p v-if="erro" class="erro" role="alert">{{ erro }}</p>

    <ejs-grid
      :dataSource="leitos"
      :allowGrouping="true"
      :allowSorting="true"
      :groupSettings="groupSettings"
      :toolbar="toolbar"
    >
      <e-columns>
        <e-column field="Codigo" headerText="Leito" width="160" template="codigoTemplate" />
        <e-column field="Paciente" headerText="Paciente" template="pacienteTemplate" />
        <e-column field="Entrada" headerText="Entrada" width="160" template="entradaTemplate" />
        <e-column field="PrevisaoAlta" headerText="Previsão de alta" width="150" template="previsaoTemplate" />
        <e-column field="Responsavel" headerText="Responsável" template="responsavelTemplate" />
        <e-column field="StatusLabel" headerText="Status" :visible="false" />
        <e-column
          headerText="Ação"
          width="150"
          :visible="podeGerenciar"
          :allowSorting="false"
          :allowGrouping="false"
          template="acaoTemplate"
        />
      </e-columns>

      <template v-slot:codigoTemplate="{ data }">
        <div class="leito" :title="`${data.Codigo} — ${data.StatusLabel}`">
          <span class="faixa" :style="{ background: COR_STATUS[data.Status] }"></span>
          <strong>{{ data.Codigo }}</strong>
        </div>
      </template>
      <template v-slot:pacienteTemplate="{ data }">{{ data.Paciente ?? '—' }}</template>
      <template v-slot:entradaTemplate="{ data }">{{ data.Entrada ?? '—' }}</template>
      <template v-slot:previsaoTemplate="{ data }">{{ data.PrevisaoAlta ?? '—' }}</template>
      <template v-slot:responsavelTemplate="{ data }">{{ data.Responsavel ?? '—' }}</template>
      <template v-slot:acaoTemplate="{ data }">
        <ejs-button :title="`Mudar o status do ${data.Codigo}`" @click="abrirModal(data)">Mudar status</ejs-button>
      </template>
    </ejs-grid>

    <ejs-dialog
      ref="dialogo"
      :header="`Mudar status — ${leitoAtual?.Codigo ?? ''}`"
      width="420px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <form class="formulario" novalidate @submit.prevent="salvar">
        <p v-if="erros.geral" class="erro" role="alert">{{ erros.geral }}</p>

        <label class="campo">Novo status
          <ejs-dropdownlist
            v-model="form.status"
            :dataSource="OPCOES_STATUS"
            :fields="{ text: 'texto', value: 'valor' }"
          />
          <span v-if="erros.status" class="campo-erro">{{ erros.status }}</span>
        </label>

        <template v-if="form.status === 'ocupado'">
          <label class="campo">Paciente
            <ejs-textbox v-model="form.paciente_nome" />
            <span v-if="erros.paciente_nome" class="campo-erro">{{ erros.paciente_nome }}</span>
          </label>
          <label class="campo">Entrada
            <ejs-datetimepicker v-model="form.entrada_em" format="dd/MM/yyyy HH:mm" />
            <span v-if="erros.entrada_em" class="campo-erro">{{ erros.entrada_em }}</span>
          </label>
          <label class="campo">Previsão de alta (opcional)
            <ejs-datepicker v-model="form.previsao_alta" format="dd/MM/yyyy" />
            <span v-if="erros.previsao_alta" class="campo-erro">{{ erros.previsao_alta }}</span>
          </label>
        </template>

        <div class="acoes">
          <ejs-button type="button" @click="dialogo?.hide()">Cancelar</ejs-button>
          <ejs-button type="submit" is-primary :disabled="enviando">
            {{ enviando ? 'Salvando...' : 'Salvar' }}
          </ejs-button>
        </div>
      </form>
    </ejs-dialog>
  </section>
</template>

<style scoped>
.titulo {
  margin: 0 0 16px;
  font-size: 20px;
  color: var(--kordis-navy-900);
}

.erro { color: var(--kordis-danger-text); }

.leito {
  display: flex;
  align-items: center;
  gap: 8px;
}

.faixa {
  width: 4px;
  height: 24px;
  border-radius: 2px;
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
  font-size: 13px;
  color: var(--kordis-text-secondary);
}

.campo-erro {
  color: var(--kordis-danger-text);
  font-size: 12px;
}

.acoes {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
