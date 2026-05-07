import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

export function useCronograma(projectId) {
    const auth = useAuthStore()
    const router = useRouter()
    const project = ref(null)
    const schedules = ref([])
    const loading = ref(true)
    const saving = ref(false)
    const showScheduleModal = ref(false)
    const showTaskModal = ref(false)
    const showCreateTask = ref(false)
    const editingSchedule = ref(null)
    const activeSchedule = ref(null)
    const availableTasks = ref([])
    const formErrors = ref({})
    const formError = ref('')
    const taskFormErrors = ref({})
    const taskFormError = ref('')

    const scheduleForm = ref(emptyScheduleForm())
    const taskForm = ref(emptyTaskForm())

    function emptyScheduleForm() {
        return { title: '', description: '', week_start: '' }
    }

    function emptyTaskForm() {
        return {
            title: '', description: '',
            date: '', start_time: '09:00', end_time: '10:00',
            is_recurring: false, recur_day: 0,
            user_id: '',
        }
    }

    const DAY_NAMES = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo']

    function getMonday(dateStr) {
        const d = new Date(dateStr)
        const day = d.getDay()
        const diff = day === 0 ? -6 : 1 - day
        d.setDate(d.getDate() + diff)
        return d
    }

    function addDays(date, n) {
        const d = new Date(date)
        d.setDate(d.getDate() + n)
        return d
    }

    function toYMD(date) {
        return date.toISOString().split('T')[0]
    }

    function weekDays(weekStart) {
        const monday = getMonday(weekStart)
        return Array.from({ length: 7 }, (_, i) => {
            const date = addDays(monday, i)
            return {
                name: DAY_NAMES[i],
                date: toYMD(date),
                label: date.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' }),
            }
        })
    }

    function tasksForDay(schedule, dateStr) {
        return (schedule.tasks || [])
            .filter(t => t.date === dateStr)
            .sort((a, b) => a.start_time.localeCompare(b.start_time))
    }

    const formatDate = (d) => d
        ? new Date(d).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' })
        : '—'

    const formatWeek = (weekStart) => {
        const monday = getMonday(weekStart)
        const sunday = addDays(monday, 6)
        const fmt = d => d.toLocaleDateString('es-CO', { day: 'numeric', month: 'long' })
        return `${fmt(monday)} - ${fmt(sunday)}`
    }

    const statusBadge = (status) => ({
        in_progress: 'badge badge-blue',
        completed: 'badge badge-green',
    })[status] ?? 'badge badge-gray'

    const statusLabel = (status) => ({
        in_progress: 'En progreso',
        completed: 'Completado',
    })[status] ?? status

    async function fetchProject() {
        loading.value = true
        try {
            const { data } = await api.get(`/projects/${projectId}`)
            project.value = data
            schedules.value = data.schedules ?? []
        } catch {
            router.push('/proyectos')
        } finally {
            loading.value = false
        }
    }

    function openCreateSchedule() {
        editingSchedule.value = null
        scheduleForm.value = emptyScheduleForm()
        formErrors.value = {}
        formError.value = ''
        showScheduleModal.value = true
    }

    function openEditSchedule(schedule) {
        editingSchedule.value = schedule
        scheduleForm.value = {
            title: schedule.title,
            description: schedule.description ?? '',
            week_start: schedule.week_start,
        }
        formErrors.value = {}
        formError.value = ''
        showScheduleModal.value = true
    }

    function closeScheduleModal() {
        showScheduleModal.value = false
        editingSchedule.value = null
        scheduleForm.value = emptyScheduleForm()
    }

    async function saveSchedule() {
        formErrors.value = {}
        formError.value = ''
        saving.value = true
        try {
            if (editingSchedule.value) {
                const { data } = await api.put(
                    `/projects/${projectId}/schedules/${editingSchedule.value.id}`,
                    scheduleForm.value
                )
                const idx = schedules.value.findIndex(s => s.id === editingSchedule.value.id)
                if (idx !== -1) schedules.value[idx] = data.schedule
            } else {
                const { data } = await api.post(`/projects/${projectId}/schedules`, scheduleForm.value)
                schedules.value.push(data.schedule)
            }
            closeScheduleModal()
        } catch (e) {
            const errs = e.response?.data?.errors
            if (errs) formErrors.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, v[0]]))
            else formError.value = e.response?.data?.message || 'Error al guardar.'
        } finally {
            saving.value = false
        }
    }

    async function deleteSchedule(schedule) {
        if (!confirm(`Eliminar cronograma "${schedule.title}"?`)) return
        try {
            await api.delete(`/projects/${projectId}/schedules/${schedule.id}`)
            schedules.value = schedules.value.filter(s => s.id !== schedule.id)
        } catch {
            alert('Error al eliminar el cronograma.')
        }
    }

    async function openAddTask(schedule) {
        activeSchedule.value = schedule
        availableTasks.value = []
        showTaskModal.value = true
        try {
            const { data } = await api.get(
                `/projects/${projectId}/schedules/${schedule.id}/available-tasks`
            )
            availableTasks.value = data
        } catch {
            alert('Error al cargar las tareas disponibles.')
        }
    }

    function closeTaskModal() {
        showTaskModal.value = false
        activeSchedule.value = null
        availableTasks.value = []
    }

    async function addTask(task) {
        try {
            const { data } = await api.post(
                `/projects/${projectId}/schedules/${activeSchedule.value.id}/tasks`,
                { task_id: task.id }
            )
            const idx = schedules.value.findIndex(s => s.id === activeSchedule.value.id)
            if (idx !== -1) schedules.value[idx] = data.schedule
            availableTasks.value = availableTasks.value.filter(t => t.id !== task.id)
        } catch (e) {
            alert(e.response?.data?.message || 'Error al agregar la tarea.')
        }
    }

    async function removeTask(schedule, task) {
        if (!confirm(`Quitar "${task.title}" del cronograma?`)) return
        try {
            await api.delete(`/projects/${projectId}/schedules/${schedule.id}/tasks/${task.id}`)
            const idx = schedules.value.findIndex(s => s.id === schedule.id)
            if (idx !== -1) {
                schedules.value[idx].tasks = schedules.value[idx].tasks.filter(t => t.id !== task.id)
            }
        } catch {
            alert('Error al quitar la tarea.')
        }
    }

    function openCreateTask(schedule, selectedDate = '') {
        activeSchedule.value = schedule
        taskForm.value = emptyTaskForm()
        const weekStart = schedule?.week_start ? toYMD(getMonday(schedule.week_start)) : ''
        taskForm.value.date = selectedDate || weekStart
        taskFormErrors.value = {}
        taskFormError.value = ''
        showCreateTask.value = true
    }

    function closeCreateTask() {
        showCreateTask.value = false
        activeSchedule.value = null
        taskForm.value = emptyTaskForm()
    }

    async function saveNewTask() {
        taskFormErrors.value = {}
        taskFormError.value = ''
        saving.value = true
        try {
            const payload = {
                ...taskForm.value,
                schedule_id: activeSchedule.value.id,
                user_id: taskForm.value.user_id || undefined,
            }
            const { data } = await api.post('/tasks', payload)

            const idx = schedules.value.findIndex(s => s.id === activeSchedule.value.id)
            if (idx !== -1) {
                if (!schedules.value[idx].tasks) schedules.value[idx].tasks = []
                schedules.value[idx].tasks.push(data.task)
            }
            closeCreateTask()
        } catch (e) {
            const errs = e.response?.data?.errors
            if (errs) taskFormErrors.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, v[0]]))
            else taskFormError.value = e.response?.data?.message || 'Error al crear la tarea.'
        } finally {
            saving.value = false
        }
    }

    return {
        auth, project, schedules, loading, saving,
        showScheduleModal, showTaskModal, showCreateTask,
        editingSchedule, activeSchedule,
        availableTasks, scheduleForm, taskForm,
        formErrors, formError, taskFormErrors, taskFormError,
        weekDays, tasksForDay,
        formatDate, formatWeek,
        statusBadge, statusLabel,
        fetchProject,
        openCreateSchedule, openEditSchedule, closeScheduleModal, saveSchedule, deleteSchedule,
        openAddTask, closeTaskModal, addTask, removeTask,
        openCreateTask, closeCreateTask, saveNewTask,
    }
}
