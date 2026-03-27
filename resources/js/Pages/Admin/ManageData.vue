<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
// import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();


const props = defineProps({
    faculties:   { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    programs:    { type: Array, default: () => [] },
});

// ── Active tab ──────────────────────────────────
const activeTab = ref('faculties');

// ── Faculty form ────────────────────────────────
const showFacultyForm = ref(false);
const facultyForm = useForm({
    name: '', code: '', is_active: true,
});
const submitFaculty = () => {
    facultyForm.post('/admin/faculties', {
        onSuccess: () => { facultyForm.reset(); showFacultyForm.value = false; },
    });
};

// ── Department form ─────────────────────────────
const showDeptForm = ref(false);
const deptForm = useForm({
    faculty_id: '', name: '', code: '', is_active: true,
});
const submitDept = () => {
    deptForm.post('/admin/departments', {
        onSuccess: () => { deptForm.reset(); showDeptForm.value = false; },
    });
};

// ── Program form ────────────────────────────────
const showProgForm = ref(false);
const progForm = useForm({
    department_id: '', name: '', code: '',
    level: '', duration_years: '', duration_display: '', is_active: true,
});

const deptsByFaculty = computed(() =>
    props.departments.filter(d => d.faculty_id == deptForm.faculty_id)
);
const deptsBySelected = computed(() =>
    props.departments.filter(d => true)
);

const submitProgram = () => {
    progForm.post('/admin/programs', {
        onSuccess: () => { progForm.reset(); showProgForm.value = false; },
    });
};

const levels = [
    { value: 'basic_certificate',    label: 'Basic Technician Certificate' },
    { value: 'certificate',          label: 'Technician Certificate' },
    { value: 'diploma',              label: 'Ordinary Diploma' },
    { value: 'higher_diploma',       label: 'Higher Diploma' },
    { value: 'postgraduate_diploma', label: 'Postgraduate Diploma' },
    { value: 'bachelors',            label: "Bachelor's Degree" },
    { value: 'masters',              label: "Master's Degree" },
    { value: 'phd',                  label: 'PhD' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Manage Institution Data" />

        <div class="py-8 px-6 max-w-7xl mx-auto">

            <!-- Page header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Institution Structure
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage faculties, departments and programs
                    </p>
                </div>
            </div>

            <div v-if="page.props.flash.success" class="mb-4 rounded-lg bg-green-100 px-4 py-3 text-green-700">
                {{ page.props.flash.success }}
            </div>

            <div v-if="page.props.flash.error" class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-red-700">
                {{ page.props.flash.error }}
            </div>



            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex gap-6">
                    <button
                        v-for="tab in ['faculties','departments','programs']"
                        :key="tab"
                        @click="activeTab = tab"
                        class="pb-3 text-sm font-medium capitalize border-b-2 transition"
                        :class="activeTab === tab
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                    >
                        {{ tab }}
                        <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                            {{
                                tab === 'faculties'   ? faculties.length   :
                                tab === 'departments' ? departments.length :
                                programs.length
                            }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ── FACULTIES TAB ── -->
            <div v-if="activeTab === 'faculties'">
                <div class="mb-4 flex justify-end">
                    <button
                        @click="showFacultyForm = !showFacultyForm"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        <span>+ Add Faculty</span>
                    </button>
                </div>

                <!-- Add Faculty Form -->
                <div v-if="showFacultyForm" class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50 p-5">
                    <h3 class="mb-4 text-sm font-semibold text-indigo-800">New Faculty</h3>
                    <form @submit.prevent="submitFaculty" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Faculty Name</label>
                            <input
                                v-model="facultyForm.name"
                                type="text"
                                placeholder="e.g. Faculty of Aviation Technology"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            <p v-if="facultyForm.errors.name" class="mt-1 text-xs text-red-500">{{ facultyForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Code</label>
                            <input
                                v-model="facultyForm.code"
                                type="text"
                                placeholder="e.g. FAT"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                required
                            />
                            <p v-if="facultyForm.errors.code" class="mt-1 text-xs text-red-500">{{ facultyForm.errors.code }}</p>
                        </div>
                        <div class="flex items-end gap-3">
                            <button
                                type="submit"
                                :disabled="facultyForm.processing"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ facultyForm.processing ? 'Saving...' : 'Save Faculty' }}
                            </button>
                            <button type="button" @click="showFacultyForm = false" class="text-sm text-gray-500 hover:text-gray-700">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Faculties Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Faculty Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Code</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Departments</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="faculties.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">No faculties yet. Add one above.</td>
                            </tr>
                            <tr v-for="(f, i) in faculties" :key="f.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400">{{ i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ f.name }}</td>
                                <td class="px-4 py-3"><span class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">{{ f.code }}</span></td>
                                <td class="px-4 py-3 text-gray-600">{{ f.departments_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="f.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                        {{ f.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── DEPARTMENTS TAB ── -->
            <div v-if="activeTab === 'departments'">
                <div class="mb-4 flex justify-end">
                    <button
                        @click="showDeptForm = !showDeptForm"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        + Add Department
                    </button>
                </div>

                <!-- Add Department Form -->
                <div v-if="showDeptForm" class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50 p-5">
                    <h3 class="mb-4 text-sm font-semibold text-indigo-800">New Department</h3>
                    <form @submit.prevent="submitDept" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Faculty</label>
                            <select v-model="deptForm.faculty_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                                <option value="">Select faculty</option>
                                <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                            <p v-if="deptForm.errors.faculty_id" class="mt-1 text-xs text-red-500">{{ deptForm.errors.faculty_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Department Name</label>
                            <input v-model="deptForm.name" type="text" placeholder="e.g. Computing and Communication Technology" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required />
                            <p v-if="deptForm.errors.name" class="mt-1 text-xs text-red-500">{{ deptForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Code</label>
                            <input v-model="deptForm.code" type="text" placeholder="e.g. CCT" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase" required />
                            <p v-if="deptForm.errors.code" class="mt-1 text-xs text-red-500">{{ deptForm.errors.code }}</p>
                        </div>
                        <div class="flex items-end gap-3">
                            <button type="submit" :disabled="deptForm.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                {{ deptForm.processing ? 'Saving...' : 'Save Department' }}
                            </button>
                            <button type="button" @click="showDeptForm = false" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Departments Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Department Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Code</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Faculty</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Programs</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="departments.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">No departments yet.</td>
                            </tr>
                            <tr v-for="(d, i) in departments" :key="d.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400">{{ i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ d.name }}</td>
                                <td class="px-4 py-3"><span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700">{{ d.code }}</span></td>
                                <td class="px-4 py-3 text-gray-600">{{ d.faculty_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ d.programs_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="d.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                        {{ d.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── PROGRAMS TAB ── -->
            <div v-if="activeTab === 'programs'">
                <div class="mb-4 flex justify-end">
                    <button
                        @click="showProgForm = !showProgForm"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        + Add Program
                    </button>
                </div>

                <!-- Add Program Form -->
                <div v-if="showProgForm" class="mb-6 rounded-xl border border-indigo-100 bg-indigo-50 p-5">
                    <h3 class="mb-4 text-sm font-semibold text-indigo-800">New Program</h3>
                    <form @submit.prevent="submitProgram" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Department</label>
                            <select v-model="progForm.department_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                                <option value="">Select department</option>
                                <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }} ({{ d.faculty_name }})</option>
                            </select>
                            <p v-if="progForm.errors.department_id" class="mt-1 text-xs text-red-500">{{ progForm.errors.department_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Program Name</label>
                            <input v-model="progForm.name" type="text" placeholder="e.g. Bachelor of Information Technology" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required />
                            <p v-if="progForm.errors.name" class="mt-1 text-xs text-red-500">{{ progForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Code</label>
                            <input v-model="progForm.code" type="text" placeholder="e.g. BIT" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase" required />
                            <p v-if="progForm.errors.code" class="mt-1 text-xs text-red-500">{{ progForm.errors.code }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Level</label>
                            <select v-model="progForm.level" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                                <option value="">Select level</option>
                                <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                            </select>
                            <p v-if="progForm.errors.level" class="mt-1 text-xs text-red-500">{{ progForm.errors.level }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Duration (years)</label>
                            <input v-model="progForm.duration_years" type="number" step="0.01" min="0.01" placeholder="e.g. 3 or 0.33" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required />
                            <p v-if="progForm.errors.duration_years" class="mt-1 text-xs text-red-500">{{ progForm.errors.duration_years }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Duration Display</label>
                            <input v-model="progForm.duration_display" type="text" placeholder="e.g. 3 years or 4 months" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required />
                            <p v-if="progForm.errors.duration_display" class="mt-1 text-xs text-red-500">{{ progForm.errors.duration_display }}</p>
                        </div>
                        <div class="sm:col-span-3 flex gap-3">
                            <button type="submit" :disabled="progForm.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                {{ progForm.processing ? 'Saving...' : 'Save Program' }}
                            </button>
                            <button type="button" @click="showProgForm = false" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Programs Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Program Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Code</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Level</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Duration</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Department</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Faculty</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="programs.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">No programs yet.</td>
                            </tr>
                            <tr v-for="(p, i) in programs" :key="p.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400">{{ i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ p.name }}</td>
                                <td class="px-4 py-3"><span class="rounded bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-700">{{ p.code }}</span></td>
                                <td class="px-4 py-3 text-gray-600 capitalize">{{ p.level?.replace('_', ' ') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ p.duration_display }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ p.department_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ p.faculty_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                        {{ p.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>