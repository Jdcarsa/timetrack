<template>
  <div class="page">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="page-title" style="margin-bottom:4px">Todos los Registros</h2>
        <p class="text-muted">Historial completo de horas del equipo</p>
      </div>
    </div>

    <div class="card mb-4">
      <div class="filters-row">
        <div class="form-group" style="margin:0; flex:1">
          <label class="form-label">Desde</label>
          <input v-model="from" type="date" class="form-input" />
        </div>
        <div class="form-group" style="margin:0; flex:1">
          <label class="form-label">Hasta</label>
          <input v-model="to" type="date" class="form-input" />
        </div>
        <div class="form-group" style="margin:0; flex:1">
          <label class="form-label">Empleado</label>
          <select v-model="userId" class="form-input">
            <option value="">Todos</option>
            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </div>
        <div style="display:flex; align-items:flex-end">
          <button class="btn btn-primary" @click="fetchRecords(1)">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15">
              <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
            </svg>
            Filtrar
          </button>
        </div>
      </div>
    </div>

    <div class="card">
      <div v-if="loading" class="text-muted text-center" style="padding:30px">Cargando...</div>

      <template v-else>
        <div v-if="records.length === 0" class="text-muted text-center" style="padding:30px">
          No hay registros para los filtros seleccionados.
        </div>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Empleado</th>
                <th>Fecha</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Horas</th>
                <th>Tarifa/h</th>
                <th>Total</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in records" :key="r.id">
                <td>{{ r.user?.name }}</td>
                <td>{{ formatDate(r.clock_in) }}</td>
                <td>{{ formatTime(r.clock_in) }}</td>
                <td>{{ r.clock_out ? formatTime(r.clock_out) : '—' }}</td>
                <td>{{ r.total_hours ?? '-' }}</td>
                <td>${{ formatMoney(r.hourly_rate_snapshot) }}</td>
                <td>{{ r.clock_out ? '$' + formatMoney(r.total_hours * r.hourly_rate_snapshot) : '—' }}</td>
                <td>
                  <span :class="r.clock_out ? 'badge badge-green' : 'badge badge-yellow'">
                    {{ r.clock_out ? 'Completo' : 'Activo' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex justify-between items-center mt-4 text-muted">
          <span>Página {{ page }} de {{ lastPage }}</span>
          <div class="flex gap-2">
            <button class="btn btn-outline btn-sm" :disabled="page <= 1" @click="fetchRecords(page - 1)">← Anterior</button>
            <button class="btn btn-outline btn-sm" :disabled="page >= lastPage" @click="fetchRecords(page + 1)">Siguiente →</button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRegistros } from '@/composables/useRegistros'


const {
  records, employees, loading, page, lastPage,
  from, to, userId,
  fetchRecords, fetchEmployees,
  formatTime, formatDate, formatMoney,
} = useRegistros()

onMounted(() => {
  fetchEmployees()
  fetchRecords()
})
</script>

<style scoped>
.filters-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
</style>
