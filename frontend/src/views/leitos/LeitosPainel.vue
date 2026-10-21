<script setup>
import { ref, reactive, computed } from 'vue'
import { DialogComponent as EjsDialog } from '@syncfusion/ej2-vue-popups'
import { DropDownListComponent as EjsDropdownlist } from '@syncfusion/ej2-vue-dropdowns'
import { TextBoxComponent as EjsTextbox } from '@syncfusion/ej2-vue-inputs'
import { DateTimePickerComponent as EjsDatetimepicker, DatePickerComponent as EjsDatepicker } from '@syncfusion/ej2-vue-calendars'
import { ButtonComponent as EjsButton } from '@syncfusion/ej2-vue-buttons'
import { useAuthStore } from '../../stores/auth'
import { useBedPresenter } from '../../presenters/useBedPresenter'

const auth = useAuthStore()
const { leitos, carregando, erro, mudarStatus } = useBedPresenter()
// Só quem gerencia leitos (enfermeiros) abre o modal; os demais perfis apenas visualizam.
const podeGerenciar = computed(() => auth.tem('leitos.gerenciar'))

// Verde = vago, azul = ocupado, amarelo = aguardando higienização.
const STATUS = {
  disponivel: { texto: 'Vago', icone: 'check_circle' },
  ocupado: { texto: 'Ocupado', icone: 'bed' },
  limpeza: { texto: 'Aguardando Higienização', icone: 'cleaning_services' },
}
const OPCOES_STATUS = Object.entries(STATUS).map(([valor, { texto }]) => ({ valor, texto }))

// ---------- Filtro ----------
const filtro = ref('todos')
const contagem = computed(() => ({
  todos: leitos.value.length,
  ...Object.fromEntries(Object.keys(STATUS).map((s) => [s, leitos.value.filter((l) => l.Status === s).length])),
}))
const leitosFiltrados = computed(() =>
  filtro.value === 'todos' ? leitos.value : leitos.value.filter((l) => l.Status === filtro.value)
)

// ---------- Modal "Alterar Status do Leito" ----------
const dialogo = ref(null)
const enviando = ref(false)
const leitoAtual = ref(null)
const erros = reactive({})
const form = reactive({ status: null, paciente_nome: '', entrada_em: null, previsao_alta: null })

