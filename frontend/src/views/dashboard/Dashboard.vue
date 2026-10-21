<script setup>
import { ref, reactive, computed } from 'vue'
import {
  KanbanComponent as EjsKanban,
  ColumnsDirective as EColumns,
  ColumnDirective as EColumn,
} from '@syncfusion/ej2-vue-kanban'
import { DialogComponent as EjsDialog } from '@syncfusion/ej2-vue-popups'
import { DropDownListComponent as EjsDropdownlist } from '@syncfusion/ej2-vue-dropdowns'
import { TextBoxComponent as EjsTextbox } from '@syncfusion/ej2-vue-inputs'
import { ButtonComponent as EjsButton } from '@syncfusion/ej2-vue-buttons'
import { useAuthStore } from '../../stores/auth'
import { useNursingTaskPresenter } from '../../presenters/useNursingTaskPresenter'

const auth = useAuthStore()
const { tarefas, carregando, erro, moverTarefa, criarTarefa } = useNursingTaskPresenter()

// Só quem tem a permissão (enfermeiros; o backend valida de novo) cria tarefas.
const podeCriar = computed(() => auth.tem('tarefas.criar'))

const kanban = ref(null)
const cardSettings = { headerField: 'Id', template: 'cardTemplate' }

// Chamado ao soltar o card. Se o servidor recusar, devolve o card à coluna original.
async function aoSoltar(args) {
  const card = args.data?.[0]
  if (!card) return

  const colunaOrigem = card.Status
  const novaColuna = args.event?.target?.closest?.('td.e-content-cells')?.getAttribute('data-key')
  if (!novaColuna || novaColuna === colunaOrigem) return

  const ok = await moverTarefa(card.Id, novaColuna)
  if (!ok) {
    kanban.value?.updateCard({ ...card, Status: colunaOrigem })
  }
}

// ---------- Modal "Nova Tarefa" ----------
const PRIORIDADES = [
  { valor: 'urgente', texto: 'Urgente' },
  { valor: 'normal', texto: 'Normal' },
  { valor: 'baixa', texto: 'Baixa' },
]
const CATEGORIAS = ['Geral', 'Assistência', 'Higienização', 'Administrativa']

const dialogo = ref(null)
const enviando = ref(false)
const erros = reactive({})
const form = reactive({})

const novoForm = () => ({
  titulo: '',
  descricao: '',
  prioridade: 'normal',
  categoria: 'Geral',
  leito: '',
  paciente_nome: '',
})

function abrirModal() {
  Object.keys(erros).forEach((k) => delete erros[k])
  Object.assign(form, novoForm())
  dialogo.value?.show()
}

async function salvar() {
  Object.keys(erros).forEach((k) => delete erros[k])
  if (!form.titulo.trim()) {
    erros.titulo = 'Informe o título da tarefa.'
    return
  }

  enviando.value = true
  const texto = (v) => v.trim() || null
  const resultado = await criarTarefa({
    titulo: form.titulo.trim(),
    descricao: texto(form.descricao),
    prioridade: form.prioridade,
    categoria: form.categoria,
    leito: texto(form.leito),
    paciente_nome: texto(form.paciente_nome),
  })
  enviando.value = false

  if (resultado.ok) {
    dialogo.value?.hide()
    return
  }
  Object.assign(erros, resultado.erros)
}
</script>

