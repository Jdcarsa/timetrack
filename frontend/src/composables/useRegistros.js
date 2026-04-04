import { ref } from 'vue'
import api from '@/services/api'

export function useRegistros() {

    const records = ref([])
    const employees = ref([])
    const loading = ref(false)
    const page = ref(1)
    const lastPage = ref(1)
    const from = ref('')
    const to = ref('')
    const userId = ref('')

    async function fetchRecords(p = 1) {
        loading.value = true
        try {
            const { data } = await api.get('/admin/records', {
                params: { page: p, from: from.value, to: to.value, user_id: userId.value || undefined }
            })
            records.value = data.data
            page.value = data.current_page
            lastPage.value = data.last_page
        } finally {
            loading.value = false
        }
    }

    async function fetchEmployees() {
        const { data } = await api.get('/admin/employees')
        employees.value = data
    }

    const formatTime = (dt) => new Date(dt).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })
    const formatDate = (dt) => new Date(dt).toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric' })
    const formatMoney = (v) => Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0 })

    return {
        // Estado
        records, employees, loading, page, lastPage,
        from, to, userId,
        // Métodos
        fetchRecords, fetchEmployees,
        // Helpers
        formatTime, formatDate, formatMoney,
    }
}