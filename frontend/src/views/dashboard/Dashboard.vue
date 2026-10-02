<script setup>
import { ref } from 'vue'
import {
  KanbanComponent as EjsKanban,
  ColumnsDirective as EColumns,
  ColumnDirective as EColumn,
} from '@syncfusion/ej2-vue-kanban'
import { useNursingTaskPresenter } from '../../presenters/useNursingTaskPresenter'

const { tarefas, carregando, erro, moverTarefa } = useNursingTaskPresenter()

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
</script>

<template>
  <section class="dashboard">
    <h1 class="titulo">Fluxo de Atividades da Enfermagem</h1>

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
        <e-column headerText="A Fazer" keyField="a_fazer" />
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
          </div>
          <span v-if="data.Concluido === true" class="tarefa-ok" title="Atividade concluída">OK</span>
        </div>
      </template>
    </ejs-kanban>
  </section>
</template>

<style scoped>
.titulo {
  margin: 0 0 16px;
  font-size: 20px;
  color: var(--kordis-navy-900);
}

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

.tarefa-ok {
  padding: 2px 10px;
  font-size: 12px;
  font-weight: 700;
  border-radius: 999px;
  color: var(--kordis-text-on-navy);
  background: var(--kordis-task-concluido);
}
</style>
