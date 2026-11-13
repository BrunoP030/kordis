<script setup>
import { ref, reactive, computed } from 'vue'
import {
  KanbanComponent as EjsKanban,
  ColumnsDirective as EColumns,
  ColumnDirective as EColumn,
} from '@syncfusion/ej2-vue-kanban'
import { L10n } from '@syncfusion/ej2-base'
import { DialogComponent as EjsDialog } from '@syncfusion/ej2-vue-popups'
import { DropDownListComponent as EjsDropdownlist } from '@syncfusion/ej2-vue-dropdowns'
import { TextBoxComponent as EjsTextbox } from '@syncfusion/ej2-vue-inputs'
import { ButtonComponent as EjsButton } from '@syncfusion/ej2-vue-buttons'
import { useAuthStore } from '../../stores/auth'
import { useNursingTaskPresenter } from '../../presenters/useNursingTaskPresenter'
import opcoesService from '../../services/opcoesService'

const auth = useAuthStore()
const { tarefas, carregando, erro, moverTarefa, criarTarefa } = useNursingTaskPresenter()

// Só quem tem a permissão (enfermeiros; o backend valida de novo) cria tarefas.
const podeCriar = computed(() => auth.tem('tarefas.criar'))

const kanban = ref(null)
const cardSettings = { headerField: 'Id', template: 'cardTemplate' }
const STATUS_TAREFA = [
  { valor: 'a_fazer', texto: 'Pendente' },
  { valor: 'em_andamento', texto: 'Em andamento' },
  { valor: 'concluido', texto: 'Concluído' },
]
const dialogSettings = {
  fields: [
    { key: 'Id', text: 'Identificador', type: 'TextBox' },
    { key: 'Status', text: 'Status', type: 'DropDown' },
    { key: 'Subtitulo', text: 'Detalhes', type: 'TextArea' },
  ],
}

L10n.load({
  'pt-BR': {
    kanban: {
      items: 'itens',
      min: 'Mín.',
      max: 'Máx.',
      cardsSelected: 'Cartões selecionados',
      addTitle: 'Adicionar novo cartão',
      editTitle: 'Editar detalhes do cartão',
      deleteTitle: 'Excluir cartão',
      deleteContent: 'Tem certeza de que deseja excluir este cartão?',
      save: 'Salvar',
      delete: 'Excluir',
      cancel: 'Cancelar',
      yes: 'Sim',
      no: 'Não',
      close: 'Fechar',
      noCard: 'Nenhum cartão para exibir',
      unassigned: 'Não atribuído',
      cards: 'Cartões',
    },
  },
})

function aoAbrirDialogo(args) {
  const campoStatus = args.element?.querySelector('.e-dropdownlist')
  const dropdown = campoStatus?.ej2_instances?.[0]
  if (!dropdown) return

  const statusAtual = dropdown.value
  dropdown.setProperties({
    dataSource: STATUS_TAREFA,
    fields: { text: 'texto', value: 'valor' },
  })
  dropdown.value = statusAtual
  dropdown.dataBind()
}

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
// Prioridades, categorias, leitos e pacientes vêm dos cadastros do banco.
const opcoes = reactive({ Prioridades: [], Categorias: [], Leitos: [], Pacientes: [] })
const idPorNome = (lista, nome) => lista.find((o) => o.Nome === nome)?.Id ?? lista[0]?.Id ?? null

async function carregarOpcoes() {
  try {
    const [cadastros, pacientes] = await Promise.all([opcoesService.cadastros(), opcoesService.pacientes()])
    Object.assign(opcoes, cadastros, { Pacientes: pacientes })
  } catch {
    erros.geral = 'Não foi possível carregar as opções do formulário.'
  }
}

const dialogo = ref(null)
const enviando = ref(false)
const erros = reactive({})
const form = reactive({})

const novoForm = () => ({
  titulo: '',
  descricao: '',
  prioridade_id: idPorNome(opcoes.Prioridades, 'Normal'),
  categoria_id: idPorNome(opcoes.Categorias, 'Geral'),
  leito_id: null,
  paciente_id: null,
})

async function abrirModal() {
  Object.keys(erros).forEach((k) => delete erros[k])
  dialogo.value?.show()
  await carregarOpcoes()
  Object.assign(form, novoForm())
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
    prioridade_id: form.prioridade_id,
    categoria_id: form.categoria_id,
    leito_id: form.leito_id || null,
    paciente_id: form.paciente_id || null,
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
      :dialogSettings="dialogSettings"
      locale="pt-BR"
      :allowDragAndDrop="true"
      @dragStop="aoSoltar"
      @dialogOpen="aoAbrirDialogo"
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
            <ejs-dropdownlist v-model="form.prioridade_id" :dataSource="opcoes.Prioridades" :fields="{ text: 'Nome', value: 'Id' }" />
            <span v-if="erros.prioridade_id" class="campo-erro">{{ erros.prioridade_id }}</span>
          </label>
          <label class="campo">Categoria
            <ejs-dropdownlist v-model="form.categoria_id" :dataSource="opcoes.Categorias" :fields="{ text: 'Nome', value: 'Id' }" />
            <span v-if="erros.categoria_id" class="campo-erro">{{ erros.categoria_id }}</span>
          </label>
        </div>

        <div class="linha">
          <label class="campo">Leito
            <ejs-dropdownlist
              v-model="form.leito_id"
              :dataSource="opcoes.Leitos"
              :fields="{ text: 'Nome', value: 'Id' }"
              :showClearButton="true"
              placeholder="Nenhum"
            />
            <span v-if="erros.leito_id" class="campo-erro">{{ erros.leito_id }}</span>
          </label>
          <label class="campo">Paciente
            <ejs-dropdownlist
              v-model="form.paciente_id"
              :dataSource="opcoes.Pacientes"
              :fields="{ text: 'Nome', value: 'Id' }"
              :allowFiltering="true"
              filterType="Contains"
              :showClearButton="true"
              placeholder="Nenhum"
            />
            <span v-if="erros.paciente_id" class="campo-erro">{{ erros.paciente_id }}</span>
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
  /* Tipografia própria: o tema Syncfusion v35 aumentou a fonte padrão do card (15px/24px). */
  font-size: 12px;
  line-height: 1.35;
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
