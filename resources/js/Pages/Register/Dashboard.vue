<script setup>
import RegistrarLayout from '@/Layouts/RegistrarLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import MetricCard from '@/Components/MetricCard.vue';
import { ArrowPathIcon, AcademicCapIcon, UserGroupIcon, UserPlusIcon, IdentificationIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    stats:         { type: Object, default: () => ({}) },
    recentImports: { type: Array,  default: () => [] },
    user:          { type: Object, default: () => ({}) },
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
}) : '—';

const statusColor = (s) => ({
    completed:   'bg-green-100 text-green-700',
    partial:     'bg-yellow-100 text-yellow-700',
    failed:      'bg-red-100 text-red-700',
    processing:  'bg-blue-100 text-blue-700',
}[s] ?? 'bg-gray-100 text-gray-600');

const typeColor = (t) => t === 'students'
    ? 'bg-indigo-100 text-indigo-700'
    : 'bg-teal-100 text-teal-700';
</script>

<template>
    <RegistrarLayout>
        <Head title="Registrar Dashboard" />
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Registrar Dashboard</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Welcome back, {{ user?.name }}</p>
                </div>
                <button @click="router.visit(route('registrar.ManageUser'))"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Import Users
                </button>
            </div>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <!-- Welcome banner -->
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">User management overview</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Manage student and staff registrations via CSV import.
                        </p>
                        <div class="flex items-center gap-4 mt-3">
                            <div>
                                <p class="text-2xl font-black text-slate-950">{{ stats.total_students ?? 0 }}</p>
                                <p class="text-xs text-slate-500">Students Imported</p>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <div>
                                <p class="text-2xl font-black text-slate-950">{{ stats.total_staff ?? 0 }}</p>
                                <p class="text-xs text-slate-500">Staff Imported</p>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <div>
                                <p class="text-2xl font-black text-slate-950">{{ stats.total_imports ?? 0 }}</p>
                                <p class="text-xs text-slate-500">Total Imports</p>
                            </div>
                        </div>
                    </div>
                    <div class="hidden sm:flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10">
                        <svg class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <MetricCard label="Total imports" :value="stats.total_imports" :icon="ArrowPathIcon" tone="blue" />
                <MetricCard label="Student batches" :value="stats.student_imports" :icon="AcademicCapIcon" tone="indigo" />
                <MetricCard label="Staff batches" :value="stats.staff_imports" :icon="UserGroupIcon" tone="teal" />
                <MetricCard label="Students added" :value="stats.total_students" :icon="UserPlusIcon" tone="emerald" />
                <MetricCard label="Staff added" :value="stats.total_staff" :icon="IdentificationIcon" tone="blue" />
                <MetricCard label="Failed rows" :value="stats.total_failed" :icon="ExclamationTriangleIcon" tone="rose" />
            </div>

            <!-- Quick actions -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button @click="router.visit(route('registrar.ManageUser'))"
                    class="rounded-xl border-2 border-indigo-200 bg-indigo-50 p-6 text-left hover:bg-indigo-100 transition group">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 mb-4 group-hover:bg-indigo-700 transition">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-indigo-900">Import Students</h3>
                    <p class="text-sm text-indigo-600 mt-1">Upload student CSV file to register students in bulk</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-700">
                        Go to Import →
                    </span>
                </button>

                <button @click="router.visit(route('registrar.ManageUser'))"
                    class="rounded-xl border-2 border-teal-200 bg-teal-50 p-6 text-left hover:bg-teal-100 transition group">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-600 mb-4 group-hover:bg-teal-700 transition">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-teal-900">Import Staff</h3>
                    <p class="text-sm text-teal-600 mt-1">Upload staff CSV file to register lecturers and HODs</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-teal-700">
                        Go to Import →
                    </span>
                </button>
            </div>

            <!-- CSV format guide -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700">CSV Format Guide</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                    <div class="p-5">
                        <h4 class="text-xs font-semibold text-indigo-700 mb-2 flex items-center gap-1">
                            <span class="rounded bg-indigo-100 px-1.5 py-0.5">Students CSV</span>
                        </h4>
                        <div class="font-mono text-xs text-gray-600 bg-gray-50 rounded-lg p-3 space-y-0.5">
                            <p class="font-semibold text-gray-800">first_name, last_name, email, phone,</p>
                            <p class="font-semibold text-gray-800">registration_number, program_code,</p>
                            <p class="font-semibold text-gray-800">year_of_study, semester,</p>
                            <p class="font-semibold text-gray-800">academic_year, enrollment_status</p>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">Default password = last_name. First login requires password change.</p>
                    </div>
                    <div class="p-5">
                        <h4 class="text-xs font-semibold text-teal-700 mb-2 flex items-center gap-1">
                            <span class="rounded bg-teal-100 px-1.5 py-0.5">Staff CSV</span>
                        </h4>
                        <div class="font-mono text-xs text-gray-600 bg-gray-50 rounded-lg p-3 space-y-0.5">
                            <p class="font-semibold text-gray-800">first_name, last_name, email, phone,</p>
                            <p class="font-semibold text-gray-800">role, department_code, staff_number,</p>
                            <p class="font-semibold text-gray-800">title, gender, specialization,</p>
                            <p class="font-semibold text-gray-800">employment_type, office_location</p>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">Default password = last_name. First login requires password change.</p>
                    </div>
                </div>
            </div>

            <!-- Recent import history -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-3 bg-gray-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Recent Import History</h3>
                    <span class="text-xs text-gray-400">{{ recentImports.length }} records</span>
                </div>

                <div v-if="recentImports.length === 0" class="px-5 py-10 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <p class="text-sm text-gray-400">No imports yet. Upload your first CSV to get started.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">File</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Success</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Failed</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="imp in recentImports" :key="imp.uuid" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-xs font-mono text-gray-600 max-w-32 truncate">
                                    {{ imp.filename ?? imp.uuid?.substring(0, 8) + '...' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="typeColor(imp.type)">
                                        {{ imp.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-bold text-green-600">
                                    {{ imp.success_count ?? 0 }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-bold" :class="(imp.fail_count ?? 0) > 0 ? 'text-red-600' : 'text-gray-400'">
                                    {{ imp.fail_count ?? 0 }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-gray-600">
                                    {{ (imp.success_count ?? 0) + (imp.fail_count ?? 0) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColor(imp.status)">
                                        {{ imp.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-400">{{ formatDate(imp.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </RegistrarLayout>
</template>
