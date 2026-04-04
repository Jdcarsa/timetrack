<template>
  <div class="page">
    <h2 class="page-title">Mi Historial</h2>

    <div class="card">
      <div v-if="loading" class="text-muted" style="text-align:center; padding:30px">Cargando...</div>

      <template v-else>
        <div v-if="records.length === 0" class="text-muted" style="text-align:center; padding:30px">
          Aún no tienes registros de horas.
        </div>

        <div v-else>
          <!-- Resumen -->
          <div class="summary-row mb-4">
            <div class="summary-box">
              <p class="summary-label">Total registros</p>
              <p class="summary-value">{{ records.length }}</p>
            </div>
            <div class="summary-box">
              <p class="summary-label">Horas totales</p>
              <p class="summary-value">{{ totalHours }}h</p>
            </div>
            <div class="summary-box">
              <p class="summary-label">Total a cobrar</p>
              <p class="summary-value">${{ formatMoney(totalEarnings) }}</p>
            </div>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
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
                  <td>{{ formatDate(r.clock_in) }}</td>
                  <td>{{ formatTime(r.clock_in) }}</td>
                  <td>{{ r.clock_out ? formatTime(r.clock_out) : '—' }}</td>
                  <td>{{ r.total_hours ?? '—' }}</td>
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

          <!-- Paginación -->
          <div class="flex justify-between items-center mt-4 text-muted">
            <span>Página {{ page }} de {{ lastPage }}</span>
            <div class="flex gap-2">
              <button class="btn btn-outline btn-sm" :disabled="page <= 1" @click="changePage(page - 1)">Anterior</button>
              <button class="btn btn-outline btn-sm" :disabled="page >= lastPage" @click="changePage(page + 1)">Siguiente</button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

const loading  = ref(true)
const records  = ref([])
const page     = ref(1)
const lastPage = ref(1)

const totalHours = computed(() =>
  records.value.reduce((sum, r) => sum + (Number(r.total_hours) || 0), 0).toFixed(2)
)
const totalEarnings = computed(() =>
  records.value.reduce((sum, r) => sum + (r.total_hours * r.hourly_rate_snapshot || 0), 0)
)

async function fetchRecords(p = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/records', { params: { page: p } })
    records.value  = data.data
    page.value     = data.current_page
    lastPage.value = data.last_page
  } finally {
    loading.value = false
  }
}

function changePage(p) { fetchRecords(p) }

const formatTime  = (dt) => new Date(dt).toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' })
const formatDate  = (dt) => new Date(dt).toLocaleDateString('es-CO', { day:'2-digit', month:'2-digit', year:'numeric' })
const formatMoney = (v)  => Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0 })

onMounted(() => fetchRecords())
</script>

<style scoped>
.summary-row { display: flex; gap: 12px; flex-wrap: wrap; }
.summary-box {
  flex: 1; min-width: 120px;
  background: var(--bg);
  border-radius: 8px;
  padding: 16px;
  text-align: center;
}
.summary-label { font-size: 12px; color: var(--text-muted); font-weight: 600; }
.summary-value { font-size: 22px; font-weight: 700; margin-top: 4px; color: var(--primary); }
</style>
