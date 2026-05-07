<template>
  <div class="app-layout">

    <div class="topbar">
      <div class="topbar-logo">
        <div class="logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
          </svg>
        </div>
        TimeTrack
      </div>
      <button class="hamburger" @click="sidebarOpen = true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22">
          <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75ZM3 12a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25a.75.75 0 0 1 .75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <div :class="['sidebar-overlay', { open: sidebarOpen }]" @click="sidebarOpen = false"></div>

    <aside :class="['sidebar', { open: sidebarOpen, collapsed: sidebarCollapsed }]">

      <div class="sidebar-logo">
        <div class="logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
          </svg>
        </div>
        <span class="brand-text">TimeTrack</span>
        <button
          class="hamburger desktop-only"
          style="margin-left:auto"
          :title="sidebarCollapsed ? 'Expandir menu' : 'Contraer menu'"
          @click="toggleSidebarCollapse"
        >
          <svg v-if="!sidebarCollapsed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path fill-rule="evenodd" d="M15.53 4.47a.75.75 0 0 1 0 1.06L9.06 12l6.47 6.47a.75.75 0 1 1-1.06 1.06l-7-7a.75.75 0 0 1 0-1.06l7-7a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path fill-rule="evenodd" d="M8.47 19.53a.75.75 0 0 1 0-1.06L14.94 12 8.47 5.53a.75.75 0 0 1 1.06-1.06l7 7a.75.75 0 0 1 0 1.06l-7 7a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>

      <nav class="sidebar-nav" @click="sidebarOpen = false">
        <RouterLink to="/" class="nav-item" exact-active-class="active" title="Dashboard">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="17" height="17">
            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
          </svg>
          <span class="nav-label">Dashboard</span>
        </RouterLink>

        <RouterLink to="/historial" class="nav-item" active-class="active" title="Mi Historial">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="17" height="17">
            <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0 1 18 9.375v9.375a3 3 0 0 0 3-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 0 0-.673-.05A3 3 0 0 0 15 1.5h-1.5a3 3 0 0 0-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6ZM13.5 3A1.5 1.5 0 0 0 12 4.5h4.5A1.5 1.5 0 0 0 15 3h-1.5Z" clip-rule="evenodd" />
            <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V9.375Zm9.586 4.594a.75.75 0 0 0-1.172-.938l-2.476 3.096-.908-.907a.75.75 0 0 0-1.06 1.06l1.5 1.5a.75.75 0 0 0 1.116-.062l3-3.75Z" clip-rule="evenodd" />
          </svg>
          <span class="nav-label">Mi Historial</span>
        </RouterLink>

        <RouterLink to="/planificacion" class="nav-item" active-class="active" title="Mi Planificacion">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="17" height="17">
            <path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z" clip-rule="evenodd" />
          </svg>
          <span class="nav-label">Mi Planificacion</span>
        </RouterLink>

        <RouterLink to="/proyectos" class="nav-item" active-class="active" title="Proyectos">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="17" height="17">
            <path fill-rule="evenodd" d="M3 6a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3v2.25a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3V6ZM3 15.75a3 3 0 0 1 3-3h2.25a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-2.25Zm9.75 0a3 3 0 0 1 3-3H18a3 3 0 0 1 3 3V18a3 3 0 0 1-3 3h-2.25a3 3 0 0 1-3-3v-2.25Z" clip-rule="evenodd" />
          </svg>
          <span class="nav-label">Proyectos</span>
        </RouterLink>

        <template v-if="auth.isAdmin">
          <div class="nav-section">Administracion</div>

          <RouterLink to="/admin/empleados" class="nav-item" active-class="active" title="Empleados">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="17" height="17">
              <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 1 1-1.49-.158 5.25 5.25 0 0 0-10.436 0 .75.75 0 0 1-1.49.158 6.745 6.745 0 0 1 1.017-4.381Z" clip-rule="evenodd" />
            </svg>
            <span class="nav-label">Empleados</span>
          </RouterLink>

          <RouterLink to="/admin/registros" class="nav-item" active-class="active" title="Todos los Registros">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="17" height="17">
              <path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 0 1 8.25-8.25.75.75 0 0 1 .75.75v6.75H18a.75.75 0 0 1 .75.75 8.25 8.25 0 0 1-16.5 0Z" clip-rule="evenodd" />
              <path fill-rule="evenodd" d="M12.75 3a.75.75 0 0 1 .75-.75 8.25 8.25 0 0 1 8.25 8.25.75.75 0 0 1-.75.75h-7.5a.75.75 0 0 1-.75-.75V3Z" clip-rule="evenodd" />
            </svg>
            <span class="nav-label">Todos los Registros</span>
          </RouterLink>

          <RouterLink to="/admin/calendario" class="nav-item" active-class="active" title="Calendario">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="17" height="17">
              <path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z" clip-rule="evenodd" />
            </svg>
            <span class="nav-label">Calendario</span>
          </RouterLink>

          <RouterLink to="/admin/export" class="nav-item" active-class="active" title="Exportar Excel">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="17" height="17">
              <path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v11.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.22 3.22V3a.75.75 0 0 1 .75-.75Zm-9 13.5a.75.75 0 0 1 .75.75v2.25a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5V16.5a.75.75 0 0 1 1.5 0v2.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V16.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
            </svg>
            <span class="nav-label">Exportar Excel</span>
          </RouterLink>
        </template>
      </nav>

      <div class="sidebar-footer">
        <div class="user-row">
          <div class="user-avatar">{{ initials }}</div>
          <div class="user-info">
            <p class="user-name">{{ auth.user?.name }}</p>
            <p class="user-role">{{ auth.user?.role === 'admin' ? 'Administrador' : 'Empleado' }}</p>
          </div>
        </div>
        <button class="logout-btn" @click="handleLogout">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
            <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 0 0 6 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5V15a.75.75 0 0 1 1.5 0v3.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V5.25a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3V9A.75.75 0 0 1 15 9V5.25a1.5 1.5 0 0 0-1.5-1.5h-6Zm10.72 4.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9a.75.75 0 0 1 0-1.5h10.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
          </svg>
          <span class="nav-label">Cerrar sesion</span>
        </button>
      </div>
    </aside>

    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth        = useAuthStore()