function abrirModal(leito) {
  if (!podeGerenciar.value) return
  leitoAtual.value = leito
  Object.keys(erros).forEach((k) => delete erros[k])
  Object.assign(form, {
    status: leito.Status,
    paciente_nome: leito.Paciente ?? '',
    entrada_em: new Date(),
    previsao_alta: null,
  })
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
    <header class="cabecalho">
      <h1 class="titulo">Acompanhamento de Leitos</h1>

      <div class="filtros" role="group" aria-label="Filtrar leitos por status">
        <button
          class="filtro"
          :class="{ ativo: filtro === 'todos' }"
          type="button"
          @click="filtro = 'todos'"
        >Todos <span class="contagem">{{ contagem.todos }}</span></button>
        <button
          v-for="(info, chave) in STATUS"
          :key="chave"
          class="filtro"
          :class="[`filtro-${chave}`, { ativo: filtro === chave }]"
          type="button"
          @click="filtro = chave"
        >
          <span class="bolinha" aria-hidden="true"></span>{{ info.texto }} <span class="contagem">{{ contagem[chave] }}</span>
        </button>
      </div>
    </header>

    <p v-if="erro" class="erro" role="alert">{{ erro }}</p>
    <p v-if="!carregando && !leitosFiltrados.length" class="vazio">Nenhum leito para exibir.</p>

    <div class="grade">
      <component
        :is="podeGerenciar ? 'button' : 'article'"
        v-for="leito in leitosFiltrados"
        :key="leito.Id"
        class="leito"
        :class="[`leito-${leito.Status}`, { clicavel: podeGerenciar }]"
        :type="podeGerenciar ? 'button' : undefined"
        :title="podeGerenciar ? `Alterar o status do ${leito.Codigo}` : `${leito.Codigo} — ${leito.StatusLabel}`"
        @click="abrirModal(leito)"
      >
        <div class="leito-topo">
          <strong class="leito-codigo">{{ leito.Codigo }}</strong>
          <span class="material-icons leito-icone" aria-hidden="true">{{ STATUS[leito.Status].icone }}</span>
        </div>
        <span class="leito-status">{{ leito.StatusLabel }}</span>

        <dl v-if="leito.Status === 'ocupado'" class="leito-dados">
          <div><dt>Paciente</dt><dd>{{ leito.Paciente }}</dd></div>
          <div><dt>Entrada</dt><dd>{{ leito.Entrada ?? '—' }}</dd></div>
          <div><dt>Previsão de alta</dt><dd>{{ leito.PrevisaoAlta ?? '—' }}</dd></div>
        </dl>
        <dl v-else-if="leito.Status === 'limpeza'" class="leito-dados">
          <div><dt>Registrado por</dt><dd>{{ leito.Responsavel ?? '—' }}</dd></div>
        </dl>
      </component>
    </div>

    <ejs-dialog
      v-if="podeGerenciar"
      ref="dialogo"
      :header="`Alterar Status do Leito — ${leitoAtual?.Codigo ?? ''}`"
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

        <p v-if="form.status === 'limpeza' && leitoAtual?.Status !== 'limpeza'" class="aviso">
          Uma tarefa de higienização será criada automaticamente na coluna Pendente do Dashboard.
        </p>

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
.cabecalho {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}

.titulo { margin: 0; font-size: 20px; color: var(--kordis-navy-900); }
.erro { color: var(--kordis-danger-text); }
.vazio { color: var(--kordis-text-secondary); }

/* Filtros */
.filtros { display: flex; flex-wrap: wrap; gap: 8px; }
.filtro {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  font: inherit;
  font-size: 13px;
  color: var(--kordis-text-secondary);
  background: var(--kordis-bg-page);
  border: 1px solid var(--kordis-border);
  border-radius: 999px;
  cursor: pointer;
}
.filtro.ativo { color: var(--kordis-text-on-navy); background: var(--kordis-navy-800); border-color: var(--kordis-navy-800); }
.contagem { font-weight: 700; }
.bolinha { width: 8px; height: 8px; border-radius: 50%; }
.filtro-disponivel .bolinha { background: var(--kordis-task-concluido); }
.filtro-ocupado .bolinha { background: var(--kordis-status-agendado-dot); }
.filtro-limpeza .bolinha { background: var(--kordis-task-progresso); }

/* Grade de cards */
.grade { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }

.leito {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-height: 130px;
  padding: 14px;
  font: inherit;
  text-align: left;
  color: var(--kordis-text-primary);
  border: 1px solid var(--kordis-border);
  border-top: 5px solid var(--cor-leito);
  border-radius: 8px;
  background: color-mix(in srgb, var(--cor-leito) 10%, var(--kordis-bg-page));
}
.leito-disponivel { --cor-leito: var(--kordis-task-concluido); }
.leito-ocupado { --cor-leito: var(--kordis-status-agendado-dot); }
.leito-limpeza { --cor-leito: var(--kordis-task-progresso); }

.leito.clicavel { cursor: pointer; transition: box-shadow 0.15s, transform 0.15s; }
.leito.clicavel:hover, .leito.clicavel:focus-visible {
  box-shadow: 0 4px 12px rgb(20 36 91 / 15%);
  transform: translateY(-2px);
  outline: none;
}

.leito-topo { display: flex; align-items: center; justify-content: space-between; }
.leito-codigo { font-size: 17px; color: var(--kordis-navy-900); }
.leito-icone { color: var(--cor-leito); font-size: 24px; }
.leito-status { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--kordis-text-secondary); }

.leito-dados { display: flex; flex-direction: column; gap: 4px; margin: 6px 0 0; }
.leito-dados div { display: flex; flex-direction: column; }
.leito-dados dt { font-size: 11px; color: var(--kordis-text-secondary); }
.leito-dados dd { margin: 0; font-size: 13px; }

/* Modal */
.formulario { display: flex; flex-direction: column; gap: 12px; }
.campo { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: var(--kordis-text-secondary); }
.campo-erro { color: var(--kordis-danger-text); font-size: 12px; }
.aviso {
  margin: 0;
  padding: 8px 10px;
  font-size: 12px;
  color: var(--kordis-text-primary);
  background: color-mix(in srgb, var(--kordis-task-progresso) 15%, var(--kordis-bg-page));
  border-left: 3px solid var(--kordis-task-progresso);
  border-radius: 4px;
}
.acoes { display: flex; justify-content: flex-end; gap: 8px; }
</style>
