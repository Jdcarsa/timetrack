import { ref } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

export function useRegistros() {

    const auth = useAuthStore()
    const records = ref([])
    const employees = ref([])
    const loading = ref(false)
    const page = ref(1)
    const lastPage = ref(1)
    const from = ref('')
    const to = ref('')
    const userId = ref('')

    // Obtener zona horaria del usuario almacenada
    const getUserTimezone = () => {
        const user = auth.user
        if (user && user.timezone) {
            return user.timezone
        }
        // Si no hay, usar la del navegador
        return Intl.DateTimeFormat().resolvedOptions().timeZone
    }

    async function fetchRecords(p = 1) {
        loading.value = true
        try {
            const { data } = await api.get('/admin/records', {
                params: { 
                    page: p, 
                    from: from.value, 
                    to: to.value, 
                    user_id: userId.value || undefined 
                }
            })
            
            // Procesar los registros para asegurar que los datos sean válidos
            records.value = data.data.map(record => ({
                ...record,
                // Asegurar que total_hours nunca sea negativo
                total_hours: record.total_hours < 0 ? null : record.total_hours,
                // Marcar si tiene error (horas negativas)
                has_error: record.total_hours < 0,
                // Calcular total correctamente (evitar negativos)
                calculated_total: record.clock_out && record.total_hours > 0 
                    ? record.total_hours * record.hourly_rate_snapshot 
                    : null
            }))
            
            page.value = data.current_page
            lastPage.value = data.last_page
        } catch (error) {
            console.error('Error fetching records:', error)
            records.value = []
        } finally {
            loading.value = false
        }
    }

    async function fetchEmployees() {
        try {
            const { data } = await api.get('/admin/employees')
            employees.value = data
        } catch (error) {
            console.error('Error fetching employees:', error)
            employees.value = []
        }
    }

    // Formatear hora con zona horaria del usuario
    const formatTime = (dt) => {
        if (!dt) return '—'
        try {
            const date = new Date(dt)
            const timezone = getUserTimezone()
            return date.toLocaleTimeString('es-CO', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true,
                timeZone: timezone
            })
        } catch (error) {
            return '—'
        }
    }
    
    // Formatear fecha con zona horaria del usuario
    const formatDate = (dt) => {
        if (!dt) return '—'
        try {
            const date = new Date(dt)
            const timezone = getUserTimezone()
            return date.toLocaleDateString('es-CO', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                timeZone: timezone
            })
        } catch (error) {
            return '—'
        }
    }
    
    // Formatear dinero
    const formatMoney = (v) => {
        if (v === null || v === undefined) return '0'
        return Number(v).toLocaleString('es-CO', { 
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        })
    }
    
    // Formatear horas (mostrar horas y minutos)
    const formatHours = (hours) => {
        if (!hours || hours < 0) return '—'
        const h = Math.floor(hours)
        const m = Math.round((hours - h) * 60)
        if (h === 0 && m > 0) return `${m} min`
        if (m === 0) return `${h}h`
        return `${h}h ${m}min`
    }
    
    // Obtener clase CSS según el estado de horas
    const getHoursClass = (hours) => {
        if (!hours) return ''
        if (hours < 0) return 'text-danger'
        if (hours === 0) return 'text-warning'
        return 'text-success'
    }
    
    // Obtener clase CSS para el total
    const getTotalClass = (record) => {
        if (!record.clock_out) return ''
        if (record.total_hours < 0) return 'text-danger'
        return ''
    }
    
    // Validar si un registro es consistente
    const isConsistent = (record) => {
        if (!record.clock_out) return true
        return record.total_hours > 0 && record.total_hours < 24
    }
    
    // Resetear filtros
    const resetFilters = () => {
        from.value = ''
        to.value = ''
        userId.value = ''
        fetchRecords(1)
    }
    

    return {
        // Estado
        records, 
        employees, 
        loading, 
        page, 
        lastPage,
        from, 
        to, 
        userId,
        // Métodos
        fetchRecords, 
        fetchEmployees,
        resetFilters,
        exportToCSV,
        // Helpers de formato
        formatTime, 
        formatDate, 
        formatMoney,
        formatHours,
        // Helpers de estilo
        getHoursClass,
        getTotalClass,
        isConsistent,
        // Utilidad
        getUserTimezone
    }
}