const router      = useRouter()
const sidebarOpen = ref(false)
const SIDEBAR_COLLAPSED_KEY = 'timetrack_sidebar_collapsed'
const sidebarCollapsed = ref(
  typeof window !== 'undefined' && localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1'
)

const initials = computed(() =>
  auth.user?.name?.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) ?? '?'
)

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

function toggleSidebarCollapse() {
  sidebarCollapsed.value = !sidebarCollapsed.value
  if (typeof window !== 'undefined') {
    localStorage.setItem(SIDEBAR_COLLAPSED_KEY, sidebarCollapsed.value ? '1' : '0')
  }
}
</script>

<style scoped>
.app-layout { display: flex; min-height: 100vh; overflow-x: hidden; }

.sidebar {
  width: 220px;
  min-height: 100vh;
  background: #18181B;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  transition: transform .25s ease, width .2s ease;
  z-index: 50;
}
.sidebar.collapsed {
  width: 76px;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 18px 16px 14px;
  font-size: 16px;
  font-weight: 700;
  color: #fff;
  border-bottom: 1px solid #27272A;
}
.logo-icon {
  width: 32px; height: 32px;
  background: #6366F1;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; flex-shrink: 0;
}

.sidebar-nav {
  padding: 10px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 10px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 500;
  color: #A1A1AA;
  text-decoration: none;
  transition: background .12s, color .12s;
}
.nav-label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.nav-item:hover          { background: #27272A; color: #E4E4E7; }
.nav-item.active         { background: #27272A; color: #fff; font-weight: 600; }
.nav-item.active svg     { color: #6366F1; }

.nav-section {
  font-size: 10px; font-weight: 700;
  letter-spacing: .08em; text-transform: uppercase;
  color: #52525B;
  padding: 14px 10px 4px;
}

.sidebar-footer {
  padding: 12px;
  border-top: 1px solid #27272A;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.user-row    { display: flex; align-items: center; gap: 10px; padding: 4px 2px; }
.user-avatar {
  width: 34px; height: 34px;
  background: #3F3F46; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: #E4E4E7; flex-shrink: 0;
}
.user-info   { overflow: hidden; }
.user-name   {
  font-size: 13px; font-weight: 600; color: #E4E4E7;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-role   { font-size: 11px; color: #71717A; margin-top: 1px; }

.logout-btn {
  display: flex; align-items: center; gap: 8px;
  width: 100%; padding: 8px 10px;
  border-radius: 7px;
  background: #27272A; border: 1px solid #3F3F46;
  color: #E4E4E7; font-size: 13px; font-weight: 500;
  cursor: pointer; transition: background .12s, color .12s;
  font-family: inherit;
}
.logout-btn:hover { background: #DC2626; border-color: #DC2626; color: #fff; }

.main-content { flex: 1; min-width: 0; overflow-y: auto; background: #F4F4F5; }
.desktop-only { display: inline-flex; }
.mobile-only { display: none; }

.sidebar.collapsed .brand-text,
.sidebar.collapsed .nav-label,
.sidebar.collapsed .nav-section,
.sidebar.collapsed .user-info {
  display: none;
}
.sidebar.collapsed .sidebar-logo {
  justify-content: center;
}
.sidebar.collapsed .nav-item {
  justify-content: center;
  padding-left: 8px;
  padding-right: 8px;
}
.sidebar.collapsed .user-row {
  justify-content: center;
}
.sidebar.collapsed .logout-btn {
  justify-content: center;
  padding-left: 8px;
  padding-right: 8px;
}

.topbar {
  display: none;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #18181B;
  position: sticky;
  top: 0;
  z-index: 40;
  border-bottom: 1px solid #27272A;
}
.topbar-logo {
  display: flex; align-items: center; gap: 8px;
  font-size: 15px; font-weight: 700; color: #fff;
}
.hamburger {
  background: none; border: none; cursor: pointer;
  color: #A1A1AA; padding: 4px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 6px;
  transition: background .12s;
}
.hamburger:hover { background: #27272A; color: #fff; }

.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 45;
}

@media (max-width: 1024px) {
  .topbar { display: flex; }
  .desktop-only { display: none; }
  .mobile-only { display: inline-flex; }

  .sidebar {
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    transform: translateX(-100%);
    z-index: 50;
    width: min(82vw, 280px);
  }
  .sidebar.collapsed {
    width: min(82vw, 280px);
  }
  .sidebar.collapsed .brand-text { display: inline; }
  .sidebar.collapsed .nav-label { display: inline; }
  .sidebar.collapsed .nav-section { display: block; }
  .sidebar.collapsed .user-info { display: block; }
  .sidebar.collapsed .sidebar-logo,
  .sidebar.collapsed .user-row,
  .sidebar.collapsed .nav-item,
  .sidebar.collapsed .logout-btn {
    justify-content: flex-start;
  }
  .sidebar.open {
    transform: translateX(0);
  }

  .sidebar-overlay.open {
    display: block;
  }

  .main-content {
    padding-top: 0;
  }
}
</style>
