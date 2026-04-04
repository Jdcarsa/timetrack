<template>
  <div class="page">

    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="page-title" style="margin-bottom:4px">Empleados</h2>
        <p class="text-muted">Gestiona el equipo y sus tarifas por hora</p>
      </div>
      <button class="btn btn-primary" @click="showForm = !showForm">
        <svg v-if="!showForm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
          <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
          <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 1 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
        {{ showForm ? 'Cancelar' : 'Nuevo empleado' }}
      </button>
    </div>

    <transition name="slide">
      <div v-if="showForm" class="card mb-4">
        <h3 class="section-title mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15">
            <path d="M6.25 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM3.25 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM19.75 7.5a.75.75 0 0 0-1.5 0v2.25H16a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25H22a.75.75 0 0 0 0-1.5h-2.25V7.5Z" />
          </svg>
          Nuevo empleado
        </h3>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nombre completo *</label>
            <input v-model="form.name" type="text" class="form-input" placeholder="Ej: Juan Pérez" />
            <span v-if="formErrors.name" class="field-error">{{ formErrors.name }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Correo electrónico *</label>
            <input v-model="form.email" type="email" class="form-input" placeholder="juan@empresa.com" />
            <span v-if="formErrors.email" class="field-error">{{ formErrors.email }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Contraseña *</label>
            <input v-model="form.password" type="password" class="form-input" placeholder="Mínimo 6 caracteres" />
            <span v-if="formErrors.password" class="field-error">{{ formErrors.password }}</span>
          </div>
          <div class="form-group">
            <label class="form-label">Tarifa por hora ($) *</label>
            <input v-model="form.hourly_rate" type="number" class="form-input" placeholder="Ej: 15000" min="0" />
            <span v-if="formErrors.hourly_rate" class="field-error">{{ formErrors.hourly_rate }}</span>
          </div>
        </div>

        <div v-if="createError"   class="alert alert-error">{{ createError }}</div>
        <div v-if="createSuccess" class="alert alert-success">{{ createSuccess }}</div>

        <div class="flex gap-2 mt-4">
          <button class="btn btn-primary" :disabled="saving" @click="createEmployee">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15">
              <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
            </svg>
            {{ saving ? 'Guardando...' : 'Crear empleado' }}
          </button>
          <button class="btn btn-outline" @click="resetForm">Limpiar</button>
        </div>
      </div>
    </transition>

    <div class="card">
      <div v-if="loading" class="empty-state">
        <div class="loading-spinner"></div>
        <p class="text-muted" style="margin-top:12px">Cargando empleados...</p>
      </div>

      <template v-else>
        <div v-if="employees.length === 0" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="32" height="32" style="color:#D4D4D8; margin-bottom:8px">
            <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 1 1-1.49-.158 5.25 5.25 0 0 0-10.436 0 .75.75 0 0 1-1.49.158 6.745 6.745 0 0 1 1.017-4.381Z" clip-rule="evenodd" />
          </svg>
          <p>No hay empleados aún</p>
          <p class="text-muted">Crea el primero con el botón "Nuevo empleado"</p>
        </div>

        <div v-else>
          <p class="text-muted mb-4" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.05em">
            {{ employees.length }} empleado{{ employees.length !== 1 ? 's' : '' }}
          </p>

          <div class="employees-grid">
            <div v-for="emp in employees" :key="emp.id" class="employee-card">
              <div class="emp-header">
                <div class="emp-avatar-lg">
                  {{ emp.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) }}
                </div>
                <div class="emp-info">
                  <p class="emp-name">{{ emp.name }}</p>
                  <p class="emp-email">{{ emp.email }}</p>
                </div>
              </div>

              <hr class="divider" style="margin:12px 0" />

              <div class="emp-rate-row">
                <span class="text-muted" style="font-size:12px">Tarifa / hora</span>
                <div v-if="editing === emp.id" class="rate-edit">
                  <input v-model="editRate" type="number" class="form-input"
                    style="width:110px; padding:5px 8px; font-size:13px" min="0" />
                  <button class="btn btn-success btn-sm" :disabled="saving" @click="saveRate(emp)">
                    {{ saving ? '...' : '✓' }}
                  </button>
                  <button class="btn btn-ghost btn-sm" @click="editing = null">✕</button>
                </div>
                <div v-else class="flex items-center gap-2">
                  <span class="rate-value">${{ formatMoney(emp.hourly_rate) }}</span>
                  <button class="btn btn-ghost btn-sm" style="padding:3px 7px" @click="startEdit(emp)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="13" height="13">
                      <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32l8.4-8.4Z" />
                    </svg>
                  </button>
                </div>
              </div>

              <div v-if="savedMessage === emp.id" class="alert alert-success"
                style="margin-top:10px; margin-bottom:0; padding:7px 10px; font-size:12px">
                Tarifa actualizada correctamente
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-if="rateError" class="alert alert-error mt-4">{{ rateError }}</div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useEmpleados } from '@/composables/useEmpleados'
import '@/assets/empleados.css'

const {
  employees, loading, showForm, saving,
  editing, editRate, savedMessage,
  rateError, createError, createSuccess,
  formErrors, form,
  fetchEmployees, createEmployee,
  saveRate, resetForm, startEdit,
  formatMoney,
} = useEmpleados()

onMounted(fetchEmployees)
</script>