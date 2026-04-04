<template>
    <div class="page">

        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="page-title" style="margin-bottom:4px">Calendario del equipo</h2>
                <p class="text-muted">Visualiza las tareas de todos los empleados</p>
            </div>
        </div>

        <!-- Controles -->
        <div class="controls-bar card mb-4">
            <div class="week-nav">
                <button class="btn btn-ghost btn-sm" @click="changeWeek(-1)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15"
                        height="15">
                        <path fill-rule="evenodd"
                            d="M7.72 12.53a.75.75 0 0 1 0-1.06l7.5-7.5a.75.75 0 1 1 1.06 1.06L9.31 12l6.97 6.97a.75.75 0 1 1-1.06 1.06l-7.5-7.5Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <span class="week-label">
                    {{ weekLabel }}
                    <span v-if="isCurrentWeek" class="badge badge-blue" style="font-size:11px">Esta semana</span>
                </span>
                <button class="btn btn-ghost btn-sm" @click="changeWeek(1)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15"
                        height="15">
                        <path fill-rule="evenodd"
                            d="M16.28 11.47a.75.75 0 0 1 0 1.06l-7.5 7.5a.75.75 0 0 1-1.06-1.06L14.69 12 7.72 5.03a.75.75 0 0 1 1.06-1.06l7.5 7.5Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Filtro por empleado -->
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15"
                    style="color:var(--text-muted)">
                    <path fill-rule="evenodd"
                        d="M3.792 2.938A49.069 49.069 0 0 1 12 2.25c2.797 0 5.54.236 8.209.688a1.857 1.857 0 0 1 1.541 1.836v1.044a3 3 0 0 1-.879 2.121l-6.182 6.182a1.5 1.5 0 0 0-.439 1.061v2.927a3 3 0 0 1-1.658 2.684l-1.757.878A.75.75 0 0 1 9.75 21v-5.818a1.5 1.5 0 0 0-.44-1.06L3.13 7.938a3 3 0 0 1-.879-2.121V4.774a1.857 1.857 0 0 1 1.542-1.836Z"
                        clip-rule="evenodd" />
                </svg>
                <select v-model="filterUserId" class="form-input" style="width:200px" @change="fetchCalendar">
                    <option value="">Todos los empleados</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                </select>
            </div>
        </div>

        <!-- Leyenda de colores -->
        <div v-if="visibleEmployees.length > 0" class="legend-row mb-4">
            <div v-for="emp in visibleEmployees" :key="emp.id" class="legend-item">
                <span class="legend-dot" :style="{ background: emp.color }"></span>
                <span style="font-size:12px; font-weight:600">{{ emp.name }}</span>
            </div>
        </div>

        <!-- Calendario -->
        <div v-if="loading" class="card" style="text-align:center; padding:40px">
            <div class="loading-spinner" style="margin:0 auto"></div>
            <p class="text-muted" style="margin-top:12px">Cargando calendario...</p>
        </div>

        <div v-else class="week-grid">
            <div v-for="day in weekDays" :key="day.date" class="day-column card" :class="{ 'day-today': day.isToday }">
                <div class="day-header">
                    <p class="day-name">{{ day.name }}</p>
                    <p class="day-date" :class="{ 'today-color': day.isToday }">{{ day.label }}</p>
                    <span v-if="day.tasks.length > 0" class="day-count">{{ day.tasks.length }}</span>
                </div>

                <div class="tasks-list">
                    <div v-if="day.tasks.length === 0" class="empty-day">Sin tareas</div>

                    <div v-for="task in day.tasks" :key="task.id + '-' + task.date" class="task-card"
                        :style="{ borderLeftColor: getEmployeeColor(task.user?.id) }">
                        <div class="task-employee">
                            <span class="emp-dot" :style="{ background: getEmployeeColor(task.user?.id) }"></span>
                            <span class="emp-name-small">{{ task.user?.name }}</span>
                        </div>

                        <p class="task-time">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="11"
                                height="11">
                                <path fill-rule="evenodd"
                                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ task.start_time }} - {{ task.end_time }}
                            <span v-if="task.is_recurring" title="Recurrente">🔁</span>
                        </p>

                        <p class="task-title">{{ task.title }}</p>
                        <p v-if="task.description" class="task-desc">{{ task.description }}</p>

                        <div style="margin-top:6px">
                            <span :class="statusBadge(task.status)">{{ task.status_label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen semanal -->
        <div v-if="!loading && summaryRows.length > 0" class="card mt-4">
            <h3 class="section-title mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15">
                    <path fill-rule="evenodd"
                        d="M2.25 13.5a8.25 8.25 0 0 1 8.25-8.25.75.75 0 0 1 .75.75v6.75H18a.75.75 0 0 1 .75.75 8.25 8.25 0 0 1-16.5 0Z"
                        clip-rule="evenodd" />
                    <path fill-rule="evenodd"
                        d="M12.75 3a.75.75 0 0 1 .75-.75 8.25 8.25 0 0 1 8.25 8.25.75.75 0 0 1-.75.75h-7.5a.75.75 0 0 1-.75-.75V3Z"
                        clip-rule="evenodd" />
                </svg>
                Resumen de la semana
            </h3>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th style="text-align:center">Total</th>
                            <th style="text-align:center">Pendientes</th>
                            <th style="text-align:center">En progreso</th>
                            <th style="text-align:center">Completadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in summaryRows" :key="row.userId">
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="legend-dot" :style="{ background: row.color }"></span>
                                    <span style="font-weight:600">{{ row.name }}</span>
                                </div>
                            </td>
                            <td style="text-align:center"><strong>{{ row.total }}</strong></td>
                            <td style="text-align:center">
                                <span class="badge badge-gray">{{ row.pending }}</span>
                            </td>
                            <td style="text-align:center">
                                <span class="badge badge-blue">{{ row.inProgress }}</span>
                            </td>
                            <td style="text-align:center">
                                <span class="badge badge-green">{{ row.completed }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCalendario } from '@/composables/useCalendario'
import '@/assets/calendario.css'

const {
    employees, loading, filterUserId,
    weekLabel, isCurrentWeek, weekDays,
    visibleEmployees, summaryRows,
    getEmployeeColor, statusBadge,
    fetchCalendar, fetchEmployees, changeWeek,
} = useCalendario()

onMounted(() => {
    fetchEmployees()
    fetchCalendar()
})
</script>