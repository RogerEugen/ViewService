<script setup>
import RegistrarLayout from '@/Layouts/RegistrarLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    imports: { type: Array, default: () => [] },
});

const page = usePage();

const activeTab    = ref('students');
const showErrors   = ref(false);

// ── Student import form ─────────────────────────
const studentForm = useForm({ csv_file: null });
const staffForm   = useForm({ csv_file: null });

const onStudentFile = (e) => {
    studentForm.csv_file = e.target.files[0];
};
const onStaffFile = (e) => {
    staffForm.csv_file = e.target.files[0];
};

const submitStudents = () => {
    studentForm.post(route('registrar.import.students'), {
        forceFormData: true,
        onSuccess: () => {
            studentForm.reset();
            showErrors.value = true;
        },
    });
};

const submitStaff = () => {
    staffForm.post(route('registrar.import.staff'), {
        forceFormData: true,
        onSuccess: () => {
            staffForm.reset();
            showErrors.value = true;
        },
    });
};

// Flash data
const flash         = computed(() => page.props.flash ?? {});
const importErrors  = computed(() => page.props.flash?.import_errors ?? []);
const successCount  = computed(() => page.props.flash?.successful_rows ?? 0);
const failedCount   = computed(() => page.props.flash?.failed_rows ?? 0);

const statusColor = (status) => {
    if (status === 'completed') return 'bg-green-100 text-green-700';
    if (status === 'failed')    return 'bg-red-100 text-red-700';
    if (status === 'processing') return 'bg-yellow-100 text-yellow-700';
    return 'bg-gray-100 text-gray-600';
};
</script>

