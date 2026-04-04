import { ref } from 'vue'
import api from '@/services/api'

export function useEmpleados() {

    const employees = ref([])
    const loading = ref(true)
    const showForm = ref(false)
    const saving = ref(false)
    const editing = ref(null)
    const editRate = ref(0)
    const savedMessage = ref(null)
    const rateError = ref('')
    const createError = ref('')
    const createSuccess = ref('')
    const formErrors = ref({})
    const form = ref(emptyForm())

    function emptyForm() {
        return { name: '', email: '', password: '', hourly_rate: '' }
    }

    const formatMoney = (v) => Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0 })

    // ── API ───────────────────────────────────────────────────────
    async function fetchEmployees() {
        loading.value = true
        try {
            const { data } = await api.get('/admin/employees')
            employees.value = data
        } finally {
            loading.value = false
        }
    }

    async function createEmployee() {
        formErrors.value = {}
        createError.value = ''
        createSuccess.value = ''
        saving.value = true
        try {
            const { data } = await api.post('/admin/employees', form.value)
            createSuccess.value = `✓ ${data.user.name} creado correctamente.`
            employees.value.push(data.user)
            resetForm()
            setTimeout(() => { showForm.value = false; createSuccess.value = '' }, 1500)
        } catch (e) {
            const errors = e.response?.data?.errors
            if (errors) {
                formErrors.value = Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]))
            } else {
                createError.value = e.response?.data?.message || 'Error al crear el empleado.'
            }
        } finally {
            saving.value = false
        }
    }

    async function saveRate(emp) {
        saving.value = true
        try {
            await api.put(`/admin/employees/${emp.id}/hourly-rate`, { hourly_rate: editRate.value })
            emp.hourly_rate = editRate.value
            editing.value = null
            savedMessage.value = emp.id
            setTimeout(() => { savedMessage.value = null }, 2000)
        } catch (e) {
            rateError.value = e.response?.data?.message || 'Error al actualizar la tarifa.'
        } finally {
            saving.value = false
        }
    }

    // ── Helpers ───────────────────────────────────────────────────
    function resetForm() {
        form.value = emptyForm()
        formErrors.value = {}
        createError.value = ''
        createSuccess.value = ''
    }

    function startEdit(emp) {
        editing.value = emp.id
        editRate.value = emp.hourly_rate
        rateError.value = ''
    }

    return {
        // Estado
        employees, loading, showForm, saving,
        editing, editRate, savedMessage,
        rateError, createError, createSuccess,
        formErrors, form,
        // Métodos
        fetchEmployees, createEmployee,
        saveRate, resetForm, startEdit,
        formatMoney,
    }
}