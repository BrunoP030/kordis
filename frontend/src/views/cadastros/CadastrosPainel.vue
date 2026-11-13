<script setup>
import { ref, reactive, computed, provide } from 'vue'
import { GridComponent as EjsGrid, Page, Sort } from '@syncfusion/ej2-vue-grids'
import { DialogComponent as EjsDialog } from '@syncfusion/ej2-vue-popups'
import { ComboBoxComponent as EjsCombobox, DropDownListComponent as EjsDropdownlist } from '@syncfusion/ej2-vue-dropdowns'
import { TextBoxComponent as EjsTextbox } from '@syncfusion/ej2-vue-inputs'
import { DatePickerComponent as EjsDatepicker } from '@syncfusion/ej2-vue-calendars'
import { ButtonComponent as EjsButton } from '@syncfusion/ej2-vue-buttons'
import { L10n } from '@syncfusion/ej2-base'
import { useCadastroPresenter } from '../../presenters/useCadastroPresenter'

provide('grid', [Page, Sort])

L10n.load({
  'pt-BR': {
    grid: { EmptyRecord: 'Nenhum registro cadastrado' },
    pager: {
      currentPageInfo: '{0} de {1} páginas',
      totalItemsInfo: '({0} registros)',
      totalItemInfo: '({0} registro)',
      firstPageTooltip: 'Primeira página',
      lastPageTooltip: 'Última página',
      nextPageTooltip: 'Próxima página',
      previousPageTooltip: 'Página anterior',
      nextPagerTooltip: 'Próximo grupo de páginas',
      previousPagerTooltip: 'Grupo de páginas anterior',
    },
  },
})

const {
  definicoes, atual, chaveAtual, linhas, opcoesFk, carregando, erro,
  selecionar, carregarOpcoes, salvar, excluir,
} = useCadastroPresenter()

// Campos "0 ou 1": 0 = Não, 1 = Sim (padrão Sim).
const SIM_NAO = [{ valor: 1, texto: 'Sim' }, { valor: 0, texto: 'Não' }]

// ---------- Grid ----------
const grid = ref(null)
const selecionado = ref(null)

const dataBr = (iso) => (iso ? iso.split('-').reverse().join('/') : '')

function formatar(campo, linha) {
  const valor = linha[campo.nome]
  switch (campo.tipo) {
    case 'fk': return linha[`${campo.nome}__nome`] ?? ''
    case 'simnao': return valor === 1 ? 'Sim' : valor === 0 ? 'Não' : ''
    case 'opcoes': return campo.opcoes.find((o) => o.valor === valor)?.texto ?? valor ?? ''
    case 'data': return dataBr(valor)
    default: return valor ?? ''
  }
}

// Largura de cada coluna em %, por peso: o nome (1ª coluna) vale 2, Sim/Não vale menos.
// Assim as colunas sempre cabem; texto longo vira "…" com o conteúdo completo no tooltip.
const PESOS = { simnao: 0.7, data: 0.9, opcoes: 0.9 }

// Colunas do grid geradas a partir da definição do cadastro (só os campos marcados para o grid).
const colunas = computed(() => {
  const campos = (atual.value?.campos ?? []).filter((c) => c.grid)
  const pesos = campos.map((c, i) => PESOS[c.tipo] ?? (i === 0 ? 2 : 1))
  const total = pesos.reduce((a, b) => a + b, 0)

  return campos.map((c, i) => ({
    field: c.nome,
    headerText: c.rotulo,
    valueAccessor: (_campo, linha) => formatar(c, linha),
    width: `${((pesos[i] / total) * 100).toFixed(2)}%`,
    clipMode: 'EllipsisWithTooltip',
    textAlign: c.tipo === 'simnao' ? 'Center' : 'Left',
  }))
})

function trocarCadastro(chave) {
  if (chave === chaveAtual.value) return
  selecionado.value = null
  selecionar(chave)
}

const aoSelecionar = (args) => { selecionado.value = args.data }
const aoDesselecionar = () => { selecionado.value = grid.value?.ej2Instances?.getSelectedRecords()?.[0] ?? null }

// ---------- Formulário (inserir / editar) ----------
const dlgForm = ref(null)
const editandoId = ref(null)
const enviando = ref(false)
const erros = reactive({})
const form = reactive({})

const limpar = (obj) => Object.keys(obj).forEach((k) => delete obj[k])
const paraDate = (iso) => {
  if (!iso) return null
  const [a, m, d] = iso.split('-').map(Number)
  return new Date(a, m - 1, d)
}
const dois = (n) => String(n).padStart(2, '0')
const paraIso = (d) => (d ? `${d.getFullYear()}-${dois(d.getMonth() + 1)}-${dois(d.getDate())}` : null)

