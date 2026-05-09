import { ref, computed } from 'vue'
import api from '@/services/api'

// Paleta de colores para distinguir empleados
const COLORS = [
    '#6366F1', '#16A34A', '#D97706', '#DC2626',
    '#0891B2', '#9333EA', '#EA580C', '#65A30D',
]

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

// ── Composable principal ────────────────────────────────────────
export function useCalendario() {

    const calendarDays = ref({})
    const employees = ref([])
    const loading = ref(true)
    const filterUserId = ref('')
    const currentWeekStart = ref(getMonday(new Date()))
    const employeeColors = ref({})

    // ── Helpers ───────────────────────────────────────────────────
    function getEmployeeColor(userId) {
        if (!userId) return '#A1A1AA'
        if (!employeeColors.value[userId]) {
            const idx = Object.keys(employeeColors.value).length % COLORS.length
            employeeColors.value[userId] = COLORS[idx]
        }
        return employeeColors.value[userId]
    }

    function statusBadge(status) {
        return {
            pending: 'badge badge-gray',
            in_progress: 'badge badge-blue',
            completed: 'badge badge-green',
        }[status] ?? 'badge badge-gray'
    }

    function toMinutes(time) {
        if (!time || typeof time !== 'string' || !time.includes(':')) return null
        const [h, m] = time.split(':').map(Number)
        if (Number.isNaN(h) || Number.isNaN(m)) return null
        return (h * 60) + m
    }

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

    // 7 días de la semana con sus tareas filtradas
    const weekDays = computed(() => {
        const dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']
        const today = toYMD(new Date())

        return Array.from({ length: 7 }, (_, i) => {
            const date = addDays(currentWeekStart.value, i)
            const dateStr = toYMD(date)
            const tasks = (calendarDays.value[dateStr] || [])
                .filter(t => !filterUserId.value || t.user?.id == filterUserId.value)
                .sort((a, b) => a.start_time.localeCompare(b.start_time))

            return {
                date: dateStr,
                name: dayNames[i],
                label: date.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' }),
                isToday: dateStr === today,
                tasks,
            }
        })
    })

    // Empleados visibles en la semana actual (para la leyenda)
    const visibleEmployees = computed(() => {
        const seen = new Map()
        Object.values(calendarDays.value).flat().forEach(task => {
            if (task.user && !seen.has(task.user.id)) {
                seen.set(task.user.id, {
                    id: task.user.id,
                    name: task.user.name,
                    color: getEmployeeColor(task.user.id),
                })
            }
        })
        return [...seen.values()]
    })

    // Tabla de resumen por empleado
    const summaryRows = computed(() => {
        const map = new Map()
        Object.values(calendarDays.value).flat().forEach(task => {
            if (!task.user) return
            if (filterUserId.value && task.user.id != filterUserId.value) return

            if (!map.has(task.user.id)) {
                map.set(task.user.id, {
                    userId: task.user.id,
                    name: task.user.name,
                    color: getEmployeeColor(task.user.id),
                    total: 0,
                    pending: 0,
                    inProgress: 0,
                    completed: 0,
                })
            }
            const row = map.get(task.user.id)
            row.total++
            if (task.status === 'pending') row.pending++
            if (task.status === 'in_progress') row.inProgress++
            if (task.status === 'completed') row.completed++
        })
        return [...map.values()]
    })

    const employeeSchedules = computed(() => {
        const dayShort = ['L', 'M', 'X', 'J', 'V', 'S', 'D']

        return employees.value
            .filter(emp => !filterUserId.value || emp.id == filterUserId.value)
            .map(emp => {
                const byDay = new Map((emp.work_schedule || []).map(day => [Number(day.day_of_week), day]))
                let totalMinutes = 0

                const days = Array.from({ length: 7 }, (_, idx) => {
                    const day = byDay.get(idx)
                    const isWorking = !!day?.is_working
                    const startTime = day?.start_time ? String(day.start_time).slice(0, 5) : null
                    const endTime = day?.end_time ? String(day.end_time).slice(0, 5) : null
                    const breakMinutes = Number(day?.break_minutes ?? 0)

                    if (isWorking && startTime && endTime) {
                        const start = toMinutes(startTime)
                        const end = toMinutes(endTime)
                        if (start !== null && end !== null && end > start) {
                            totalMinutes += Math.max(0, (end - start) - breakMinutes)
                        }
                    }

                    return {
                        day_of_week: idx,
                        short: dayShort[idx],
                        is_working: isWorking,
                        start_time: isWorking ? startTime : null,
                        end_time: isWorking ? endTime : null,
                    }
                })

                return {
                    id: emp.id,
                    name: emp.name,
                    color: getEmployeeColor(emp.id),
                    total_hours: Math.round((totalMinutes / 60) * 10) / 10,
                    days,
                }
            })
            .sort((a, b) => a.name.localeCompare(b.name, 'es'))
    })

    // ── API ───────────────────────────────────────────────────────
    async function fetchCalendar() {
        loading.value = true
        try {
            const { data } = await api.get('/admin/calendar', {
                params: { week: toYMD(currentWeekStart.value) }
            })
            calendarDays.value = data.days

            // Asigna colores a los empleados que aparecen
            Object.values(data.days).flat().forEach(task => {
                if (task.user) getEmployeeColor(task.user.id)
            })
        } finally {
            loading.value = false
        }
    }

    async function fetchEmployees() {
        const { data } = await api.get('/admin/employees')
        employees.value = data
    }

    function changeWeek(direction) {
        currentWeekStart.value = addDays(currentWeekStart.value, direction * 7)
        fetchCalendar()
    }

    return {
        // Estado
        calendarDays, employees, loading, filterUserId,
        // Computed
        weekLabel, isCurrentWeek, weekDays,
        visibleEmployees, summaryRows, employeeSchedules,
        // Helpers
        getEmployeeColor, statusBadge,
        // Métodos
        fetchCalendar, fetchEmployees, changeWeek,
    }
}