<template>
    <RegistrarLayout>
        <Head title="Manage Users — CSV Import" />

        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Manage Users</h2>
        </template>

        <div class="py-8 px-6 max-w-6xl mx-auto">

            <!-- Success banner -->
            <div
                v-if="flash.success"
                class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 flex items-start gap-3"
            >
                <svg class="h-5 w-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-green-800">{{ flash.success }}</p>
                    <p class="mt-0.5 text-sm text-green-700">
                        <span class="font-medium">{{ successCount }}</span> imported successfully.
                        <span v-if="failedCount > 0" class="text-red-600 font-medium">{{ failedCount }} failed — see errors below.</span>
                    </p>
                </div>
            </div>

            <!-- Error rows from import -->
            <div
                v-if="importErrors.length > 0"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4"
            >
                <p class="mb-3 text-sm font-semibold text-red-800">
                    {{ importErrors.length }} rows failed to import:
                </p>
                <div class="max-h-48 overflow-y-auto space-y-2">
                    <div
                        v-for="err in importErrors"
                        :key="err.row"
                        class="rounded-lg bg-white border border-red-100 px-3 py-2 text-xs"
                    >
                        <span class="font-semibold text-red-700">Row {{ err.row }}:</span>
                        <span class="ml-1 text-gray-600">{{ err.errors.join(', ') }}</span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex gap-6">
                    <button
                        v-for="tab in ['students', 'staff', 'history']"
                        :key="tab"
                        @click="activeTab = tab"
                        class="pb-3 text-sm font-medium capitalize border-b-2 transition"
                        :class="activeTab === tab
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                    >
                        {{ tab === 'history' ? 'Import History' : 'Import ' + tab }}
                    </button>
                </nav>
            </div>

            <!-- ── STUDENTS TAB ── -->
            <div v-if="activeTab === 'students'">
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Upload Student CSV</h3>
                    <p class="text-sm text-gray-500 mb-5">
                        Each student will be created with their last name as the default password.
                        They will be forced to change it on first login.
                    </p>

                    <!-- Required columns -->
                    <div class="mb-5 rounded-lg bg-gray-50 border border-gray-200 p-4">
                        <p class="text-xs font-semibold text-gray-600 mb-2">Required CSV columns:</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="col in ['first_name','last_name','email','phone','registration_number','program_code','year_of_study','semester','academic_year','gender','date_of_birth','admission_year','enrollment_status']"
                                :key="col"
                                class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-mono text-indigo-700"
                            >{{ col }}</span>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">
                            enrollment_status values: active, suspended, deferred, graduated
                        </p>
                    </div>

                    <form @submit.prevent="submitStudents">
                        <div class="flex items-center gap-4">
                            <label class="flex-1 cursor-pointer rounded-xl border-2 border-dashed border-gray-300 p-6 text-center hover:border-indigo-400 hover:bg-indigo-50 transition">
                                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                                <span class="text-sm text-gray-600">
                                    {{ studentForm.csv_file ? studentForm.csv_file.name : 'Click to select students CSV file' }}
                                </span>
                                <input type="file" accept=".csv" class="hidden" @change="onStudentFile"/>
                            </label>
                        </div>
                        <p v-if="studentForm.errors.csv_file" class="mt-1 text-xs text-red-500">{{ studentForm.errors.csv_file }}</p>

                        <div class="mt-4 flex items-center gap-3">
                            <button
                                type="submit"
                                :disabled="!studentForm.csv_file || studentForm.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="studentForm.processing" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                    Importing...
                                </span>
                                <span v-else>Import Students</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── STAFF TAB ── -->
            <div v-if="activeTab === 'staff'">
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-1">Upload Staff CSV</h3>
                    <p class="text-sm text-gray-500 mb-5">
                        Lecturers, HODs, Deans and other staff will be created with their last name as default password.
                    </p>

                    <!-- Required columns -->
                    <div class="mb-5 rounded-lg bg-gray-50 border border-gray-200 p-4">
                        <p class="text-xs font-semibold text-gray-600 mb-2">Required CSV columns:</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="col in ['first_name','last_name','email','phone','staff_number','department_code','role','title','gender','specialization','employment_type','office_location','joined_date']"
                                :key="col"
                                class="rounded bg-purple-100 px-2 py-0.5 text-xs font-mono text-purple-700"
                            >{{ col }}</span>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">
                            role values: lecturer, hod, dean, rector, registrar, admin &nbsp;|&nbsp;
                            title values: Mr, Mrs, Ms, Dr, Prof &nbsp;|&nbsp;
                            employment_type: fulltime, parttime, contract
                        </p>
                    </div>

                    <form @submit.prevent="submitStaff">
                        <div class="flex items-center gap-4">
                            <label class="flex-1 cursor-pointer rounded-xl border-2 border-dashed border-gray-300 p-6 text-center hover:border-purple-400 hover:bg-purple-50 transition">
                                <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                                <span class="text-sm text-gray-600">
                                    {{ staffForm.csv_file ? staffForm.csv_file.name : 'Click to select staff CSV file' }}
                                </span>
                                <input type="file" accept=".csv" class="hidden" @change="onStaffFile"/>
                            </label>
                        </div>
                        <p v-if="staffForm.errors.csv_file" class="mt-1 text-xs text-red-500">{{ staffForm.errors.csv_file }}</p>

                        <div class="mt-4">
                            <button
                                type="submit"
                                :disabled="!staffForm.csv_file || staffForm.processing"
                                class="rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="staffForm.processing" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                    Importing...
                                </span>
                                <span v-else>Import Staff</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── HISTORY TAB ── -->
            <div v-if="activeTab === 'history'">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">File</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Total</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Success</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Failed</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="imports.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">No imports yet.</td>
                            </tr>
                            <tr v-for="imp in imports" :key="imp.uuid" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900 max-w-xs truncate">{{ imp.original_filename }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                        :class="imp.import_type === 'students' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700'">
                                        {{ imp.import_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ imp.total_rows }}</td>
                                <td class="px-4 py-3 text-green-600 font-medium">{{ imp.successful_rows }}</td>
                                <td class="px-4 py-3 text-red-500 font-medium">{{ imp.failed_rows }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColor(imp.status)">
                                        {{ imp.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ new Date(imp.created_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </RegistrarLayout>
</template>