async function abrirForm(registro = null) {
  limpar(erros)
  limpar(form)
  editandoId.value = registro?.Id ?? null

  for (const c of atual.value.campos) {
    const valor = registro?.[c.nome]
    if (c.tipo === 'simnao') form[c.nome] = valor ?? 1
    else if (c.tipo === 'data') form[c.nome] = paraDate(valor)
    else form[c.nome] = valor ?? null
  }

  try {
    await carregarOpcoes(registro)
  } catch {
    erros.geral = 'Não foi possível carregar as opções dos campos.'
  }
  dlgForm.value?.show()
}

const editarSelecionado = () => selecionado.value && abrirForm(selecionado.value)

async function salvarForm() {
  limpar(erros)
  for (const c of atual.value.campos) {
    const v = form[c.nome]
    if (c.obrigatorio && (v === null || v === undefined || (typeof v === 'string' && !v.trim()))) {
      erros[c.nome] = `Preencha o campo ${c.rotulo}.`
    }
  }
  if (Object.keys(erros).length) return

  const dados = {}
  for (const c of atual.value.campos) {
    const v = form[c.nome]
    dados[c.nome] = c.tipo === 'data' ? paraIso(v) : typeof v === 'string' ? v.trim() || null : v ?? null
  }

  enviando.value = true
  const r = await salvar(dados, editandoId.value)
  enviando.value = false

  if (r.ok) {
    dlgForm.value?.hide()
    selecionado.value = null
  } else {
    Object.assign(erros, r.erros)
  }
}

// ---------- Exclusão ----------
const dlgExcluir = ref(null)
const erroExcluir = ref(null)
const excluindo = ref(false)

function pedirExclusao() {
  if (!selecionado.value || selecionado.value.Protegido) return
  erroExcluir.value = null
  dlgExcluir.value?.show()
}

const descricaoSelecionado = computed(() => {
  if (!selecionado.value || !atual.value) return ''
  const principal = atual.value.campos.find((c) => c.grid)
  return principal ? formatar(principal, selecionado.value) : `#${selecionado.value.Id}`
})

async function confirmarExclusao() {
  excluindo.value = true
  const r = await excluir(selecionado.value.Id)
  excluindo.value = false
  if (r.ok) {
    dlgExcluir.value?.hide()
    selecionado.value = null
  } else {
    erroExcluir.value = r.erros.geral ?? Object.values(r.erros)[0]
  }
}
</script>

