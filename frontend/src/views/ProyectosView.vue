<template>
    <div class="page">

        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="page-title" style="margin-bottom:4px">Proyectos</h2>
                <p class="text-muted">Gestiona los proyectos y sus cronogramas</p>
            </div>
            <button v-if="auth.isAdmin" class="btn btn-primary" @click="openCreate">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path fill-rule="evenodd"
                        d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                        clip-rule="evenodd" />
                </svg>
                Nuevo proyecto
            </button>
        </div>

        <div v-if="loading" class="card" style="text-align:center; padding:40px">
            <div class="loading-spinner" style="margin:0 auto"></div>
            <p class="text-muted" style="margin-top:12px">Cargando proyectos...</p>
        </div>

        <div v-else-if="projects.length === 0" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="36" height="36"
                style="color:#D4D4D8">
                <path fill-rule="evenodd"
                    d="M3 6a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3V6ZM3 15.75a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-2.25Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3v-2.25Z"
                    clip-rule="evenodd" />
            </svg>
            <p style="font-weight:600">No hay proyectos</p>
            <p class="text-muted" style="font-size:13px">
                {{ auth.isAdmin ? 'Crea el primero con el boton "Nuevo proyecto"' : 'El administrador te asignara a un proyecto pronto.' }}
                proyecto pronto.' }}
            </p>
        </div>

        <div v-else class="projects-grid">
            <RouterLink v-for="project in projects" :key="project.id" :to="`/proyectos/${project.id}`"
                class="project-card">
                <div class="project-card-header">
                    <div style="flex:1">
                        <p class="project-title">{{ project.title }}</p>
                        <p v-if="project.description" class="project-desc">{{ project.description }}</p>
                    </div>
                    <span :class="statusBadge(project.status)">{{ statusLabel(project.status) }}</span>
                </div>

                <div class="project-meta">
                    <div class="project-meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13"
                            height="13">
                            <path fill-rule="evenodd"
                                d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ formatDate(project.start_date) }} - {{ formatDate(project.end_date) }}
                    </div>
                    <div class="project-meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13"
                            height="13">
                            <path fill-rule="evenodd"
                                d="M1.5 5.625c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v12.75c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 18.375V5.625Z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ project.schedules_count ?? 0 }} cronograma{{ (project.schedules_count ?? 0) !== 1 ? 's' : ''
                        }}
                    </div>
                </div>

                <div class="members-row">
                    <div v-for="m in project.members.slice(0, 5)" :key="m.id" class="member-dot" :title="m.name">
                        {{ initials(m.name) }}
                    </div>
                    <span v-if="project.members.length > 5" class="text-muted" style="font-size:11px; margin-left:6px">
                        +{{ project.members.length - 5 }} mas
                    </span>
                </div>
            </RouterLink>
        </div>

        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal-box card">
                <div class="flex items-center justify-between mb-4">
                    <h3 style="font-size:16px; font-weight:700">
                        {{ editing ? 'Editar proyecto' : 'Nuevo proyecto' }}
                    </h3>
                    <button class="btn btn-ghost btn-sm" @click="closeModal">X</button>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input v-model="form.title" type="text" class="form-input" placeholder="Ej: Rediseno web" />
                    <span v-if="formErrors.title" class="field-error">{{ formErrors.title }}</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripcion</label>
                    <textarea v-model="form.description" class="form-input" rows="2"
                        placeholder="Descripcion opcional..."></textarea>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Fecha inicio *</label>
                        <input v-model="form.start_date" type="date" class="form-input" />
                        <span v-if="formErrors.start_date" class="field-error">{{ formErrors.start_date }}</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha fin *</label>
                        <input v-model="form.end_date" type="date" class="form-input" />
                        <span v-if="formErrors.end_date" class="field-error">{{ formErrors.end_date }}</span>
                    </div>
                </div>

                <div v-if="editing" class="form-group">
                    <label class="form-label">Estado</label>
                    <select v-model="form.status" class="form-input">
                        <option value="in_progress">En progreso</option>
                        <option value="completed">Completado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Asignar empleados</label>
                    <div class="members-select">
                        <label v-for="emp in employees" :key="emp.id" class="member-option">
                            <input type="checkbox" :checked="form.member_ids.includes(emp.id)"
                                @change="toggleMember(emp.id)" />
                            <span>{{ emp.name }}</span>
                            <span class="text-muted" style="font-size:12px">{{ emp.email }}</span>
                        </label>
                    </div>
                </div>

                <div v-if="formError" class="alert alert-error">{{ formError }}</div>

                <div class="flex gap-2 mt-4">
                    <button class="btn btn-primary" :disabled="saving" @click="saveProject">
                        {{ saving ? 'Guardando...' : editing ? 'Guardar cambios' : 'Crear proyecto' }}
                    </button>
                    <button class="btn btn-outline" @click="closeModal">Cancelar</button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useProyectos } from '@/composables/useProyectos'
import '@/assets/proyectos.css'

const {
    auth, projects, employees, loading, saving,
    showModal, editing, form, formErrors, formError,
    statusBadge, statusLabel, formatDate, initials,
    fetchProjects, fetchEmployees,
    openCreate, openEdit, closeModal,
    toggleMember, saveProject, deleteProject,
} = useProyectos()

onMounted(() => {
    fetchProjects()
    fetchEmployees()
})
</script>
