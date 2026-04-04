import { ref, computed } from 'vue'
import api from '@/services/api'

export function useExport() {

    const from = ref('')
    const to = ref('')
    const userId = ref('')
    const employees = ref([])
    const summary = ref([])
    const loading = ref(false)
    const exporting = ref(false)
    const error = ref('')
    const success = ref('')

    const totalRecords = computed(() => summary.value.reduce((s, r) => s + r.total_records, 0))
    const totalHours = computed(() => summary.value.reduce((s, r) => s + r.total_hours, 0).toFixed(2))
    const totalEarnings = computed(() => summary.value.reduce((s, r) => s + r.total_earnings, 0))

    const formatMoney = (v) => Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0 })

    async function fetchEmployees() {
        const { data } = await api.get('/admin/employees')
        employees.value = data
    }

    async function previewSummary() {
        error.value = ''
        success.value = ''
        loading.value = true
        summary.value = []
        try {
            const { data } = await api.get('/admin/summary', {
                params: { from: from.value, to: to.value, user_id: userId.value || undefined }
            })
            summary.value = data
            if (data.length === 0) error.value = 'No hay registros completados en ese rango de fechas.'
        } catch (e) {
            error.value = e.response?.data?.message || 'Error al obtener el resumen.'
        } finally {
            loading.value = false
        }
    }

    async function downloadExcel() {
        error.value = ''
        success.value = ''
        exporting.value = true
        try {
            const response = await api.get('/admin/export', {
                params: { from: from.value, to: to.value, user_id: userId.value || undefined },
                responseType: 'blob',
            })
            const url = window.URL.createObjectURL(new Blob([response.data]))
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', `timetrack_${from.value}_${to.value}.xlsx`)
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
            success.value = 'Archivo descargado correctamente.'
        } catch {
            error.value = 'Error al generar el archivo. Verifica el rango de fechas.'
        } finally {
            exporting.value = false
        }
    }

    return {
        // Estado
        from, to, userId, employees, summary,
        loading, exporting, error, success,
        // Computed
        totalRecords, totalHours, totalEarnings,
        // Métodos
        fetchEmployees, previewSummary, downloadExcel,
        formatMoney,
    }
}