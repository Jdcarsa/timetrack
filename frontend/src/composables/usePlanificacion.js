import { ref, computed } from 'vue'
import api from '@/services/api'

export function getMonday(date) {
    const d = new Date(date)
    const day = d.getDay()
    const diff = day === 0 ? -6 : 1 - day
    d.setDate(d.getDate() + diff)
    d.setHours(0, 0, 0, 0)
    return d
}

export function getDayName(date) {
    const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
    return days[date.getDay()]
}

export function addDays(date, days) {
    const d = new Date(date)
    d.setDate(d.getDate() + days)
    return d
}

export function toYMD(date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

export function emptyForm(date = '') {
    return {
        title: '', description: '', date,
        start_time: '09:00', end_time: '10:00',
        is_recurring: false, recur_day: 0,
    }
}

export function usePlanificacion() {

    const tasks = ref([])
    const loading = ref(true)
    const saving = ref(false)
    const showModal = ref(false)
    const editingTask = ref(null)
    const formError = ref('')
    const errors = ref({})
    const scheduleLoading = ref(false)
    const scheduleSaving = ref(false)
    const scheduleError = ref('')
    const scheduleSuccess = ref('')
    const currentWeekStart = ref(getMonday(new Date()))
    const form = ref(emptyForm())
    const scheduleDays = ref(defaultScheduleDays())

    function defaultScheduleDays() {
        return [
            { day_of_week: 0, name: 'Lunes', is_working: true, start_time: '08:00', end_time: '17:00', break_minutes: 60 },
            { day_of_week: 1, name: 'Martes', is_working: true, start_time: '08:00', end_time: '17:00', break_minutes: 60 },
            { day_of_week: 2, name: 'Miércoles', is_working: true, start_time: '08:00', end_time: '17:00', break_minutes: 60 },
            { day_of_week: 3, name: 'Jueves', is_working: true, start_time: '08:00', end_time: '17:00', break_minutes: 60 },
            { day_of_week: 4, name: 'Viernes', is_working: true, start_time: '08:00', end_time: '17:00', break_minutes: 60 },
            { day_of_week: 5, name: 'Sábado', is_working: false, start_time: null, end_time: null, break_minutes: 0 },
            { day_of_week: 6, name: 'Domingo', is_working: false, start_time: null, end_time: null, break_minutes: 0 },
        ]
    }

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
        const today = toYMD(new Date())
        // Nombres de días en orden correcto (0=Lunes, 6=Domingo)
        const dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']

        return Array.from({ length: 7 }, (_, i) => {
            const date = addDays(currentWeekStart.value, i)
            const dateStr = toYMD(date)
            
            // Obtener el índice real del día (0=Lunes, 6=Domingo)
            const jsDayOfWeek = date.getDay() // 0=Domingo, 1=Lunes, ..., 6=Sábado
            let dayIndex
            if (jsDayOfWeek === 0) {
                dayIndex = 6 // Domingo
            } else {
                dayIndex = jsDayOfWeek - 1 // Lunes(1)→0, Martes(2)→1, etc.
            }
            
            return {
                date: dateStr,
                name: dayNames[dayIndex], // Nombre correcto según la fecha real
                label: date.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' }),
                isToday: dateStr === today,
                tasks: tasks.value
                    .filter(t => t.date === dateStr)
                    .sort((a, b) => a.start_time.localeCompare(b.start_time)),
            }
        })
    })

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

    async function fetchWorkSchedule() {
        scheduleLoading.value = true
        scheduleError.value = ''
        try {
            const { data } = await api.get('/work-schedule')
            const serverDays = data.days ?? []
            scheduleDays.value = defaultScheduleDays().map(base => {
                const current = serverDays.find(d => Number(d.day_of_week) === base.day_of_week)
                if (!current) return base
                return {
                    ...base,
                    is_working: !!current.is_working,
                    start_time: current.start_time,
                    end_time: current.end_time,
                    break_minutes: Number(current.break_minutes ?? 0),
                }
            })
        } catch {
            scheduleError.value = 'No se pudo cargar tu horario.'
        } finally {
            scheduleLoading.value = false
        }
    }

    async function saveWorkSchedule() {
        scheduleSaving.value = true
        scheduleError.value = ''
        scheduleSuccess.value = ''

        try {
            const payload = {
                days: scheduleDays.value.map(d => ({
                    day_of_week: d.day_of_week,
                    is_working: !!d.is_working,
                    start_time: d.is_working ? d.start_time : null,
                    end_time: d.is_working ? d.end_time : null,
                    break_minutes: d.is_working ? Number(d.break_minutes || 0) : 0,
                })),
            }

            if (import.meta.env.DEV) {
                console.debug('saveWorkSchedule payload', JSON.parse(JSON.stringify(payload)))
            }

            const { data } = await api.put('/work-schedule', payload)
            scheduleDays.value = data.days.map(d => ({
                ...d,
                break_minutes: Number(d.break_minutes ?? 0),
            }))
            scheduleSuccess.value = 'Horario guardado correctamente.'
        } catch (e) {
            scheduleError.value = e.response?.data?.message || 'Error al guardar el horario.'
        } finally {
            scheduleSaving.value = false
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
            fetchTasks() // Recargar tareas para asegurar consistencia
        } catch (e) {
            const errs = e.response?.data?.errors
            if (errs) errors.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, v[0]]))
            else formError.value = e.response?.data?.message || 'Error al guardar.'
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

    function changeWeek(direction) {
        currentWeekStart.value = addDays(currentWeekStart.value, direction * 7)
        fetchTasks()
    }

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

    // Función auxiliar para obtener el horario de un día específico
    function getWorkScheduleForDay(dateStr) {
        if (!dateStr || !scheduleDays.value.length) return null
        
        const [year, month, day] = dateStr.split('-').map(Number)
        const date = new Date(year, month - 1, day)
        const jsDayOfWeek = date.getDay() // 0=Domingo, 1=Lunes, ..., 6=Sábado
        
        // Convertir a índice del horario (0=Lunes, 6=Domingo)
        let scheduleIndex
        if (jsDayOfWeek === 0) {
            scheduleIndex = 6 // Domingo
        } else {
            scheduleIndex = jsDayOfWeek - 1 // Lunes(1)→0, Martes(2)→1, etc.
        }
        
        return scheduleDays.value.find(d => d.day_of_week === scheduleIndex)
    }

    // Verificar si una tarea está dentro del horario laboral
    function isTaskWithinWorkHours(task) {
        if (task.status === 'completed') return true
        
        const schedule = getWorkScheduleForDay(task.date)
        if (!schedule || !schedule.is_working) return false
        
        const taskStart = task.start_time
        const taskEnd = task.end_time
        const workStart = schedule.start_time
        const workEnd = schedule.end_time
        
        return taskStart >= workStart && taskEnd <= workEnd
    }

    // Obtener clase CSS de advertencia para tareas fuera de horario
    function getTaskWarningClass(task) {
        if (task.status === 'completed') return ''
        if (!isTaskWithinWorkHours(task)) return 'task-warning'
        return ''
    }

    // Obtener título de tooltip para tareas fuera de horario
    function getTaskWarningTitle(task) {
        if (task.status === 'completed') return ''
        if (!isTaskWithinWorkHours(task)) {
            const schedule = getWorkScheduleForDay(task.date)
            if (schedule && schedule.is_working) {
                return `⚠️ Fuera del horario laboral (${schedule.start_time} - ${schedule.end_time})`
            }
            return '⚠️ Día no laboral'
        }
        return ''
    }

    return {
        tasks, loading, saving, showModal,
        editingTask, formError, errors, form,
        scheduleLoading, scheduleSaving, scheduleError, scheduleSuccess, scheduleDays,
        weekLabel, isCurrentWeek, weekDays,
        fetchTasks, saveTask, changeStatus,
        deleteTask, changeWeek, openForm, closeForm,
        fetchWorkSchedule, saveWorkSchedule,
        getWorkScheduleForDay,
        isTaskWithinWorkHours,
        getTaskWarningClass,
        getTaskWarningTitle,
    }
}