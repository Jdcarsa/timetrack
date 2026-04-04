<template>
  <div class="login-bg">
    <div class="login-card card">

      <!-- Logo -->
      <div class="login-logo">
        <div class="logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22">
            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
          </svg>
        </div>
        <h1>TimeTrack</h1>
        <p class="text-muted">Control de horas de trabajo</p>
      </div>

      <div v-if="error" class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
          <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
        </svg>
        {{ error }}
      </div>

      <div class="form-group">
        <label class="form-label">Correo electrónico</label>
        <input v-model="email" type="email" class="form-input" placeholder="correo@empresa.com" @keyup.enter="handleLogin" />
      </div>

      <div class="form-group">
        <label class="form-label">Contraseña</label>
        <input v-model="password" type="password" class="form-input" placeholder="••••••••" @keyup.enter="handleLogin" />
      </div>

      <button class="btn btn-primary btn-lg" style="width:100%; justify-content:center" :disabled="loading" @click="handleLogin">
        <svg v-if="!loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
          <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 0 0 6 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5V15a.75.75 0 0 1 1.5 0v3.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V5.25a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3V9A.75.75 0 0 1 15 9V5.25a1.5 1.5 0 0 0-1.5-1.5h-6Zm10.72 4.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9a.75.75 0 0 1 0-1.5h10.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
        {{ loading ? 'Ingresando...' : 'Ingresar' }}
      </button>

      <div class="demo-hint">
        <p>Usuarios de prueba:</p>
        <code>admin@timetrack.com / password</code>
        <code>juan@timetrack.com / password</code>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router   = useRouter()
const auth     = useAuthStore()
const email    = ref('')
const password = ref('')
const loading  = ref(false)
const error    = ref('')

async function handleLogin() {
  error.value = ''
  if (!email.value || !password.value) { error.value = 'Por favor completa todos los campos.'; return }
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Credenciales incorrectas.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-bg {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #F4F4F5;
  padding: 16px;
}
.login-card { width: 100%; max-width: 400px; }
.login-logo { text-align: center; margin-bottom: 28px; }
.logo-icon {
  width: 52px; height: 52px;
  background: #18181B;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  margin-bottom: 12px;
}
.login-logo h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }

.demo-hint {
  margin-top: 20px;
  padding: 12px;
  background: var(--bg);
  border-radius: 8px;
  border: 1px solid var(--border);
  text-align: center;
}
.demo-hint p  { font-size: 11px; color: var(--text-muted); margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.demo-hint code {
  display: block;
  font-size: 12px;
  color: var(--text);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 5px;
  padding: 4px 8px;
  margin-top: 4px;
  font-family: monospace;
}
</style>
