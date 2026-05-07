<template>
  <div class="page">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="page-title" style="margin-bottom:4px">Bienvenido, {{ auth.user?.name }}</h2>
        <p class="text-muted">{{ currentDate }}</p>
      </div>
      <div class="clock-badge">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
          <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
        </svg>
        {{ currentTime }}
      </div>
    </div>

    <!-- Tarjeta de fichaje -->
    <div class="card mb-4">
      <div v-if="loading" class="text-muted text-center" style="padding:20px">Cargando estado...</div>

      <template v-else>
        <div class="status-row">
          <!-- Estado actual -->
          <div class="status-info">
            <p class="status-label">Estado actual</p>
            <div class="flex items-center gap-2" style="margin-top:6px">
              <span :class="isClockedIn ? 'status-dot green' : 'status-dot gray'"></span>
              <span class="status-text">{{ isClockedIn ? 'En jornada' : 'Sin fichar' }}</span>
            </div>
            <p v-if="isClockedIn && activeRecord" class="text-muted" style="margin-top:6px; font-size:12px">
              Entrada a las {{ formatTime(activeRecord.clock_in) }}
            </p>
          </div>

          <!-- Botón de fichaje -->
          <button
            v-if="!isClockedIn"
            class="btn btn-clock green"
            :disabled="actionLoading"
            @click="clockIn"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
              <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd" />
            </svg>
            {{ actionLoading ? 'Registrando...' : 'Marcar Entrada' }}
          </button>

          <button
            v-else
            class="btn btn-clock red"
            :disabled="actionLoading"
            @click="clockOut"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
              <path fill-rule="evenodd" d="M4.5 7.5a3 3 0 0 1 3-3h9a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-9a3 3 0 0 1-3-3v-9Z" clip-rule="evenodd" />
            </svg>
            {{ actionLoading ? 'Registrando...' : 'Marcar Salida' }}
          </button>
        </div>

        <div v-if="message" :class="['alert', messageType === 'success' ? 'alert-success' : 'alert-error']" style="margin-top:16px; margin-bottom:0">
          {{ message }}
        </div>
      </template>
    </div>

    <!-- Últimos registros -->
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="section-title">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
            <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0 1 18 9.375v9.375a3 3 0 0 0 3-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 0 0-.673-.05A3 3 0 0 0 15 1.5h-1.5a3 3 0 0 0-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6ZM13.5 3A1.5 1.5 0 0 0 12 4.5h4.5A1.5 1.5 0 0 0 15 3h-1.5Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V9.375Zm9.586 4.594a.75.75 0 0 0-1.172-.938l-2.476 3.096-.908-.907a.75.75 0 0 0-1.06 1.06l1.5 1.5a.75.75 0 0 0 1.116-.062l3-3.75Z" clip-rule="evenodd" />
          </svg>
          Últimos registros
        </h3>
        <RouterLink to="/historial" class="btn btn-ghost btn-sm">Ver todos →</RouterLink>
      </div>

      <div v-if="records.length === 0" class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="32" height="32" style="color:#D4D4D8; margin-bottom:8px">
          <path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Z" clip-rule="evenodd" />
        </svg>
        <p>Aún no tienes registros</p>
      </div>

      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Entrada</th>
              <th>Salida</th>
              <th>Horas</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in records.slice(0, 5)" :key="r.id">
              <td>{{ formatDate(r.clock_in) }}</td>
              <td>{{ formatTime(r.clock_in) }}</td>
              <td>{{ r.clock_out ? formatTime(r.clock_out) : '—' }}</td>
              <td>{{ r.total_hours ? r.total_hours + 'h' : '—' }}</td>
              <td>{{ r.clock_out ? '$' + formatMoney(r.total_hours * r.hourly_rate_snapshot) : '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth          = useAuthStore()
const loading       = ref(true)
const actionLoading = ref(false)
const isClockedIn   = ref(false)
const activeRecord  = ref(null)
const records       = ref([])
const message       = ref('')
const messageType   = ref('success')
const currentTime   = ref('')
const currentDate   = ref('')

let clockInterval = null

function tick() {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  currentDate.value = now.toLocaleDateString('es-CO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

async function fetchStatus() {
  try {
    const { data } = await api.get('/status')
    isClockedIn.value  = data.is_clocked_in
    activeRecord.value = data.record
  } finally {
    loading.value = false
  }
}

async function fetchRecords() {
  const { data } = await api.get('/records')
  records.value = data.data
}

async function clockIn() {
  actionLoading.value = true
  message.value = ''
  try {
    await api.post('/clock-in')
    message.value     = 'Entrada registrada correctamente.'
    messageType.value = 'success'
    await fetchStatus()
    await fetchRecords()
  } catch (e) {
    message.value     = e.response?.data?.message || 'Error al registrar entrada.'
    messageType.value = 'error'
  } finally {
    actionLoading.value = false
  }
}

async function clockOut() {
  actionLoading.value = true
  message.value = ''
  try {
    const { data } = await api.post('/clock-out')
    message.value     = `Salida registrada. Trabajaste ${data.total_hours}h — Total: $${formatMoney(data.total_earnings)}`
    messageType.value = 'success'
    await fetchStatus()
    await fetchRecords()
  } catch (e) {
    message.value     = e.response?.data?.message || 'Error al registrar salida.'
    messageType.value = 'error'
  } finally {
    actionLoading.value = false
  }
}

const formatTime  = (dt) => new Date(dt).toLocaleTimeString('es-CO',  { hour: '2-digit', minute: '2-digit' })
const formatDate  = (dt) => new Date(dt).toLocaleDateString('es-CO',  { day: '2-digit', month: '2-digit', year: 'numeric' })
const formatMoney = (v)  => Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0 })

onMounted(() => {
  tick()
  clockInterval = setInterval(tick, 1000)
  fetchStatus()
  fetchRecords()
})
onUnmounted(() => clearInterval(clockInterval))
</script>

<style scoped>
/* Reloj badge */
.clock-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  box-shadow: var(--shadow);
  font-variant-numeric: tabular-nums;
}

/* Status row */
.status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.status-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; }
.status-text  { font-size: 16px; font-weight: 700; color: var(--text); }
.status-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.status-dot.green { background: #16A34A; box-shadow: 0 0 0 3px #DCFCE7; }
.status-dot.gray  { background: #A1A1AA; box-shadow: 0 0 0 3px #F4F4F5; }

/* Botón fichaje grande */
.btn-clock {
  padding: 12px 28px;
  font-size: 15px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all .15s;
  font-family: inherit;
}
.btn-clock:disabled { opacity: .5; cursor: not-allowed; }
.btn-clock.green { background: #16A34A; color: #fff; }
.btn-clock.green:hover:not(:disabled) { background: #15803D; }
.btn-clock.red   { background: #DC2626; color: #fff; }
.btn-clock.red:hover:not(:disabled)   { background: #B91C1C; }

/* Section title */
.section-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
  display: flex;
  align-items: center;
  gap: 7px;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 32px;
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.table-wrap {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.table-wrap::-webkit-scrollbar {
  width: 0;
  height: 0;
  display: none;
}
</style>
