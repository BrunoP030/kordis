<script setup>
import { ref, reactive, computed } from 'vue'
import { DialogComponent as EjsDialog } from '@syncfusion/ej2-vue-popups'
import { DropDownListComponent as EjsDropdownlist } from '@syncfusion/ej2-vue-dropdowns'
import { TextBoxComponent as EjsTextbox } from '@syncfusion/ej2-vue-inputs'
import { ButtonComponent as EjsButton } from '@syncfusion/ej2-vue-buttons'
import { useAuthStore } from '../../stores/auth'
import { useClinicalRecordPresenter } from '../../presenters/useClinicalRecordPresenter'

const auth = useAuthStore()
// Apenas médicos/cirurgiões (prontuarios.criar) veem o botão e o formulário de inserção.
const podeCriar = computed(() => auth.tem('prontuarios.criar'))

const {
  pacientes, paciente, registros, carregandoPacientes, carregandoHistorico, erro,
  buscarPacientes, selecionarPaciente, criarRegistro,
} = useClinicalRecordPresenter()

const TIPOS = [
  { valor: 'evolucao', texto: 'Evolução clínica', icone: 'monitor_heart' },
  { valor: 'prescricao', texto: 'Prescrição', icone: 'medication' },
  { valor: 'exame', texto: 'Exame', icone: 'biotech' },
  { valor: 'procedimento', texto: 'Procedimento', icone: 'medical_services' },
  { valor: 'alta', texto: 'Alta', icone: 'logout' },
]
const info = (tipo) => TIPOS.find((t) => t.valor === tipo) ?? TIPOS[0]

const formataData = (iso) =>
  new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })

// ---------- Busca de pacientes (com debounce) ----------
const busca = ref('')
let temporizador = null
function aoDigitar() {
  clearTimeout(temporizador)
  temporizador = setTimeout(() => buscarPacientes(busca.value.trim()), 300)
}

// ---------- Filtro do histórico ----------
const filtroTipo = ref('todos')
const registrosFiltrados = computed(() =>
  filtroTipo.value === 'todos' ? registros.value : registros.value.filter((r) => r.Tipo === filtroTipo.value)
)

// ---------- Modal "Novo Registro / Evolução Clínica" ----------
const dialogo = ref(null)
const enviando = ref(false)
const erros = reactive({})
const form = reactive({ tipo: 'evolucao', titulo: '', descricao: '' })

function abrirModal() {
  Object.keys(erros).forEach((k) => delete erros[k])
  Object.assign(form, { tipo: 'evolucao', titulo: '', descricao: '' })
  dialogo.value?.show()
}

