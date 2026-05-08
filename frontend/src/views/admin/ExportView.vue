<template>
  <div class="page">

    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="page-title" style="margin-bottom:4px">Exportar a Excel</h2>
        <p class="text-muted">Genera un consolidado de horas trabajadas por rango de fechas</p>
      </div>
      <div class="header-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22">
          <path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v11.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.22 3.22V3a.75.75 0 0 1 .75-.75Zm-9 13.5a.75.75 0 0 1 .75.75v2.25a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5V16.5a.75.75 0 0 1 1.5 0v2.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V16.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
        </svg>
      </div>
    </div>

    <div class="export-layout">
      <div class="export-filters card">
        <h3 class="section-title mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15">
            <path fill-rule="evenodd" d="M3.792 2.938A49.069 49.069 0 0 1 12 2.25c2.797 0 5.54.236 8.209.688a1.857 1.857 0 0 1 1.541 1.836v1.044a3 3 0 0 1-.879 2.121l-6.182 6.182a1.5 1.5 0 0 0-.439 1.061v2.927a3 3 0 0 1-1.658 2.684l-1.757.878A.75.75 0 0 1 9.75 21v-5.818a1.5 1.5 0 0 0-.44-1.06L3.13 7.938a3 3 0 0 1-.879-2.121V4.774a1.857 1.857 0 0 1 1.542-1.836Z" clip-rule="evenodd" />
          </svg>
          Filtros
        </h3>

        <div class="form-group">
          <label class="form-label">Fecha desde *</label>
          <input v-model="from" type="date" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">Fecha hasta *</label>
          <input v-model="to" type="date" class="form-input" />
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">Empleado</label>
          <select v-model="userId" class="form-input">
            <option value="">Todos los empleados</option>
            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">Modo de calculo</label>
          <select v-model="mode" class="form-input">
            <option value="actual">Horas contadas (clock in/out)</option>
            <option value="schedule">Horario planificado</option>
          </select>
        </div>

        <div v-if="error" class="alert alert-error">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15" style="flex-shrink:0">
            <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
          </svg>
          {{ error }}
        </div>
        <div v-if="success" class="alert alert-success">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15" style="flex-shrink:0">
            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
          </svg>
          {{ success }}
        </div>

        <div class="action-buttons">
          <button class="btn btn-outline" style="width:100%; justify-content:center"
            :disabled="loading || !from || !to" @click="previewSummary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15">
              <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
              <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" />
            </svg>
            {{ loading ? 'Cargando...' : 'Ver resumen' }}
          </button>
          <button class="btn btn-primary" style="width:100%; justify-content:center"
            :disabled="exporting || !from || !to" @click="downloadExcel">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15">
              <path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v11.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.22 3.22V3a.75.75 0 0 1 .75-.75Zm-9 13.5a.75.75 0 0 1 .75.75v2.25a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5V16.5a.75.75 0 0 1 1.5 0v2.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V16.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
            </svg>
            {{ exporting ? 'Generando...' : 'Descargar Excel' }}
          </button>
        </div>
      </div>

      <div class="export-preview">
        <div v-if="summary.length === 0 && !loading" class="card empty-preview">
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
              <path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm6.905 9.97a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72V18a.75.75 0 0 0 1.5 0v-4.19l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />
              <path d="M14.25 5.25a5.23 5.23 0 0 0-1.279-3.434 9.768 9.768 0 0 1 6.963 6.963A5.23 5.23 0 0 0 16.5 7.5h-1.875a.375.375 0 0 1-.375-.375V5.25Z" />
            </svg>
          </div>
          <p style="font-weight:600; margin-bottom:4px">Sin datos</p>
          <p class="text-muted" style="font-size:13px; text-align:center">
            Selecciona un rango de fechas y haz clic en<br><strong>"Ver resumen"</strong> para previsualizar
          </p>
        </div>

        <div v-else-if="loading" class="card empty-preview">
          <div class="loading-spinner"></div>
          <p class="text-muted" style="margin-top:12px">Calculando resumen...</p>
        </div>

        <div v-else class="card" style="padding:0; overflow:hidden">
          <div class="preview-header">
            <div>
              <p style="font-weight:700; font-size:14px; color:#fff">Resumen del perÃ­odo</p>
              <p style="font-size:12px; color:rgba(255,255,255,.65); margin-top:2px">{{ from }} â†’ {{ to }}</p>
              <p style="font-size:12px; color:rgba(255,255,255,.65); margin-top:2px">{{ modeLabel }}</p>
            </div>
            <div class="preview-stats">
              <div class="preview-stat">
                <p class="preview-stat-value">{{ totalHours }}h</p>
                <p class="preview-stat-label">Horas totales</p>
              </div>
              <div class="preview-stat-divider"></div>
              <div class="preview-stat">
                <p class="preview-stat-value">${{ formatMoney(totalEarnings) }}</p>
                <p class="preview-stat-label">Total a pagar</p>
              </div>
            </div>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Empleado</th>
                  <th style="text-align:center">Registros</th>
                  <th style="text-align:right">Horas</th>
                  <th style="text-align:right">Total a Pagar</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in summary" :key="s.user_id">
                  <td>
                    <div class="employee-cell">
                      <div class="emp-avatar">{{ s.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase() }}</div>
                      <div>
                        <p style="font-weight:600">{{ s.name }}</p>
                        <p style="font-size:12px; color:var(--text-muted)">{{ s.email }}</p>
                      </div>
                    </div>
                  </td>
                  <td style="text-align:center"><span class="badge badge-gray">{{ s.total_records }}</span></td>
                  <td style="text-align:right; font-weight:600">{{ s.total_hours }}h</td>
                  <td style="text-align:right"><span style="font-weight:700; color:var(--success)">${{ formatMoney(s.total_earnings) }}</span></td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="totals-row">
                  <td><strong>Total general</strong></td>
                  <td style="text-align:center"><strong>{{ totalRecords }}</strong></td>
                  <td style="text-align:right"><strong>{{ totalHours }}h</strong></td>
                  <td style="text-align:right"><strong style="color:var(--success)">${{ formatMoney(totalEarnings) }}</strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useExport } from '@/composables/useExport'
import '@/assets/export.css'

const {
  from, to, userId, mode, employees, summary,
  loading, exporting, error, success,
  totalRecords, totalHours, totalEarnings, modeLabel,
  fetchEmployees, previewSummary, downloadExcel,
  formatMoney,
} = useExport()

onMounted(fetchEmployees)
</script>