<template>
  <section class="dashboard">
    <header class="cabecalho">
      <h1 class="titulo">Fluxo de Atividades da Enfermagem</h1>
      <ejs-button v-if="podeCriar" is-primary title="Criar uma nova tarefa" @click="abrirModal">
        <span class="material-icons">add</span> Nova Tarefa
      </ejs-button>
    </header>

    <p v-if="erro" class="erro" role="alert">{{ erro }}</p>
    <p v-if="carregando" class="carregando">Carregando atividades...</p>

    <ejs-kanban
      ref="kanban"
      keyField="Status"
      :dataSource="tarefas"
      :cardSettings="cardSettings"
      :allowDragAndDrop="true"
      @dragStop="aoSoltar"
    >
      <e-columns>
        <!-- headerText é só o rótulo; o keyField continua "a_fazer" para casar com o banco. -->
        <e-column headerText="Pendente" keyField="a_fazer" />
        <e-column headerText="Em Andamento" keyField="em_andamento" />
        <e-column headerText="Concluído" keyField="concluido" />
      </e-columns>

      <template v-slot:cardTemplate="{ data }">
        <div
          class="tarefa"
          :style="{ borderLeftColor: data.CorBorda }"
          :title="`${data.Titulo} — ${data.Subtitulo}`"
        >
          <div class="tarefa-texto">
            <strong class="tarefa-titulo" :style="{ color: data.CorBorda }">{{ data.Titulo }}</strong>
            <span class="tarefa-subtitulo">{{ data.Subtitulo }}</span>
            <span v-if="data.Categoria && data.Categoria !== 'Geral'" class="tarefa-categoria">{{ data.Categoria }}</span>
          </div>
          <span v-if="data.Concluido === true" class="tarefa-ok" title="Atividade concluída">OK</span>
        </div>
      </template>
    </ejs-kanban>

    <ejs-dialog
      v-if="podeCriar"
      ref="dialogo"
      header="Nova Tarefa"
      width="460px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <form class="formulario" novalidate @submit.prevent="salvar">
        <p v-if="erros.geral" class="erro" role="alert">{{ erros.geral }}</p>

        <label class="campo">Título *
          <ejs-textbox v-model="form.titulo" placeholder="Ex.: Troca de curativo — Leito 203" />
          <span v-if="erros.titulo" class="campo-erro">{{ erros.titulo }}</span>
        </label>

        <label class="campo">Descrição
          <ejs-textbox v-model="form.descricao" :multiline="true" />
        </label>

        <div class="linha">
          <label class="campo">Prioridade
            <ejs-dropdownlist v-model="form.prioridade" :dataSource="PRIORIDADES" :fields="{ text: 'texto', value: 'valor' }" />
            <span v-if="erros.prioridade" class="campo-erro">{{ erros.prioridade }}</span>
          </label>
          <label class="campo">Categoria
            <ejs-dropdownlist v-model="form.categoria" :dataSource="CATEGORIAS" />
            <span v-if="erros.categoria" class="campo-erro">{{ erros.categoria }}</span>
          </label>
        </div>

        <div class="linha">
          <label class="campo">Leito
            <ejs-textbox v-model="form.leito" placeholder="Ex.: Leito 203" />
          </label>
          <label class="campo">Paciente
            <ejs-textbox v-model="form.paciente_nome" />
          </label>
        </div>

        <div class="acoes">
          <ejs-button type="button" @click="dialogo?.hide()">Cancelar</ejs-button>
          <ejs-button type="submit" is-primary :disabled="enviando">{{ enviando ? 'Salvando...' : 'Criar tarefa' }}</ejs-button>
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
  gap: 16px;
  margin-bottom: 16px;
}

.titulo {
  margin: 0;
  font-size: 20px;
  color: var(--kordis-navy-900);
}

.cabecalho .material-icons { margin-right: 4px; }

.erro {
  color: var(--kordis-danger-text);
}

.carregando {
  color: var(--kordis-text-secondary);
}

.tarefa {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 12px;
  background: var(--kordis-bg-page);
  border: 1px solid var(--kordis-border);
  border-left: 4px solid var(--kordis-navy-800);
  border-radius: 6px;
}

.tarefa-texto {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.tarefa-titulo {
  font-weight: 700;
}

.tarefa-subtitulo {
  font-size: 13px;
  color: var(--kordis-text-secondary);
}

.tarefa-categoria {
  align-self: flex-start;
  margin-top: 4px;
  padding: 1px 8px;
  font-size: 11px;
  font-weight: 600;
  color: var(--kordis-text-secondary);
  background: var(--kordis-bg-sidebar);
  border: 1px solid var(--kordis-border);
  border-radius: 999px;
}

.tarefa-ok {
  padding: 2px 10px;
  font-size: 12px;
  font-weight: 700;
  border-radius: 999px;
  color: var(--kordis-text-on-navy);
  background: var(--kordis-task-concluido);
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
