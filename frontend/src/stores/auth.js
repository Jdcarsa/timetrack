import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  // Estado reactivo
  const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref(localStorage.getItem('token') || null)

  // Computadas
  const isLoggedIn = computed(() => !!token.value)
  const isAdmin    = computed(() => user.value?.role === 'admin')

  // ✅ Obtener zona horaria del navegador
  const getUserTimezone = () => {
    try {
      return Intl.DateTimeFormat().resolvedOptions().timeZone
    } catch (error) {
      console.error('Error obteniendo zona horaria:', error)
      return 'America/Bogota' // Valor por defecto
    }
  }

  // Acciones
  async function login(email, password) {
    // ✅ Enviar la zona horaria al backend
    const timezone = getUserTimezone()
    
    const { data } = await api.post('/login', { 
      email, 
      password,
      timezone: timezone  // ← Envía la zona horaria
    })
    
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
  }

  async function logout() {
    try { 
      await api.post('/logout') 
    } catch (error) {
      console.error('Error en logout:', error)
    } finally {
      token.value = null
      user.value  = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  // ✅ Opcional: Actualizar zona horaria del usuario (si cambia de país)
  async function updateUserTimezone() {
    if (!user.value) return
    
    const timezone = getUserTimezone()
    try {
      // Si tienes un endpoint para actualizar zona horaria
      // await api.put('/users/timezone', { timezone })
      user.value.timezone = timezone
      localStorage.setItem('user', JSON.stringify(user.value))
    } catch (error) {
      console.error('Error actualizando zona horaria:', error)
    }
  }

  return { 
    user, 
    token, 
    isLoggedIn, 
    isAdmin, 
    login, 
    logout,
    getUserTimezone,
    updateUserTimezone
  }
})