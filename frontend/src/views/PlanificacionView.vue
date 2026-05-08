<template>
    <div class="page">

        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="page-title" style="margin-bottom:4px">Mi Planificación</h2>
                <p class="text-muted">Organiza tus tareas de la semana</p>
            </div>
            <button class="btn btn-primary" @click="openForm()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path fill-rule="evenodd"
                        d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                        clip-rule="evenodd" />
                </svg>
                Nueva tarea
            </button>
        </div>

        <!-- Navegación de semana -->
        <div class="week-nav card mb-4">
            <button class="btn btn-ghost btn-sm" @click="changeWeek(-1)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path fill-rule="evenodd"
                        d="M7.72 12.53a.75.75 0 0 1 0-1.06l7.5-7.5a.75.75 0 1 1 1.06 1.06L9.31 12l6.97 6.97a.75.75 0 1 1-1.06 1.06l-7.5-7.5Z"
                        clip-rule="evenodd" />
                </svg>
                Anterior
            </button>

            <div class="week-label">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"
                    style="color:var(--accent)">
                    <path fill-rule="evenodd"
                        d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z"
                        clip-rule="evenodd" />
                </svg>
                <span class="week-label-text">{{ weekLabel }}</span>
                <span v-if="isCurrentWeek" class="badge badge-blue" style="font-size:11px">Esta semana</span>
            </div>

            <button class="btn btn-ghost btn-sm" @click="changeWeek(1)">
                Siguiente
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path fill-rule="evenodd"
                        d="M16.28 11.47a.75.75 0 0 1 0 1.06l-7.5 7.5a.75.75 0 0 1-1.06-1.06L14.69 12 7.72 5.03a.75.75 0 0 1 1.06-1.06l7.5 7.5Z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="card mb-4">
            <div class="flex items-center justify-between mb-4" style="gap:12px">
                <div>
                    <h3 style="font-size:15px; font-weight:700">Mi horario laboral</h3>
                    <p class="text-muted" style="font-size:12px">Define tus dias y horas. Se usa para exportes por horario.</p>
                </div>
                <button class="btn btn-outline btn-sm" :disabled="scheduleLoading || scheduleSaving" @click="fetchWorkSchedule">
                    Recargar
                </button>
            </div>

            <div v-if="scheduleLoading" class="text-muted" style="font-size:13px">Cargando horario...</div>

            <template v-else>
                <div class="schedule-batch">
                    <p class="schedule-batch-title">Aplicar a varios dias</p>

                    <div class="schedule-day-chips">
                        <button v-for="d in DAY_OPTIONS" :key="d.value" type="button" class="day-chip"
                            :class="{ active: selectedScheduleDays.includes(d.value) }"
                            @click="toggleScheduleDay(d.value)">
                            {{ d.short }}
                        </button>
                    </div>

                    <div class="schedule-batch-actions">
                        <button type="button" class="btn btn-ghost btn-sm" @click="selectWeekdays">L-V</button>
                        <button type="button" class="btn btn-ghost btn-sm" @click="selectAllDays">Todos</button>
                        <button type="button" class="btn btn-ghost btn-sm" @click="clearSelectedDays">Limpiar</button>
                    </div>

                    <div class="schedule-batch-form">
                        <label class="schedule-day-label" style="min-width:0">
                            <input v-model="batchWorking" type="checkbox" />
                            <span>Dia laboral</span>
                        </label>

                        <input v-model="batchStartTime" type="time" class="form-input" :disabled="!batchWorking" />
                        <input v-model="batchEndTime" type="time" class="form-input" :disabled="!batchWorking" />
                        <input v-model.number="batchBreakMinutes" type="number" min="0" max="300" class="form-input"
                            :disabled="!batchWorking" placeholder="Descanso (min)" />

                        <button type="button" class="btn btn-outline btn-sm" @click="applyBatchSchedule">
                            Aplicar seleccion
                        </button>
                    </div>
                </div>

                <p class="text-muted" style="font-size:12px">
                    Usa los selectores de arriba para aplicar tu horario a los dias que necesites y luego guarda.
                </p>
            </template>

            <div v-if="scheduleError" class="alert alert-error mt-4">{{ scheduleError }}</div>
            <div v-if="scheduleSuccess" class="alert alert-success mt-4">{{ scheduleSuccess }}</div>

            <div class="flex gap-2 mt-4">
                <button class="btn btn-primary" :disabled="scheduleSaving || scheduleLoading" @click="saveWorkSchedule">
                    {{ scheduleSaving ? 'Guardando...' : 'Guardar horario' }}
                </button>
            </div>
        </div>

        <!-- Calendario semanal -->
        <div v-if="loading" class="card" style="text-align:center; padding:40px">
            <div class="loading-spinner" style="margin:0 auto"></div>
            <p class="text-muted" style="margin-top:12px">Cargando semana...</p>
        </div>

        <div v-else class="week-grid">
            <div v-for="day in weekDays" :key="day.date" class="day-column card" :class="{ 'day-today': day.isToday }">
                <div class="day-header">
                    <p class="day-name">{{ day.name }}</p>
                    <p class="day-date" :class="{ 'today-badge': day.isToday }">{{ day.label }}</p>
                </div>

                <button class="add-task-btn" @click="openForm(day.date)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13"
                        height="13">
                        <path fill-rule="evenodd"
                            d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                            clip-rule="evenodd" />
                    </svg>
                    Agregar
                </button>

                <div class="tasks-list">
                    <div v-if="day.tasks.length === 0" class="empty-day">Sin tareas</div>

                    <div v-for="task in day.tasks" :key="task.id" class="task-card" :class="'task-' + task.status">
                        <p class="task-time">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11"
                                height="11">
                                <path fill-rule="evenodd"
                                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ task.start_time }} - {{ task.end_time }}
                            <span v-if="task.is_recurring" title="Tarea recurrente">🔁</span>
                        </p>

                        <p class="task-title">{{ task.title }}</p>

                        <span v-if="task.schedule" class="schedule-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="9" height="9">
                            <path fill-rule="evenodd" d="M1.5 5.625c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v12.75c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 18.375V5.625Z" clip-rule="evenodd" />
                        </svg>
                        {{ task.schedule.title }}
                        </span>

                        <p v-if="task.description" class="task-desc">{{ task.description }}</p>

                        <div class="task-footer">
                            <select :value="task.status" class="status-select" :class="'status-' + task.status"
                                @change="changeStatus(task, $event.target.value)">
                                <option value="pending">Pendiente</option>
                                <option value="in_progress">En progreso</option>
                                <option value="completed">Completada</option>
                            </select>

                            <div class="task-actions">
                                <button class="icon-btn" title="Editar" @click="openForm(day.date, task)">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        width="13" height="13">
                                        <path
                                            d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
                                    </svg>
                                </button>
                                <button class="icon-btn danger" title="Eliminar" @click="deleteTask(task)">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        width="13" height="13">
                                        <path fill-rule="evenodd"
                                            d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeForm">
            <div class="modal-box card">
                <div class="flex items-center justify-between mb-4">
                    <h3 style="font-size:16px; font-weight:700">
                        {{ editingTask ? 'Editar tarea' : 'Nueva tarea' }}
                    </h3>
                    <button class="btn btn-ghost btn-sm" @click="closeForm">✕</button>
                </div>

                <div class="form-group">
                    <label class="form-label">Título *</label>
                    <input v-model="form.title" type="text" class="form-input" placeholder="Ej: Reunión de equipo" />
                    <span v-if="errors.title" class="field-error">{{ errors.title }}</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea v-model="form.description" class="form-input" rows="2"
                        placeholder="Detalle opcional..."></textarea>
                </div>

                <div class="recur-toggle mb-4">
                    <label class="toggle-label">
                        <input v-model="form.is_recurring" type="checkbox" class="toggle-input" />
                        <span class="toggle-track"></span>
                        <span style="font-size:13px; font-weight:600">Tarea recurrente (se repite cada semana)</span>
                    </label>
                </div>

                <div v-if="form.is_recurring" class="form-group">
                    <label class="form-label">Día de la semana *</label>
                    <select v-model="form.recur_day" class="form-input">
                        <option :value="0">Lunes</option>
                        <option :value="1">Martes</option>
                        <option :value="2">Miércoles</option>
                        <option :value="3">Jueves</option>
                        <option :value="4">Viernes</option>
                        <option :value="5">Sábado</option>
                        <option :value="6">Domingo</option>
                    </select>
                </div>

                <div v-else class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input v-model="form.date" type="date" class="form-input" />
                    <span v-if="errors.date" class="field-error">{{ errors.date }}</span>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Hora inicio *</label>
                        <input v-model="form.start_time" type="time" class="form-input" />
                        <span v-if="errors.start_time" class="field-error">{{ errors.start_time }}</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora fin *</label>
                        <input v-model="form.end_time" type="time" class="form-input" />
                        <span v-if="errors.end_time" class="field-error">{{ errors.end_time }}</span>
                    </div>
                </div>

                <div v-if="formError" class="alert alert-error">{{ formError }}</div>

                <div class="flex gap-2 mt-4">
                    <button class="btn btn-primary" :disabled="saving" @click="saveTask">
                        {{ saving ? 'Guardando...' : editingTask ? 'Guardar cambios' : 'Crear tarea' }}
                    </button>
                    <button class="btn btn-outline" @click="closeForm">Cancelar</button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { usePlanificacion } from '@/composables/usePlanificacion'
