<template>
    <div class="page">

        <div v-if="loading" class="card" style="text-align:center; padding:40px">
            <div class="loading-spinner" style="margin:0 auto"></div>
            <p class="text-muted" style="margin-top:12px">Cargando proyecto...</p>
        </div>

        <template v-else-if="project">

            <div class="project-header-card">
                <div class="flex items-center justify-between" style="flex-wrap:wrap; gap:12px">
                    <div>
                        <p class="project-header-title">{{ project.title }}</p>
                        <p v-if="project.description"
                            style="font-size:13px; color:rgba(255,255,255,.6); margin-top:4px">
                            {{ project.description }}
                        </p>
                    </div>
                    <span :class="statusBadge(project.status)">{{ statusLabel(project.status) }}</span>
                </div>
                <div class="project-header-meta">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13"
                            height="13">
                            <path fill-rule="evenodd"
                                d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ formatDate(project.start_date) }} - {{ formatDate(project.end_date) }}
                    </span>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13"
                            height="13">
                            <path fill-rule="evenodd"
                                d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 1 1-1.49-.158 5.25 5.25 0 0 0-10.436 0 .75.75 0 0 1-1.49.158 6.745 6.745 0 0 1 1.017-4.381Z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ project.members?.length ?? 0 }} miembro{{ (project.members?.length ?? 0) !== 1 ? 's' : '' }}
                    </span>
                    <span>Creado por {{ project.created_by?.name }}</span>
                </div>
            </div>

            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 style="font-size:16px; font-weight:700">Cronogramas</h3>
                    <p class="text-muted" style="font-size:13px">
                        {{ schedules.length }} cronograma{{ schedules.length !== 1 ? 's' : '' }} en este proyecto
                    </p>
                </div>
                <div class="flex gap-2">
                    <RouterLink to="/proyectos" class="btn btn-outline btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14"
                            height="14">
                            <path fill-rule="evenodd"
                                d="M7.72 12.53a.75.75 0 0 1 0-1.06l7.5-7.5a.75.75 0 1 1 1.06 1.06L9.31 12l6.97 6.97a.75.75 0 1 1-1.06 1.06l-7.5-7.5Z"
                                clip-rule="evenodd" />
                        </svg>
                        Volver
                    </RouterLink>
                    <button class="btn btn-primary" @click="openCreateSchedule">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15"
                            height="15">
                            <path fill-rule="evenodd"
                                d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                                clip-rule="evenodd" />
                        </svg>
                        Nuevo cronograma
                    </button>
                </div>
            </div>

            <div v-if="schedules.length === 0" class="card"
                style="text-align:center; padding:40px; color:var(--text-muted)">
                <p style="font-weight:600">Sin cronogramas</p>
                <p style="font-size:13px; margin-top:4px">Crea el primero con el boton "Nuevo cronograma"</p>
            </div>

            <div class="schedules-list">
                <div v-for="schedule in schedules" :key="schedule.id" class="schedule-card">

                    <div class="schedule-card-header">
                        <div>
                            <p class="schedule-title">{{ schedule.title }}</p>
                            <p class="schedule-week">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    width="12" height="12">
                                    <path fill-rule="evenodd"
                                        d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z"
                                        clip-rule="evenodd" />
                                </svg>
                                Semana del {{ formatWeek(schedule.week_start) }}
                            </p>
                        </div>
                        <div class="schedule-actions">
                            <span class="badge badge-gray">{{ schedule.tasks?.length ?? 0 }} tareas</span>
                            <button class="btn btn-outline btn-sm" @click="openCreateTask(schedule)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    width="13" height="13">
                                    <path fill-rule="evenodd"
                                        d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                                        clip-rule="evenodd" />
                                </svg>
                                Nueva tarea
                            </button>
                            <button class="btn btn-outline btn-sm" @click="openAddTask(schedule)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    width="13" height="13">
                                    <path
                                        d="M12 1.5a.75.75 0 0 1 .75.75V7.5h-1.5V2.25A.75.75 0 0 1 12 1.5ZM11.25 7.5v5.69l-1.72-1.72a.75.75 0 0 0-1.06 1.06l3 3a.75.75 0 0 0 1.06 0l3-3a.75.75 0 1 0-1.06-1.06l-1.72 1.72V7.5h3.75a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-9a3 3 0 0 1-3-3v-9a3 3 0 0 1 3-3h3.75Z" />
                                </svg>
                                Agregar existente
                            </button>
                            <button class="icon-btn" @click="openEditSchedule(schedule)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    width="14" height="14">
                                    <path
                                        d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
                                </svg>
                            </button>
                            <button class="icon-btn danger" @click="deleteSchedule(schedule)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    width="14" height="14">
                                    <path fill-rule="evenodd"
                                        d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="schedule-tasks">
                        <div v-if="!schedule.tasks || schedule.tasks.length === 0" class="empty-schedule">
                            Sin tareas. Usa "Nueva tarea" para crear una o "Agregar existente" para vincular.
                        </div>

                        <div v-else class="tasks-week-grid">
                            <div v-for="day in weekDays(schedule.week_start)" :key="day.date" class="week-day-col">
                                <p class="week-day-name">{{ day.name }}</p>
                                <p class="week-day-date">{{ day.label }}</p>

                                <div v-if="tasksForDay(schedule, day.date).length === 0"
                                    style="font-size:11px; color:#D4D4D8; text-align:center; padding:8px 0">-</div>

                                <div v-for="task in tasksForDay(schedule, day.date)" :key="task.id" class="task-chip"
                                    :class="'status-' + task.status">
                                    <button class="task-chip-remove" @click="removeTask(schedule, task)">x</button>
                                    <p class="task-chip-title">{{ task.title }}</p>
                                    <p class="task-chip-time">{{ task.start_time }} - {{ task.end_time }}</p>
                                    <p v-if="task.user" class="task-chip-user">{{ task.user.name }}</p>
                                </div>

                                <button class="add-task-btn-small" @click="openCreateTask(schedule)">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        width="11" height="11">
                                        <path fill-rule="evenodd"
                                            d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Nueva
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </template>

        <div v-if="showScheduleModal" class="modal-overlay" @click.self="closeScheduleModal">
            <div class="modal-box card">
                <div class="flex items-center justify-between mb-4">
                    <h3 style="font-size:16px; font-weight:700">
                        {{ editingSchedule ? 'Editar cronograma' : 'Nuevo cronograma' }}
                    </h3>
                    <button class="btn btn-ghost btn-sm" @click="closeScheduleModal">X</button>
                </div>

                <div class="form-group">
                    <label class="form-label">Titulo *</label>
                    <input v-model="scheduleForm.title" type="text" class="form-input"
                        placeholder="Ej: Semana de desarrollo" />
                    <span v-if="formErrors.title" class="field-error">{{ formErrors.title }}</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripcion</label>
                    <textarea v-model="scheduleForm.description" class="form-input" rows="2"
                        placeholder="Descripcion opcional..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Semana (selecciona cualquier dia) *</label>
                    <input v-model="scheduleForm.week_start" type="date" class="form-input" />
                    <span v-if="formErrors.week_start" class="field-error">{{ formErrors.week_start }}</span>
                </div>

                <div v-if="formError" class="alert alert-error">{{ formError }}</div>

                <div class="flex gap-2 mt-4">
                    <button class="btn btn-primary" :disabled="saving" @click="saveSchedule">
                        {{ saving ? 'Guardando...' : editingSchedule ? 'Guardar cambios' : 'Crear cronograma' }}
                    </button>
                    <button class="btn btn-outline" @click="closeScheduleModal">Cancelar</button>
                </div>
            </div>
        </div>

        <div v-if="showCreateTask" class="modal-overlay" @click.self="closeCreateTask">
            <div class="modal-box card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 style="font-size:16px; font-weight:700">Nueva tarea</h3>
                        <p class="text-muted" style="font-size:13px; margin-top:2px">
                            Se vinculara automaticamente a "{{ activeSchedule?.title }}"
                        </p>
                    </div>
                    <button class="btn btn-ghost btn-sm" @click="closeCreateTask">X</button>
                </div>

                <div class="form-group">
                    <label class="form-label">Asignar a *</label>
                    <select v-model="taskForm.user_id" class="form-input">
                        <option value="">Yo mismo</option>
                        <option v-for="m in project?.members ?? []" :key="m.id" :value="m.id">
                            {{ m.name }}
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Titulo *</label>
                    <input v-model="taskForm.title" type="text" class="form-input"
                        placeholder="Ej: Revision de diseno" />
                    <span v-if="taskFormErrors.title" class="field-error">{{ taskFormErrors.title }}</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripcion</label>
                    <textarea v-model="taskForm.description" class="form-input" rows="2"
                        placeholder="Detalle opcional..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input v-model="taskForm.date" type="date" class="form-input" />
                    <span v-if="taskFormErrors.date" class="field-error">{{ taskFormErrors.date }}</span>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                    <div class="form-group">
                        <label class="form-label">Hora inicio *</label>
                        <input v-model="taskForm.start_time" type="time" class="form-input" />
                        <span v-if="taskFormErrors.start_time" class="field-error">{{ taskFormErrors.start_time
                            }}</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora fin *</label>
                        <input v-model="taskForm.end_time" type="time" class="form-input" />
                        <span v-if="taskFormErrors.end_time" class="field-error">{{ taskFormErrors.end_time }}</span>
                    </div>
                </div>

                <div v-if="taskFormError" class="alert alert-error">{{ taskFormError }}</div>

                <div class="flex gap-2 mt-4">
                    <button class="btn btn-primary" :disabled="saving" @click="saveNewTask">
                        {{ saving ? 'Guardando...' : 'Crear tarea' }}
                    </button>
                    <button class="btn btn-outline" @click="closeCreateTask">Cancelar</button>
                </div>
            </div>
        </div>

        <div v-if="showTaskModal" class="modal-overlay" @click.self="closeTaskModal">
            <div class="modal-box card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 style="font-size:16px; font-weight:700">Agregar tarea existente</h3>
                        <p class="text-muted" style="font-size:13px; margin-top:2px">
                            Tareas disponibles de la semana del cronograma
                        </p>
                    </div>
                    <button class="btn btn-ghost btn-sm" @click="closeTaskModal">X</button>
                </div>

                <div v-if="availableTasks.length === 0" class="text-muted text-center"
                    style="padding:24px; font-size:13px">
                    No hay tareas disponibles para esta semana. Crea una con "Nueva tarea".
                </div>

                <div v-else class="available-tasks-list">
                    <div v-for="task in availableTasks" :key="task.id" class="available-task-item">
                        <div class="available-task-info">
                            <p class="available-task-title">{{ task.title }}</p>
                            <p class="available-task-meta">
                                {{ task.date }} | {{ task.start_time }} - {{ task.end_time }}
                                <span v-if="task.user"> | {{ task.user.name }}</span>
                            </p>
                        </div>
                        <button class="btn btn-primary btn-sm" @click="addTask(task)">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13"
                                height="13">
                                <path fill-rule="evenodd"
                                    d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                                    clip-rule="evenodd" />
                            </svg>
                            Agregar
                        </button>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-4">
                    <p class="text-muted" style="font-size:12px">
                        {{ availableTasks.length }} tarea{{ availableTasks.length !== 1 ? 's' : '' }} disponible{{
                            availableTasks.length !== 1 ? 's' : '' }}
                    </p>
                    <button class="btn btn-outline btn-sm" @click="closeTaskModal">Cerrar</button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useCronograma } from '@/composables/useCronograma'
import '@/assets/cronograma.css'

const route = useRoute()

const {
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
} = useCronograma(route.params.id)

onMounted(fetchProject)
</script>