import { ref } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

export function useProyectos() {
    const auth = useAuthStore()
    const projects = ref([])
    const employees = ref([])
    const loading = ref(true)
    const saving = ref(false)
    const showModal = ref(false)
    const editing = ref(null)
    const formErrors = ref({})
    const formError = ref('')

    const form = ref(emptyForm())

    function emptyForm() {
        return {
            title: '', description: '',
            start_date: '', end_date: '',
            status: 'in_progress', member_ids: [],
        }
    }

    const statusBadge = (status) => ({
        in_progress: 'badge badge-blue',
        completed: 'badge badge-green',
    })[status] ?? 'badge badge-gray'

    const statusLabel = (status) => ({
        in_progress: 'En progreso',
        completed: 'Completado',
    })[status] ?? status

    const formatDate = (d) => d
        ? new Date(d).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' })
        : '—'

    function initials(name) {
        return name?.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) ?? '?'
    }

    async function fetchProjects() {
        loading.value = true
        try {
            const { data } = await api.get('/projects')
            projects.value = data
        } finally {
            loading.value = false
        }
    }

    async function fetchEmployees() {
        if (!auth.isAdmin) return
        const { data } = await api.get('/admin/employees')
        employees.value = data
    }

    function openCreate() {
        editing.value = null
        form.value = emptyForm()
        formErrors.value = {}
        formError.value = ''
        showModal.value = true
    }

    function openEdit(project) {
        editing.value = project
        form.value = {
            title: project.title,
            description: project.description ?? '',
            start_date: project.start_date,
            end_date: project.end_date,
            status: project.status,
            member_ids: project.members.map(m => m.id),
        }
        formErrors.value = {}
        formError.value = ''
        showModal.value = true
    }

    function closeModal() {
        showModal.value = false
        editing.value = null
        form.value = emptyForm()
    }

    function toggleMember(id) {
        const idx = form.value.member_ids.indexOf(id)
        if (idx === -1) form.value.member_ids.push(id)
        else form.value.member_ids.splice(idx, 1)
    }

    async function saveProject() {
        formErrors.value = {}
        formError.value = ''
        saving.value = true
        try {
            if (editing.value) {
                const { data } = await api.put(`/projects/${editing.value.id}`, form.value)
                const idx = projects.value.findIndex(p => p.id === editing.value.id)
                if (idx !== -1) projects.value[idx] = data.project
            } else {
                const { data } = await api.post('/projects', form.value)
                projects.value.unshift(data.project)
            }
            closeModal()
        } catch (e) {
            const errs = e.response?.data?.errors
            if (errs) formErrors.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, v[0]]))
            else formError.value = e.response?.data?.message || 'Error al guardar.'
        } finally {
            saving.value = false
        }
    }

    async function deleteProject(project) {
        if (!confirm(`Eliminar "${project.title}"?`)) return
        try {
            await api.delete(`/projects/${project.id}`)
            projects.value = projects.value.filter(p => p.id !== project.id)
        } catch {
            alert('Error al eliminar el proyecto.')
        }
    }

    return {
        auth, projects, employees, loading, saving,
        showModal, editing, form, formErrors, formError,
        statusBadge, statusLabel, formatDate, initials,
        fetchProjects, fetchEmployees,
        openCreate, openEdit, closeModal,
        toggleMember, saveProject, deleteProject,
    }
}