import '@/assets/planificacion.css'

const DAY_OPTIONS = [
    { value: 0, short: 'L' },
    { value: 1, short: 'M' },
    { value: 2, short: 'X' },
    { value: 3, short: 'J' },
    { value: 4, short: 'V' },
    { value: 5, short: 'S' },
    { value: 6, short: 'D' },
]

const {
    loading, saving, showModal, editingTask,
    formError, errors, form,
    scheduleLoading, scheduleSaving, scheduleError, scheduleSuccess, scheduleDays,
    weekLabel, isCurrentWeek, weekDays,
    fetchTasks, saveTask, changeStatus,
    deleteTask, changeWeek, openForm, closeForm,
    fetchWorkSchedule, saveWorkSchedule,
} = usePlanificacion()

const selectedScheduleDays = ref([0, 1, 2, 3, 4])
const batchWorking = ref(true)
const batchStartTime = ref('08:00')
const batchEndTime = ref('17:00')
const batchBreakMinutes = ref(60)

function toggleScheduleDay(day) {
    if (selectedScheduleDays.value.includes(day)) {
        selectedScheduleDays.value = selectedScheduleDays.value.filter(d => d !== day)
        return
    }
    selectedScheduleDays.value = [...selectedScheduleDays.value, day].sort((a, b) => a - b)
}

function selectWeekdays() {
    selectedScheduleDays.value = [0, 1, 2, 3, 4]
}

function selectAllDays() {
    selectedScheduleDays.value = [0, 1, 2, 3, 4, 5, 6]
}

function clearSelectedDays() {
    selectedScheduleDays.value = []
}

function applyBatchSchedule() {
    if (selectedScheduleDays.value.length === 0) {
        alert('Selecciona al menos un dia para aplicar cambios.')
        return
    }

    scheduleDays.forEach(day => {
        if (!selectedScheduleDays.value.includes(day.day_of_week)) return

        day.is_working = batchWorking.value
        if (batchWorking.value) {
            day.start_time = batchStartTime.value
            day.end_time = batchEndTime.value
            day.break_minutes = Number(batchBreakMinutes.value || 0)
        } else {
            day.start_time = null
            day.end_time = null
            day.break_minutes = 0
        }
    })
}

onMounted(() => {
    fetchTasks()
    fetchWorkSchedule()
})
</script>
