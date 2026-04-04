import { ref, computed } from 'vue'
import api from '@/services/api'

// ── Helpers de fecha ────────────────────────────────────────────
export function getMonday(date) {
    const d = new Date(date)
    const day = d.getDay()
    const diff = day === 0 ? -6 : 1 - day
    d.setDate(d.getDate() + diff)
    d.setHours(0, 0, 0, 0)
    return d
}

export function addDays(date, days) {
    const d = new Date(date)
    d.setDate(d.getDate() + days)
    return d
}

export function toYMD(date) {
    return date.toISOString().split('T')[0]
}

export function emptyForm(date = '') {
    return {
        title: '', description: '', date,
        start_time: '09:00', end_time: '10:00',
        is_recurring: false, recur_day: 0,
    }
}

// ── Composable principal ────────────────────────────────────────
export function usePlanificacion() {

    const tasks = ref([])
    const loading = ref(true)
    const saving = ref(false)
    const showModal = ref(false)
    const editingTask = ref(null)
    const formError = ref('')
    const errors = ref({})
    const currentWeekStart = ref(getMonday(new Date()))
    const form = ref(emptyForm())

    // ── Computed ──────────────────────────────────────────────────
    const weekLabel = computed(() => {
        const start = currentWeekStart.value
        const end = addDays(start, 6)
        const fmt = d => d.toLocaleDateString('es-CO', { day: 'numeric', month: 'long' })
        return `${fmt(start)} — ${fmt(end)}`
    })

    const isCurrentWeek = computed(() =>
        toYMD(currentWeekStart.value) === toYMD(getMonday(new Date()))
    )

    const weekDays = computed(() => {
        const dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']
        const today = toYMD(new Date())

        return Array.from({ length: 7 }, (_, i) => {
            const date = addDays(currentWeekStart.value, i)
            const dateStr = toYMD(date)
            return {
                date: dateStr,
                name: dayNames[i],
                label: date.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' }),
                isToday: dateStr === today,
                tasks: tasks.value
                    .filter(t => t.date === dateStr)
                    .sort((a, b) => a.start_time.localeCompare(b.start_time)),
            }
        })
    })

    // ── API ───────────────────────────────────────────────────────
    async function fetchTasks() {
        loading.value = true
        try {
            const { data } = await api.get('/tasks', {
                params: { week: toYMD(currentWeekStart.value) }
            })
            tasks.value = data.tasks
        } finally {
            loading.value = false
        }
    }

    async function saveTask() {
        saving.value = true
        formError.value = ''
        errors.value = {}
        try {
            if (editingTask.value) {
                const { data } = await api.put(`/tasks/${editingTask.value.id}`, form.value)
                const idx = tasks.value.findIndex(t => t.id === editingTask.value.id)
                if (idx !== -1) tasks.value[idx] = data.task
            } else {
                const { data } = await api.post('/tasks', form.value)
                tasks.value.push(data.task)
            }
            closeForm()
        } catch (e) {
            const errs = e.response?.data?.errors
            if (errs) {
                errors.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, v[0]]))
            } else {
                formError.value = e.response?.data?.message || 'Error al guardar.'
            }
        } finally {
            saving.value = false
        }
    }

    async function changeStatus(task, newStatus) {
        try {
            const { data } = await api.patch(`/tasks/${task.id}/status`, { status: newStatus })
            const idx = tasks.value.findIndex(t => t.id === task.id)
            if (idx !== -1) tasks.value[idx] = data.task
        } catch {
            alert('Error al cambiar el estado.')
        }
    }

    async function deleteTask(task) {
        if (!confirm(`¿Eliminar "${task.title}"?`)) return
        try {
            await api.delete(`/tasks/${task.id}`)
            tasks.value = tasks.value.filter(t => t.id !== task.id)
        } catch {
            alert('Error al eliminar la tarea.')
        }
    }

    // ── Navegación ────────────────────────────────────────────────
    function changeWeek(direction) {
        currentWeekStart.value = addDays(currentWeekStart.value, direction * 7)
        fetchTasks()
    }

    // ── Modal ─────────────────────────────────────────────────────
    function openForm(date = '', task = null) {
        editingTask.value = task
        form.value = task ? { ...task } : emptyForm(date)
        errors.value = {}
        formError.value = ''
        showModal.value = true
    }

    function closeForm() {
        showModal.value = false
        editingTask.value = null
        form.value = emptyForm()
    }

    return {
        // Estado
        tasks, loading, saving, showModal,
        editingTask, formError, errors, form,
        // Computed
        weekLabel, isCurrentWeek, weekDays,
        // Métodos
        fetchTasks, saveTask, changeStatus,
        deleteTask, changeWeek, openForm, closeForm,
    }
}