async function salvar() {
  Object.keys(erros).forEach((k) => delete erros[k])
  if (!form.titulo.trim()) erros.titulo = 'Informe um título para o registro.'
  if (!form.descricao.trim()) erros.descricao = 'Descreva a evolução clínica.'
  if (Object.keys(erros).length) return

  enviando.value = true
  const resultado = await criarRegistro({
    tipo: form.tipo,
    titulo: form.titulo.trim(),
    descricao: form.descricao.trim(),
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
  <section class="painel">
    <div class="topo">
      <h1 class="titulo">Prontuários</h1>
      <ejs-button
        v-if="podeCriar"
        is-primary
        :disabled="!paciente"
        :title="paciente ? 'Adicionar prontuário' : 'Selecione um paciente para adicionar um prontuário'"
        @click="abrirModal"
      >
        <span class="material-icons">add</span> Adicionar Prontuário
      </ejs-button>
    </div>

    <p v-if="erro" class="erro" role="alert">{{ erro }}</p>

    <div class="layout">
      <!-- Lista de pacientes -->
      <aside class="pacientes" aria-label="Pacientes">
        <label class="busca">
          <span class="material-icons" aria-hidden="true">search</span>
          <input v-model="busca" type="search" placeholder="Buscar paciente por nome" @input="aoDigitar" />
        </label>

        <p v-if="!carregandoPacientes && !pacientes.length" class="vazio-lista">Nenhum paciente encontrado.</p>

        <ul class="lista">
          <li v-for="p in pacientes" :key="p.Id">
            <button
              type="button"
              class="paciente"
              :class="{ ativo: paciente?.Id === p.Id }"
              @click="selecionarPaciente(p.Id)"
            >
              <strong>{{ p.Nome }}</strong>
              <span>{{ p.Idade }} anos · {{ p.Sexo }}</span>
            </button>
          </li>
        </ul>
      </aside>

      <!-- Histórico clínico -->
      <div class="historico">
        <div v-if="!paciente" class="vazio">
          <span class="material-icons vazio-icone">folder_shared</span>
          <h2>Selecione um paciente</h2>
          <p>Escolha um paciente ao lado para ver o histórico clínico.</p>
        </div>

        <template v-else>
          <header class="ficha">
            <div>
              <h2 class="ficha-nome">{{ paciente.Nome }}</h2>
              <p class="ficha-dados">
                {{ paciente.Idade }} anos · {{ paciente.Sexo }}
                <template v-if="paciente.TipoSanguineo"> · Tipo sanguíneo {{ paciente.TipoSanguineo }}</template>
              </p>
              <p v-if="paciente.Alergias" class="alergia">
                <span class="material-icons" aria-hidden="true">warning</span> Alergias: {{ paciente.Alergias }}
              </p>
            </div>

            <ejs-button v-if="podeCriar" is-primary title="Registrar uma nova evolução clínica" @click="abrirModal">
              <span class="material-icons">add</span> Novo Registro / Evolução Clínica
            </ejs-button>
          </header>

          <div class="filtros" role="group" aria-label="Filtrar por tipo de registro">
            <button class="filtro" :class="{ ativo: filtroTipo === 'todos' }" type="button" @click="filtroTipo = 'todos'">Todos</button>
            <button
              v-for="t in TIPOS"
              :key="t.valor"
              class="filtro"
              :class="{ ativo: filtroTipo === t.valor }"
              type="button"
              @click="filtroTipo = t.valor"
            >{{ t.texto }}</button>
          </div>

          <p v-if="carregandoHistorico" class="vazio-lista">Carregando histórico...</p>
          <p v-else-if="!registrosFiltrados.length" class="vazio-lista">Nenhum registro clínico para este filtro.</p>

          <ol class="linha-tempo">
            <li v-for="r in registrosFiltrados" :key="r.Id" class="registro">
              <span class="marcador" aria-hidden="true"><span class="material-icons">{{ info(r.Tipo).icone }}</span></span>
              <article class="registro-corpo">
                <header class="registro-topo">
                  <span class="registro-tipo">{{ info(r.Tipo).texto }}</span>
                  <time :datetime="r.CriadoEm">{{ formataData(r.CriadoEm) }}</time>
                </header>
                <h3 class="registro-titulo">{{ r.Titulo }}</h3>
                <p class="registro-descricao">{{ r.Descricao }}</p>
                <footer class="registro-autor">Registrado por {{ r.Autor ?? 'usuário removido' }}</footer>
              </article>
            </li>
          </ol>
        </template>
      </div>
    </div>

    <ejs-dialog
      v-if="podeCriar"
      ref="dialogo"
      header="Novo Registro / Evolução Clínica"
      width="560px"
      :visible="false"
      :isModal="true"
      :showCloseIcon="true"
      :closeOnEscape="true"
    >
      <form class="formulario" novalidate @submit.prevent="salvar">
        <p v-if="erros.geral" class="erro" role="alert">{{ erros.geral }}</p>
        <p class="paciente-modal">Paciente: <strong>{{ paciente?.Nome }}</strong></p>

        <label class="campo">Tipo de registro
          <ejs-dropdownlist v-model="form.tipo" :dataSource="TIPOS" :fields="{ text: 'texto', value: 'valor' }" />
          <span v-if="erros.tipo" class="campo-erro">{{ erros.tipo }}</span>
        </label>

        <label class="campo">Título *
          <ejs-textbox v-model="form.titulo" placeholder="Ex.: Evolução pós-operatória — D1" />
          <span v-if="erros.titulo" class="campo-erro">{{ erros.titulo }}</span>
        </label>

        <label class="campo">Descrição *
          <ejs-textbox v-model="form.descricao" :multiline="true" placeholder="Quadro clínico, conduta, intercorrências..." />
          <span v-if="erros.descricao" class="campo-erro">{{ erros.descricao }}</span>
        </label>

        <div class="acoes">
          <ejs-button type="button" @click="dialogo?.hide()">Cancelar</ejs-button>
          <ejs-button type="submit" is-primary :disabled="enviando">{{ enviando ? 'Salvando...' : 'Salvar registro' }}</ejs-button>
        </div>
      </form>
    </ejs-dialog>
  </section>
</template>

<style scoped>
.topo { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.titulo { margin: 0; font-size: 20px; color: var(--kordis-navy-900); }
.erro { color: var(--kordis-danger-text); }

.layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start; }

/* Pacientes */
.pacientes { display: flex; flex-direction: column; gap: 10px; }
.busca {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 10px;
  border: 1px solid var(--kordis-border);
  border-radius: 6px;
  color: var(--kordis-text-secondary);
}
.busca input { flex: 1; padding: 9px 0; font: inherit; font-size: 14px; border: 0; outline: none; background: transparent; color: var(--kordis-text-primary); }
.lista { display: flex; flex-direction: column; gap: 6px; max-height: 560px; margin: 0; padding: 0; overflow-y: auto; list-style: none; }
.paciente {
  display: flex;
  flex-direction: column;
  gap: 2px;
  width: 100%;
  padding: 10px 12px;
  font: inherit;
  text-align: left;
  color: var(--kordis-text-primary);
  background: var(--kordis-bg-page);
  border: 1px solid var(--kordis-border);
  border-left: 4px solid transparent;
  border-radius: 6px;
  cursor: pointer;
}
.paciente span { font-size: 12px; color: var(--kordis-text-secondary); }
.paciente:hover { background: var(--kordis-bg-sidebar); }
.paciente.ativo { background: var(--kordis-status-agendado-bg); border-left-color: var(--kordis-navy-800); }
.vazio-lista { margin: 8px 0; font-size: 13px; color: var(--kordis-text-secondary); }

/* Histórico */
.vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 64px 16px;
  text-align: center;
  color: var(--kordis-text-secondary);
  border: 1px dashed var(--kordis-border);
  border-radius: 8px;
}
.vazio h2 { margin: 0; font-size: 18px; color: var(--kordis-navy-900); }
.vazio p { margin: 0; }
.vazio-icone { font-size: 44px; color: var(--kordis-teal-500); }

.ficha {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 14px;
  margin-bottom: 14px;
  border-bottom: 1px solid var(--kordis-border);
}
.ficha-nome { margin: 0; font-size: 20px; color: var(--kordis-navy-900); }
.ficha-dados { margin: 2px 0 0; font-size: 13px; color: var(--kordis-text-secondary); }
.ficha .material-icons { margin-right: 4px; }
.alergia {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin: 8px 0 0;
  padding: 3px 10px;
  font-size: 12px;
  font-weight: 600;
  color: var(--kordis-danger-text);
  background: color-mix(in srgb, var(--kordis-task-urgente) 12%, var(--kordis-bg-page));
  border-radius: 999px;
}
.alergia .material-icons { font-size: 16px; margin: 0; }

.filtros { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.filtro {
  padding: 5px 12px;
  font: inherit;
  font-size: 13px;
  color: var(--kordis-text-secondary);
  background: var(--kordis-bg-page);
  border: 1px solid var(--kordis-border);
  border-radius: 999px;
  cursor: pointer;
}
.filtro.ativo { color: var(--kordis-text-on-navy); background: var(--kordis-navy-800); border-color: var(--kordis-navy-800); }

.linha-tempo { position: relative; display: flex; flex-direction: column; gap: 14px; margin: 0; padding: 0; list-style: none; }
.linha-tempo::before { content: ''; position: absolute; top: 8px; bottom: 8px; left: 17px; width: 2px; background: var(--kordis-border); }
.registro { position: relative; display: flex; gap: 14px; }
.marcador {
  z-index: 1;
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  color: var(--kordis-text-on-navy);
  background: var(--kordis-navy-800);
  border: 3px solid var(--kordis-bg-page);
  border-radius: 50%;
}
.marcador .material-icons { font-size: 18px; }
.registro-corpo { flex: 1; padding: 12px 14px; border: 1px solid var(--kordis-border); border-radius: 8px; }
.registro-topo { display: flex; justify-content: space-between; gap: 12px; font-size: 12px; color: var(--kordis-text-secondary); }
.registro-tipo { font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--kordis-teal-500); }
.registro-titulo { margin: 4px 0; font-size: 15px; color: var(--kordis-navy-900); }
.registro-descricao { margin: 0; font-size: 14px; white-space: pre-wrap; color: var(--kordis-text-primary); }
.registro-autor { margin-top: 8px; font-size: 12px; color: var(--kordis-text-secondary); }

/* Modal */
.formulario { display: flex; flex-direction: column; gap: 12px; }
.paciente-modal { margin: 0; font-size: 13px; color: var(--kordis-text-secondary); }
.campo { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: var(--kordis-text-secondary); }
.campo-erro { color: var(--kordis-danger-text); font-size: 12px; }
.acoes { display: flex; justify-content: flex-end; gap: 8px; }
</style>
