<template>
  <div class="login">
    <form class="login__card" @submit.prevent="entrar">
      <img class="login__logo" src="../../assets/brand/kordis-logo.png" alt="Kordis" />
      <ejs-textbox v-model="email" placeholder="E-mail" floatLabelType="Auto" type="email" />
      <ejs-textbox v-model="senha" placeholder="Senha" floatLabelType="Auto" type="password" />
      <p v-if="erro" class="login__erro">{{ erro }}</p>
      <ejs-button cssClass="e-primary" isPrimary="true" type="submit" :disabled="carregando">
        {{ carregando ? 'Entrando...' : 'Entrar' }}
      </ejs-button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { TextBoxComponent as EjsTextbox } from '@syncfusion/ej2-vue-inputs'
import { ButtonComponent as EjsButton } from '@syncfusion/ej2-vue-buttons'
import { useAuthStore } from '../../stores/auth'

const email = ref('')
const senha = ref('')
const erro = ref(null)
const carregando = ref(false)

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

async function entrar() {
  carregando.value = true
  erro.value = null
  try {
    await authStore.login(email.value, senha.value)
    router.push(route.query.redirect ?? { name: 'dashboard' })
  } catch (e) {
    erro.value = 'E-mail ou senha inválidos.'
  } finally {
    carregando.value = false
  }
}
</script>

<style scoped>
.login {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  background: var(--kordis-bg-sidebar);
}
.login__card {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  width: 340px;
  padding: 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(20, 36, 91, 0.1);
}
.login__logo {
  height: 32px;
  object-fit: contain;
  margin: 0 auto 0.5rem;
}
.login__erro {
  color: var(--kordis-danger-text);
  font-size: 0.875rem;
}
</style>
