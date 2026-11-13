import { ref, computed, onMounted } from 'vue'
import cadastroService from '../services/cadastroService'

function extrairErros(e, padrao) {
  const erros = {}
  for (const [campo, msgs] of Object.entries(e.response?.data?.errors ?? {})) {
    erros[campo] = msgs[0]
  }
  if (!Object.keys(erros).length) erros.geral = e.response?.data?.message ?? padrao
  return erros
}

export function useCadastroPresenter() {
  const definicoes = ref([])
  const chaveAtual = ref(null)
  const linhas = ref([])
  const opcoesFk = ref({}) // { recurso: [{ Id, Nome }] }
  const carregando = ref(false)
  const erro = ref(null)

  const atual = computed(() => definicoes.value.find((d) => d.chave === chaveAtual.value) ?? null)

  async function iniciar() {
    carregando.value = true
    try {
      definicoes.value = await cadastroService.definicoes()
      if (definicoes.value.length) await selecionar(definicoes.value[0].chave)
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar os cadastros.'
    } finally {
      carregando.value = false
    }
  }

  async function selecionar(chave) {
    chaveAtual.value = chave
    linhas.value = []
    erro.value = null
    carregando.value = true
    try {
      linhas.value = await cadastroService.listar(chave)
    } catch (e) {
      erro.value = e.response?.data?.message ?? 'Não foi possível carregar os registros.'
    } finally {
      carregando.value = false
    }
  }

  // Opções dos combobox de FK: só ativos, mais o valor que o registro em edição já tem.
  async function carregarOpcoes(registro = null) {
    const camposFk = atual.value?.campos.filter((c) => c.tipo === 'fk') ?? []
    const pares = await Promise.all(
      camposFk.map(async (c) => [c.nome, await cadastroService.opcoes(c.recurso, registro?.[c.nome] ?? null)])
    )
    opcoesFk.value = Object.fromEntries(pares)
  }

  // Retorna { ok: true } ou { ok: false, erros: { campo: 'mensagem' } }.
  async function salvar(dados, id = null) {
    try {
      const salvo = id
        ? await cadastroService.atualizar(chaveAtual.value, id, dados)
        : await cadastroService.criar(chaveAtual.value, dados)
      await selecionar(chaveAtual.value)
      return { ok: true, registro: salvo }
    } catch (e) {
      return { ok: false, erros: extrairErros(e, 'Não foi possível salvar o registro.') }
    }
  }

  async function excluir(id) {
    try {
      await cadastroService.excluir(chaveAtual.value, id)
      linhas.value = linhas.value.filter((l) => l.Id !== id)
      return { ok: true }
    } catch (e) {
      return { ok: false, erros: extrairErros(e, 'Não foi possível excluir o registro.') }
    }
  }

  onMounted(iniciar)

  return { definicoes, atual, chaveAtual, linhas, opcoesFk, carregando, erro, selecionar, carregarOpcoes, salvar, excluir }
}
