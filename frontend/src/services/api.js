import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: { 
    'Content-Type': 'application/json', 
    'Accept': 'application/json' 
  },
  timeout: 30000, // 30 segundos de timeout
})

// Inyecta el token en cada request si existe
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  
  return config
}, (error) => {
  console.error('Request interceptor error:', error)
  return Promise.reject(error)
})

// Interceptor de respuesta
api.interceptors.response.use(
  (response) => {
    // ✅ Log de respuestas exitosas (solo en desarrollo)
    if (import.meta.env.DEV) {
      console.debug(`API Response [${response.config.method?.toUpperCase()}] ${response.config.url}:`, response.status)
    }
    return response
  },
  (error) => {
    // Manejo de errores
    if (error.response?.status === 401) {
      // Token expirado o inválido
      console.warn('Token inválido o expirado, limpiando sesión...')
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      
      // Redirigir solo si no está ya en login
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login'
      }
    }
    
    // Error 403 (No autorizado)
    if (error.response?.status === 403) {
      console.error('No tienes permisos para esta acción')
      // Puedes mostrar un toast o notificación aquí
    }
    
    // Error 422 (Validación)
    if (error.response?.status === 422) {
      console.warn('Error de validación:', error.response.data.errors)
    }
    
    // Error 500 (Servidor)
    if (error.response?.status === 500) {
      console.error('Error del servidor:', error.response.data)
    }
    
    
    return Promise.reject(error)
  }
)

export default api