<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    faculties:   { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    programs:    { type: Array, default: () => [] },
    user:        { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

// Tab from URL query
const urlParams  = new URLSearchParams(window.location.search);
const activeTab  = ref(urlParams.get('tab') ?? 'faculties');

// ── Forms ─────────────────────────────────────────────────────
const facultyForm = useForm({ name: '', code: '' });
const deptForm    = useForm({ name: '', code: '', faculty_id: '' });
const programForm = useForm({
    name: '', code: '', department_id: '',
    level: 'degree', duration_years: 3,
});

const submitFaculty = () => {
    facultyForm.post(route('admin.faculties.store'), {
        onSuccess: () => facultyForm.reset(),
    });
};

const submitDept = () => {
    deptForm.post(route('admin.departments.store'), {
        onSuccess: () => deptForm.reset(),
    });
};

const submitProgram = () => {
    programForm.post(route('admin.programs.store'), {
        onSuccess: () => programForm.reset(),
    });
};

// Helpers
const getFacultyName = (id) => props.faculties.find(f => f.id == id)?.name ?? '—';
const getDeptName    = (id) => props.departments.find(d => d.id == id)?.name ?? '—';
const getFacultyForDept = (deptId) => {
    const dept = props.departments.find(d => d.id == deptId);
    return dept ? getFacultyName(dept.faculty_id) : '—';
};

const levels = ['certificate','diploma','degree','masters','phd'];
</script>

<template>
    <AdminLayout>
        <Head title="Manage Data" />
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Manage Academic Structure</h2>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <!-- Flash -->
            <transition enter-active-class="transition duration-300" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium flex items-center gap-2">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ flash.success }}
                </div>
            </transition>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-0">
                    <button
                        v-for="tab in [
                            { key: 'faculties',   label: 'Faculties',   count: faculties.length },
                            { key: 'departments', label: 'Departments', count: departments.length },
                            { key: 'programs',    label: 'Programs',    count: programs.length },
                        ]"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        class="flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-medium transition"
                        :class="activeTab === tab.key
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                    >
                        {{ tab.label }}
                        <span class="rounded-full px-1.5 py-0.5 text-xs font-bold"
                            :class="activeTab === tab.key ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'">
                            {{ tab.count }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ── FACULTIES TAB ──────────────────────────────── -->
            <div v-if="activeTab === 'faculties'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Add form -->
                <div class="lg:col-span-1">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 sticky top-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded bg-indigo-100">
                                <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </div>
                            Add Faculty
                        </h3>
                        <form @submit.prevent="submitFaculty" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Faculty Name *</label>
                                <input v-model="facultyForm.name" type="text" placeholder="e.g. Faculty of Engineering"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required/>
                                <p v-if="facultyForm.errors.name" class="mt-1 text-xs text-red-500">{{ facultyForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Code *</label>
                                <input v-model="facultyForm.code" type="text" placeholder="e.g. FITE"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required/>
                                <p v-if="facultyForm.errors.code" class="mt-1 text-xs text-red-500">{{ facultyForm.errors.code }}</p>
                            </div>
                            <button type="submit" :disabled="facultyForm.processing"
                                class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                {{ facultyForm.processing ? 'Adding...' : 'Add Faculty' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List -->
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">All Faculties ({{ faculties.length }})</h3>
                        </div>
                        <div v-if="faculties.length === 0" class="px-5 py-10 text-center text-sm text-gray-400">
                            No faculties yet. Add one to get started.
                        </div>
                        <table v-else class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead><tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Depts</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(f, i) in faculties" :key="f.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ f.name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-indigo-600 bg-indigo-50 rounded">{{ f.code }}</td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">
                                        {{ departments.filter(d => d.faculty_id == f.id).length }} depts
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="f.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'">
                                            {{ f.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── DEPARTMENTS TAB ────────────────────────────── -->
            <div v-if="activeTab === 'departments'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-1">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 sticky top-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded bg-purple-100">
                                <svg class="h-3.5 w-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </div>
                            Add Department
                        </h3>
                        <form @submit.prevent="submitDept" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Faculty *</label>
                                <select v-model="deptForm.faculty_id"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 bg-white" required>
                                    <option value="">Select faculty...</option>
                                    <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }} ({{ f.code }})</option>
                                </select>
                                <p v-if="deptForm.errors.faculty_id" class="mt-1 text-xs text-red-500">{{ deptForm.errors.faculty_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Department Name *</label>
                                <input v-model="deptForm.name" type="text" placeholder="e.g. Computer Science"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500" required/>
                                <p v-if="deptForm.errors.name" class="mt-1 text-xs text-red-500">{{ deptForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Code *</label>
                                <input v-model="deptForm.code" type="text" placeholder="e.g. CS"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase focus:border-purple-500 focus:ring-1 focus:ring-purple-500" required/>
                                <p v-if="deptForm.errors.code" class="mt-1 text-xs text-red-500">{{ deptForm.errors.code }}</p>
                            </div>
                            <button type="submit" :disabled="deptForm.processing"
                                class="w-full rounded-lg bg-purple-600 py-2.5 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-50">
                                {{ deptForm.processing ? 'Adding...' : 'Add Department' }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">All Departments ({{ departments.length }})</h3>
                        </div>
                        <div v-if="departments.length === 0" class="px-5 py-10 text-center text-sm text-gray-400">
                            No departments yet.
                        </div>
                        <table v-else class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead><tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Faculty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Programs</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(d, i) in departments" :key="d.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ d.name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-purple-600">{{ d.code }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ getFacultyName(d.faculty_id) }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ programs.filter(p => p.department_id == d.id).length }} programs
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── PROGRAMS TAB ───────────────────────────────── -->
            <div v-if="activeTab === 'programs'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-1">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 sticky top-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded bg-teal-100">
                                <svg class="h-3.5 w-3.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </div>
                            Add Program
                        </h3>
                        <form @submit.prevent="submitProgram" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Department *</label>
                                <select v-model="programForm.department_id"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 bg-white" required>
                                    <option value="">Select department...</option>
                                    <optgroup v-for="f in faculties" :key="f.id" :label="f.name">
                                        <option
                                            v-for="d in departments.filter(dep => dep.faculty_id == f.id)"
                                            :key="d.id" :value="d.id">
                                            {{ d.name }} ({{ d.code }})
                                        </option>
                                    </optgroup>
                                </select>
                                <p v-if="programForm.errors.department_id" class="mt-1 text-xs text-red-500">{{ programForm.errors.department_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Program Name *</label>
                                <input v-model="programForm.name" type="text" placeholder="e.g. Bachelor of IT"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500" required/>
                                <p v-if="programForm.errors.name" class="mt-1 text-xs text-red-500">{{ programForm.errors.name }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Code *</label>
                                    <input v-model="programForm.code" type="text" placeholder="e.g. BIT"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase focus:border-teal-500 focus:ring-1 focus:ring-teal-500" required/>
                                    <p v-if="programForm.errors.code" class="mt-1 text-xs text-red-500">{{ programForm.errors.code }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Duration (yrs) *</label>
                                    <input v-model="programForm.duration_years" type="number" min="1" max="7"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500" required/>
                                    <p v-if="programForm.errors.duration_years" class="mt-1 text-xs text-red-500">{{ programForm.errors.duration_years }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Level *</label>
                                <select v-model="programForm.level"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 bg-white capitalize" required>
                                    <option v-for="lv in levels" :key="lv" :value="lv" class="capitalize">{{ lv }}</option>
                                </select>
                                <p v-if="programForm.errors.level" class="mt-1 text-xs text-red-500">{{ programForm.errors.level }}</p>
                            </div>
                            <button type="submit" :disabled="programForm.processing"
                                class="w-full rounded-lg bg-teal-600 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50">
                                {{ programForm.processing ? 'Adding...' : 'Add Program' }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">All Programs ({{ programs.length }})</h3>
                        </div>
                        <div v-if="programs.length === 0" class="px-5 py-10 text-center text-sm text-gray-400">
                            No programs yet.
                        </div>
                        <table v-else class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead><tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Level</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dept</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Duration</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(p, i) in programs" :key="p.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ p.name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-teal-600">{{ p.code }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 capitalize">{{ p.level }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ getDeptName(p.department_id) }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ p.duration_display ?? p.duration_years + 'yr' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>