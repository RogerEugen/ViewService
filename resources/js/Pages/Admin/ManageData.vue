<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    faculties:   { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    programs:    { type: Array, default: () => [] },
    categories:  { type: Array, default: () => [] },
    user:        { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const urlParams = new URLSearchParams(window.location.search);
const activeTab = ref(urlParams.get('tab') ?? 'faculties');

// ── Forms ─────────────────────────────────────────────────────
const facultyForm = useForm({ name: '', code: '' });
const deptForm    = useForm({ name: '', code: '', faculty_id: '' });
const programForm = useForm({
    name: '', code: '', department_id: '',
    level: 'degree', duration_years: 3,
});
const categoryForm = useForm({
    name: '', routes_to: 'hod', sender_role: 'student', description: '',
});
const editCategoryForm = useForm({
    id: '', name: '', routes_to: 'hod', sender_role: 'student', description: '', is_active: true,
});

// ── HOD form ──────────────────────────────────────────────────
const hodForm = useForm({
    first_name:    '',
    last_name:     '',
    email:         '',
    phone:         '',
    staff_number:  '',
    title:         'Dr',
    gender:        'Male',
    specialization:'',
    action:        'assign',
});

const deanForm = useForm({
    first_name:    '',
    last_name:     '',
    email:         '',
    phone:         '',
    staff_number:  '',
    title:         'Dr',
    gender:        'Male',
    specialization:'',
    action:        'assign',
});

const selectedDept    = ref(null);
const showHodModal    = ref(false);
const showSuccess     = ref(false);
const createdHodInfo  = ref(null);
const selectedFaculty = ref(null);
const showDeanModal   = ref(false);

const openHodModal = (dept, action = 'assign') => {
    selectedDept.value  = dept;
    hodForm.action      = action;
    hodForm.specialization = dept.name;
    showHodModal.value  = true;
};

const closeHodModal = () => {
    showHodModal.value = false;
    selectedDept.value = null;
    hodForm.reset();
};

const submitHod = () => {
    hodForm.post(route('admin.departments.hod.store', selectedDept.value.id), {
        onSuccess: () => {
            closeHodModal();
        },
    });
};

const openDeanModal = (faculty, action = 'assign') => {
    selectedFaculty.value = faculty;
    deanForm.action = action;
    deanForm.specialization = faculty.name;
    showDeanModal.value = true;
};

const closeDeanModal = () => {
    showDeanModal.value = false;
    selectedFaculty.value = null;
    deanForm.reset();
};

const submitDean = () => {
    deanForm.post(route('admin.faculties.dean.store', selectedFaculty.value.id), {
        onSuccess: () => closeDeanModal(),
    });
};

// ── Submit helpers ────────────────────────────────────────────
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
const submitCategory = () => {
    categoryForm.post(route('admin.categories.store'), {
        onSuccess: () => categoryForm.reset('name', 'description'),
    });
};
const openEditCategory = (cat) => {
    editCategoryForm.id = cat.id;
    editCategoryForm.name = cat.name;
    editCategoryForm.routes_to = cat.routes_to;
    editCategoryForm.sender_role = cat.sender_role;
    editCategoryForm.description = cat.description ?? '';
    editCategoryForm.is_active = !!cat.is_active;
};
const updateCategory = () => {
    editCategoryForm.put(route('admin.categories.update', editCategoryForm.id));
};
const deleteCategory = (id) => {
    if (confirm('Delete/deactivate this category?')) {
        router.delete(route('admin.categories.delete', id));
    }
};

// ── Helpers ───────────────────────────────────────────────────
const getFacultyName  = (id) => props.faculties.find(f => f.id == id)?.name ?? '—';
const getDeptName     = (id) => props.departments.find(d => d.id == id)?.name ?? '—';

const levels = ['certificate', 'diploma', 'degree', 'bachelors', 'masters', 'phd'];

// Departments without HOD
const deptsWithoutHod    = computed(() => props.departments.filter(d => !d.hod_user_id));
const deptsWithHod       = computed(() => props.departments.filter(d => d.hod_user_id));
const facultiesWithoutDean = computed(() => props.faculties.filter(f => !f.dean_user_id));
const facultiesWithDean    = computed(() => props.faculties.filter(f => f.dean_user_id));
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
                <div v-if="flash.success"
                    class="rounded-xl bg-green-50 border border-green-200 px-4 py-4 flex items-start gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-green-800">{{ flash.success }}</p>
                    </div>
                </div>
            </transition>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-0 overflow-x-auto">
                    <button
                        v-for="tab in [
                            { key: 'faculties',   label: 'Faculties',   count: faculties.length },
                            { key: 'departments', label: 'Departments', count: departments.length },
                            { key: 'programs',    label: 'Programs',    count: programs.length },
                            { key: 'categories',  label: 'Feedback Categories', count: categories.length },
                            { key: 'hods',        label: 'HOD Management', count: deptsWithoutHod.length, badge: true },
                            { key: 'deans',       label: 'Dean Management', count: facultiesWithoutDean.length, badge: true },
                        ]"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        class="flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-medium transition whitespace-nowrap"
                        :class="activeTab === tab.key
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                    >
                        {{ tab.label }}
                        <span class="rounded-full px-1.5 py-0.5 text-xs font-bold"
                            :class="[
                                activeTab === tab.key ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500',
                                tab.badge && tab.count > 0 ? 'bg-red-100 text-red-600' : ''
                            ]">
                            {{ tab.count }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ── FACULTIES TAB ──────────────────────────────── -->
            <div v-if="activeTab === 'faculties'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                            </div>
                            <button type="submit" :disabled="facultyForm.processing"
                                class="w-full rounded-lg bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                {{ facultyForm.processing ? 'Adding...' : 'Add Faculty' }}
                            </button>
                        </form>
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">All Faculties ({{ faculties.length }})</h3>
                        </div>
                        <div v-if="faculties.length === 0" class="px-5 py-10 text-center text-sm text-gray-400">No faculties yet.</div>
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
                                    <td class="px-4 py-3"><span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ f.code }}</span></td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ departments.filter(d => d.faculty_id == f.id).length }} depts</td>
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
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-purple-500 bg-white" required>
                                    <option value="">Select faculty...</option>
                                    <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }} ({{ f.code }})</option>
                                </select>
                                <p v-if="deptForm.errors.faculty_id" class="mt-1 text-xs text-red-500">{{ deptForm.errors.faculty_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Department Name *</label>
                                <input v-model="deptForm.name" type="text" placeholder="e.g. Computer Science"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-purple-500" required/>
                                <p v-if="deptForm.errors.name" class="mt-1 text-xs text-red-500">{{ deptForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Code *</label>
                                <input v-model="deptForm.code" type="text" placeholder="e.g. CCT"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase focus:border-purple-500" required/>
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
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead><tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Faculty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">HOD</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Programs</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="departments.length === 0">
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">No departments yet.</td>
                                </tr>
                                <tr v-for="(d, i) in departments" :key="d.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ d.name }}</td>
                                    <td class="px-4 py-3"><span class="font-mono text-xs font-bold text-purple-600">{{ d.code }}</span></td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ getFacultyName(d.faculty_id) }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        <span v-if="d.hod_name" class="text-green-700 font-medium">{{ d.hod_name }}</span>
                                        <span v-else class="text-red-500 font-medium">Not assigned</span>
                                    </td>
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
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 bg-white" required>
                                    <option value="">Select department...</option>
                                    <optgroup v-for="f in faculties" :key="f.id" :label="f.name">
                                        <option v-for="d in departments.filter(dep => dep.faculty_id == f.id)" :key="d.id" :value="d.id">
                                            {{ d.name }} ({{ d.code }})
                                        </option>
                                    </optgroup>
                                </select>
                                <p v-if="programForm.errors.department_id" class="mt-1 text-xs text-red-500">{{ programForm.errors.department_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Program Name *</label>
                                <input v-model="programForm.name" type="text" placeholder="e.g. Bachelor of IT"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500" required/>
                                <p v-if="programForm.errors.name" class="mt-1 text-xs text-red-500">{{ programForm.errors.name }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Code *</label>
                                    <input v-model="programForm.code" type="text" placeholder="e.g. BIT"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase focus:border-teal-500" required/>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Duration (yrs) *</label>
                                    <input v-model="programForm.duration_years" type="number" min="1" max="7"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500" required/>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Level *</label>
                                <select v-model="programForm.level"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-teal-500 bg-white capitalize" required>
                                    <option value="">Select level...</option>
                                    <option value="certificate">Certificate</option>
                                    <option value="diploma">Diploma</option>
                                    <option value="bachelors">Degree (Bachelors)</option>
                                    <option value="masters">Masters</option>
                                    <option value="phd">PhD</option>
                                </select>
                                <p v-if="programForm.errors.level" class="mt-1 text-xs text-red-500">{{ programForm.errors.level }}</p>
                                <p v-if="programForm.errors.name" class="mt-1 text-xs text-red-500">{{ programForm.errors.name }}</p>
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
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead><tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Level</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dept</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Duration</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="programs.length === 0">
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">No programs yet.</td>
                                </tr>
                                <tr v-for="(p, i) in programs" :key="p.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 text-xs">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ p.name }}</td>
                                    <td class="px-4 py-3"><span class="font-mono text-xs font-bold text-teal-600">{{ p.code }}</span></td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 capitalize">{{ p.level }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ getDeptName(p.department_id) }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ p.duration_display ?? p.duration_years + ' yr' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'categories'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Create Feedback Category</h3>
                    <form @submit.prevent="submitCategory" class="space-y-3">
                        <input v-model="categoryForm.name" type="text" placeholder="Category name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" required />
                        <div class="grid grid-cols-2 gap-3">
                            <select v-model="categoryForm.sender_role" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white">
                                <option value="student">Student</option>
                                <option value="lecturer">Lecturer</option>
                                <option value="any">Any</option>
                            </select>
                            <select v-model="categoryForm.routes_to" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white">
                                <option value="hod">HOD</option>
                                <option value="dean">Dean</option>
                                <option value="rector">Rector</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <textarea v-model="categoryForm.description" rows="3" placeholder="Description (optional)"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"></textarea>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save Category</button>
                    </form>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Manage Categories</h3>
                    <div class="space-y-3 max-h-[520px] overflow-y-auto pr-1">
                        <div v-for="cat in categories" :key="cat.id" class="rounded-lg border border-gray-200 p-3 space-y-2">
                            <input v-model="editCategoryForm.name" v-if="editCategoryForm.id === cat.id" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
                            <p v-else class="text-sm font-semibold text-gray-800">{{ cat.name }}</p>
                            <p class="text-xs text-gray-500">{{ cat.description || 'No description' }}</p>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5">{{ cat.sender_role }}</span>
                                <span class="rounded-full bg-blue-100 text-blue-700 px-2 py-0.5">{{ cat.routes_to }}</span>
                                <span class="rounded-full px-2 py-0.5" :class="cat.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                                    {{ cat.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600">
                                From: <span class="font-semibold capitalize">{{ cat.sender_role }}</span>
                                →
                                To: <span class="font-semibold uppercase">{{ cat.routes_to }}</span>
                            </p>
                            <div class="flex gap-2">
                                <button v-if="editCategoryForm.id !== cat.id" @click="openEditCategory(cat)" class="rounded border border-gray-200 px-2 py-1 text-xs">Edit</button>
                                <template v-else>
                                    <button @click="updateCategory" class="rounded bg-indigo-600 text-white px-2 py-1 text-xs">Update</button>
                                    <button @click="editCategoryForm.id=''" class="rounded border border-gray-200 px-2 py-1 text-xs">Cancel</button>
                                </template>
                                <button @click="deleteCategory(cat.id)" class="rounded border border-red-200 text-red-600 px-2 py-1 text-xs">Delete/Deactivate</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── HOD MANAGEMENT TAB ─────────────────────────── -->
            <div v-if="activeTab === 'hods'" class="space-y-6">

                <!-- Info -->
                <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 flex gap-2 items-start">
                    <svg class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-xs text-blue-700">
                        <p class="font-semibold mb-0.5">HOD Account Creation</p>
                        <p>When you create an HOD, a user account is automatically created with their <strong>last name as the temporary password</strong>. They must change it on first login. The previous HOD (if any) will be deactivated.</p>
                    </div>
                </div>

                <!-- Departments without HOD -->
                <div v-if="deptsWithoutHod.length > 0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-2 w-2 rounded-full bg-red-500"></div>
                        <h3 class="text-sm font-semibold text-gray-700">
                            Departments Without HOD ({{ deptsWithoutHod.length }})
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="d in deptsWithoutHod" :key="d.id"
                            class="rounded-xl border-2 border-dashed border-red-200 bg-red-50 p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ d.name }}</p>
                                    <p class="text-xs font-mono text-gray-400">{{ d.code }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ getFacultyName(d.faculty_id) }}</p>
                                </div>
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600">
                                    No HOD
                                </span>
                            </div>
                            <button
                                @click="openHodModal(d, 'assign')"
                                class="w-full rounded-lg bg-indigo-600 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                            >
                                + Assign HOD
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Departments with HOD -->
                <div v-if="deptsWithHod.length > 0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                        <h3 class="text-sm font-semibold text-gray-700">
                            Departments With HOD ({{ deptsWithHod.length }})
                        </h3>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Department</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Faculty</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Current HOD</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">HOD Email</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="d in deptsWithHod" :key="d.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900 text-sm">{{ d.name }}</p>
                                        <p class="font-mono text-xs text-gray-400">{{ d.code }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ getFacultyName(d.faculty_id) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-green-700 text-sm">{{ d.hod_name }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ d.hod_email }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            @click="openHodModal(d, 'replace')"
                                            class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-100"
                                        >
                                            Replace HOD
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="departments.length === 0" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                    <p class="text-sm text-gray-400">No departments found. Create departments first.</p>
                    <button @click="activeTab = 'departments'" class="mt-3 text-sm text-indigo-600 font-medium">Go to Departments →</button>
                </div>

            </div>

            <!-- ── DEAN MANAGEMENT TAB ─────────────────────────── -->
            <div v-if="activeTab === 'deans'" class="space-y-6">
                <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-4 py-3 flex gap-2 items-start">
                    <svg class="h-4 w-4 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-xs text-indigo-700">
                        <p class="font-semibold mb-0.5">Dean Account Creation</p>
                        <p>When you create a Dean, account is created with <strong>last name as temporary password</strong>. First login requires password change.</p>
                    </div>
                </div>

                <div v-if="facultiesWithoutDean.length > 0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-2 w-2 rounded-full bg-red-500"></div>
                        <h3 class="text-sm font-semibold text-gray-700">
                            Faculties Without Dean ({{ facultiesWithoutDean.length }})
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="f in facultiesWithoutDean" :key="f.id"
                            class="rounded-xl border-2 border-dashed border-red-200 bg-red-50 p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ f.name }}</p>
                                    <p class="text-xs font-mono text-gray-400">{{ f.code }}</p>
                                </div>
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600">
                                    No Dean
                                </span>
                            </div>
                            <button
                                @click="openDeanModal(f, 'assign')"
                                class="w-full rounded-lg bg-indigo-600 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                            >
                                + Assign Dean
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="facultiesWithDean.length > 0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                        <h3 class="text-sm font-semibold text-gray-700">
                            Faculties With Dean ({{ facultiesWithDean.length }})
                        </h3>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Faculty</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Current Dean</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Dean Email</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="f in facultiesWithDean" :key="f.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900 text-sm">{{ f.name }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ f.code }}</td>
                                    <td class="px-4 py-3"><span class="font-semibold text-green-700 text-sm">{{ f.dean_name }}</span></td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ f.dean_email }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            @click="openDeanModal(f, 'replace')"
                                            class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-100"
                                        >
                                            Replace Dean
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>

    <!-- ── HOD Creation Modal ─────────────────────────────────── -->
    <div v-if="showHodModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden">

            <!-- Modal header -->
            <div class="border-b border-gray-100 px-6 py-4 bg-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ hodForm.action === 'replace' ? 'Replace HOD' : 'Assign HOD' }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Department: <strong>{{ selectedDept?.name }}</strong>
                        <span v-if="hodForm.action === 'replace'" class="ml-1 text-orange-600">
                            — Current HOD ({{ selectedDept?.hod_name }}) will be deactivated
                        </span>
                    </p>
                </div>
                <button @click="closeHodModal"
                    class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-100">
                    ✕
                </button>
            </div>

            <!-- Warn if replacing -->
            <div v-if="hodForm.action === 'replace'"
                class="mx-6 mt-4 rounded-lg bg-orange-50 border border-orange-200 px-3 py-2.5 text-xs text-orange-700">
                ⚠️ Replacing the HOD will deactivate <strong>{{ selectedDept?.hod_name }}</strong>'s account and demote them to lecturer role.
            </div>

            <!-- Form -->
            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

                <!-- ✅ Show any error returned -->
                <div v-if="Object.keys(hodForm.errors).length > 0"
                    class="rounded-lg bg-red-50 border border-red-200 px-3 py-3">
                    <p class="text-xs font-semibold text-red-700 mb-1">Please fix the following:</p>
                    <ul class="space-y-0.5">
                        <li v-for="(err, field) in hodForm.errors" :key="field" class="text-xs text-red-600">
                            • {{ err }}
                        </li>
                    </ul>
                </div>



                <!-- Name row -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">First Name *</label>
                        <input v-model="hodForm.first_name" type="text" placeholder="e.g. John"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required/>
                        <p v-if="hodForm.errors.first_name" class="mt-1 text-xs text-red-500">{{ hodForm.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Last Name *</label>
                        <input v-model="hodForm.last_name" type="text" placeholder="e.g. Kimaro"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required/>
                        <p v-if="hodForm.errors.last_name" class="mt-1 text-xs text-red-500">{{ hodForm.errors.last_name }}</p>
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Email Address *</label>
                    <input v-model="hodForm.email" type="email" placeholder="hod.department@college.ac.tz"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required/>
                    <p v-if="hodForm.errors.email" class="mt-1 text-xs text-red-500">{{ hodForm.errors.email }}</p>
                </div>

                <!-- Phone + Staff Number -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Phone</label>
                        <input v-model="hodForm.phone" type="text" placeholder="07XXXXXXXX"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Staff Number</label>
                        <input v-model="hodForm.staff_number" type="text" placeholder="NIT/HOD/XXX"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 font-mono"/>
                    </div>
                </div>

                <!-- Title + Gender -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Title</label>
                        <select v-model="hodForm.title"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 bg-white">

                            <option value="Dr">Dr.</option>
                            <option value="Prof">Prof.</option>
                            <option value="Mr">Mr.</option>
                            <option value="Ms">Ms.</option>
                            <option value="Mrs">Mrs.</option>
                            <option value="Eng">Eng.</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Gender</label>
                        <select v-model="hodForm.gender"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 bg-white">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <!-- Specialization -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Specialization</label>
                    <input v-model="hodForm.specialization" type="text" placeholder="e.g. Computer Science"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500"/>
                </div>

                <!-- Password notice -->
                <div class="rounded-lg bg-yellow-50 border border-yellow-200 px-3 py-2.5">
                    <p class="text-xs text-yellow-700">
                        <strong>Temporary Password:</strong> The HOD's last name will be set as their initial password.
                        They will be required to change it on first login.
                    </p>
                    <p v-if="hodForm.last_name" class="mt-1 text-xs font-mono font-bold text-yellow-800">
                        Password will be: "{{ hodForm.last_name }}"
                    </p>
                </div>

            </div>

            <!-- Actions -->
            <div class="border-t border-gray-100 px-6 py-4 flex gap-3 bg-gray-50">
                <button
                    @click="submitHod"
                    :disabled="hodForm.processing || !hodForm.first_name || !hodForm.last_name || !hodForm.email"
                    class="flex-1 rounded-xl py-3 text-sm font-bold text-white transition disabled:opacity-50"
                    :class="hodForm.action === 'replace'
                        ? 'bg-orange-500 hover:bg-orange-600'
                        : 'bg-indigo-600 hover:bg-indigo-700'"
                >
                    <span v-if="hodForm.processing" class="flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Creating HOD account...
                    </span>
                    <span v-else>
                        {{ hodForm.action === 'replace' ? '⚠️ Replace HOD' : '✓ Create HOD Account' }}
                    </span>
                </button>
                <button @click="closeHodModal"
                    class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100">
                    Cancel
                </button>
            </div>

        </div>
    </div>

    <!-- ── DEAN Creation Modal ────────────────────────────────── -->
    <div v-if="showDeanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden">
            <div class="border-b border-gray-100 px-6 py-4 bg-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ deanForm.action === 'replace' ? 'Replace Dean' : 'Assign Dean' }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Faculty: <strong>{{ selectedFaculty?.name }}</strong>
                    </p>
                </div>
                <button @click="closeDeanModal"
                    class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-100">
                    ✕
                </button>
            </div>

            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div v-if="Object.keys(deanForm.errors).length > 0"
                    class="rounded-lg bg-red-50 border border-red-200 px-3 py-3">
                    <p class="text-xs font-semibold text-red-700 mb-1">Please fix the following:</p>
                    <ul class="space-y-0.5">
                        <li v-for="(err, field) in deanForm.errors" :key="field" class="text-xs text-red-600">
                            • {{ err }}
                        </li>
                    </ul>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">First Name *</label>
                        <input v-model="deanForm.first_name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" required/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Last Name *</label>
                        <input v-model="deanForm.last_name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" required/>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Email Address *</label>
                    <input v-model="deanForm.email" type="email" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm" required/>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Phone</label>
                        <input v-model="deanForm.phone" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Staff Number</label>
                        <input v-model="deanForm.staff_number" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono"/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Title</label>
                        <select v-model="deanForm.title" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white">
                            <option value="Dr">Dr.</option>
                            <option value="Prof">Prof.</option>
                            <option value="Mr">Mr.</option>
                            <option value="Ms">Ms.</option>
                            <option value="Mrs">Mrs.</option>
                            <option value="Eng">Eng.</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Gender</label>
                        <select v-model="deanForm.gender" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Specialization</label>
                    <input v-model="deanForm.specialization" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"/>
                </div>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 flex gap-3 bg-gray-50">
                <button
                    @click="submitDean"
                    :disabled="deanForm.processing || !deanForm.first_name || !deanForm.last_name || !deanForm.email"
                    class="flex-1 rounded-xl py-3 text-sm font-bold text-white transition disabled:opacity-50"
                    :class="deanForm.action === 'replace' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-indigo-600 hover:bg-indigo-700'"
                >
                    {{ deanForm.action === 'replace' ? 'Replace Dean' : 'Create Dean Account' }}
                </button>
                <button @click="closeDeanModal"
                    class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100">
                    Cancel
                </button>
            </div>
        </div>
    </div>

</template>