<template>
  <section class="painel">
    <header class="cabecalho">
      <h1 class="titulo">Cadastro</h1>
    </header>

    <div class="layout">
      <nav class="lista-cadastros" aria-label="Cadastros">
        <button
          v-for="d in definicoes"
          :key="d.chave"
          type="button"
          class="item-cadastro"
          :class="{ ativo: d.chave === chaveAtual }"
          :aria-current="d.chave === chaveAtual ? 'page' : undefined"
          @click="trocarCadastro(d.chave)"
        >{{ d.titulo }}</button>
      </nav>

      <div v-if="atual" class="conteudo">
        <div class="barra">
          <h2 class="subtitulo">{{ atual.titulo }}</h2>
          <div class="botoes">
            <ejs-button is-primary @click="abrirForm()">
              <span class="material-icons" aria-hidden="true">add</span> Inserir
            </ejs-button>
            <ejs-button :disabled="!selecionado" title="Selecione uma linha para editar" @click="editarSelecionado">
              <span class="material-icons" aria-hidden="true">edit</span> Editar
            </ejs-button>
            <ejs-button
              :disabled="!selecionado || selecionado.Protegido"
              :title="selecionado?.Protegido ? 'Registro usado pelo sistema: não pode ser excluído' : 'Selecione uma linha para excluir'"
              @click="pedirExclusao"
            >
              <span class="material-icons" aria-hidden="true">delete</span> Excluir
            </ejs-button>
          </div>
        </div>

        <p v-if="erro" class="erro" role="alert">{{ erro }}</p>

        <ejs-grid
          :key="chaveAtual"
          ref="grid"
          :dataSource="linhas"
          :columns="colunas"
          :allowPaging="true"
          :allowSorting="true"
          :pageSettings="{ pageSize: 15 }"
          :selectionSettings="{ type: 'Single' }"
          locale="pt-BR"
          height="auto"
          @rowSelected="aoSelecionar"
          @rowDeselected="aoDesselecionar"
          @recordDoubleClick="(args) => abrirForm(args.rowData)"
        />
        <p class="dica">{{ carregando ? 'Carregando...' : 'Dê um duplo clique em uma linha para editar.' }}</p>
      </div>
    </div>

    <!-- Inserir / editar -->
    <ejs-dialog
      ref="dlgForm"
      :header="`${editandoId ? 'Editar' : 'Inserir'} ${atual?.singular ?? ''}`"
      width="560px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <!-- Sem v-if no form: o diálogo precisa nascer com conteúdo para ter o espaçamento interno. -->
      <form class="formulario" novalidate @submit.prevent="salvarForm">
        <p v-if="erros.geral" class="erro" role="alert">{{ erros.geral }}</p>

        <div class="grade-campos">
          <label
            v-for="c in atual?.campos ?? []"
            :key="`${chaveAtual}-${c.nome}`"
            class="campo"
            :class="{ largo: c.tipo === 'textarea' }"
          >
            {{ c.rotulo }}{{ c.obrigatorio ? ' *' : '' }}

            <ejs-combobox
              v-if="c.tipo === 'fk'"
              v-model="form[c.nome]"
              :dataSource="opcoesFk[c.nome] ?? []"
              :fields="{ text: 'Nome', value: 'Id' }"
              :allowFiltering="true"
              :allowCustom="false"
              :showClearButton="!c.obrigatorio"
              placeholder="Selecione"
            />
            <ejs-dropdownlist
              v-else-if="c.tipo === 'simnao'"
              v-model="form[c.nome]"
              :dataSource="SIM_NAO"
              :fields="{ text: 'texto', value: 'valor' }"
            />
            <ejs-dropdownlist
              v-else-if="c.tipo === 'opcoes'"
              v-model="form[c.nome]"
              :dataSource="c.opcoes"
              :fields="{ text: 'texto', value: 'valor' }"
              :showClearButton="!c.obrigatorio"
              placeholder="Selecione"
            />
            <ejs-datepicker
              v-else-if="c.tipo === 'data'"
              v-model="form[c.nome]"
              format="dd/MM/yyyy"
              placeholder="dd/mm/aaaa"
            />
            <ejs-textbox
              v-else
              v-model="form[c.nome]"
              :multiline="c.tipo === 'textarea'"
              :type="c.tipo === 'email' ? 'email' : 'text'"
              :maxlength="c.max ?? undefined"
            />

            <span v-if="erros[c.nome]" class="campo-erro">{{ erros[c.nome] }}</span>
          </label>
        </div>

        <div class="acoes">
          <ejs-button type="button" @click="dlgForm?.hide()">Cancelar</ejs-button>
          <ejs-button type="submit" is-primary :disabled="enviando">{{ enviando ? 'Salvando...' : 'Salvar' }}</ejs-button>
        </div>
      </form>
    </ejs-dialog>

    <!-- Confirmação de exclusão -->
    <ejs-dialog
      ref="dlgExcluir"
      header="Excluir registro"
      width="420px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <div class="formulario">
        <p>Excluir <strong>{{ descricaoSelecionado }}</strong> de {{ atual?.titulo }}? Esta ação não pode ser desfeita.</p>
        <p v-if="erroExcluir" class="erro" role="alert">{{ erroExcluir }}</p>
        <div class="acoes">
          <ejs-button type="button" @click="dlgExcluir?.hide()">Cancelar</ejs-button>
          <ejs-button type="button" cssClass="e-danger" :disabled="excluindo" @click="confirmarExclusao">
            {{ excluindo ? 'Excluindo...' : 'Excluir' }}
          </ejs-button>
        </div>
      </div>
    </ejs-dialog>
  </section>
</template>

<style scoped>
.cabecalho { margin-bottom: 16px; }
.titulo { margin: 0; font-size: 20px; color: var(--kordis-navy-900); }
.subtitulo { margin: 0; font-size: 16px; color: var(--kordis-navy-900); }
.erro { color: var(--kordis-danger-text); }
.dica { margin: 8px 0 0; font-size: 12px; color: var(--kordis-text-secondary); }

.layout {
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: 16px;
  align-items: start;
}

.lista-cadastros {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 8px;
  border: 1px solid var(--kordis-border);
  border-radius: 8px;
  background: var(--kordis-bg-sidebar);
}

.item-cadastro {
  text-align: left;
  padding: 8px 10px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: var(--kordis-text-primary);
  font: inherit;
  font-size: 14px;
  cursor: pointer;
}
.item-cadastro:hover { background: var(--kordis-border); }
.item-cadastro.ativo { background: var(--kordis-navy-800); color: var(--kordis-text-on-navy); }
.item-cadastro:focus-visible { outline: 2px solid var(--kordis-teal-500); outline-offset: 1px; }

.conteudo { min-width: 0; }

.barra {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}
.botoes { display: flex; gap: 8px; flex-wrap: wrap; }
.botoes .material-icons { font-size: 18px; vertical-align: middle; margin-right: 2px; }

.formulario { display: flex; flex-direction: column; gap: 12px; }
.grade-campos { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.campo {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
  color: var(--kordis-text-secondary);
}
.campo.largo { grid-column: 1 / -1; }
.campo-erro { color: var(--kordis-danger-text); font-size: 12px; }
.acoes { display: flex; justify-content: flex-end; gap: 8px; }

@media (max-width: 760px) {
  .layout { grid-template-columns: 1fr; }
  .lista-cadastros { flex-direction: row; flex-wrap: wrap; }
  .grade-campos { grid-template-columns: 1fr; }
}
</style>
