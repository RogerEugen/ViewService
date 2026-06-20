<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDownTrayIcon,
    BuildingOffice2Icon,
    CheckCircleIcon,
    ClockIcon,
    DocumentChartBarIcon,
    ExclamationTriangleIcon,
    FunnelIcon,
    PrinterIcon,
} from '@heroicons/vue/24/outline';
import RectorLayout from '@/Layouts/RectorLayout.vue';
import MetricCard from '@/Components/MetricCard.vue';

const props = defineProps({
    report: { type: Object, default: () => ({}) },
    faculties: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const facultyId = ref(props.filters.faculty_id ?? '');
const departmentId = ref(props.filters.department_id ?? '');
const facultyNames = computed(() => Object.fromEntries(props.faculties.map((item) => [item.id, item.name])));
const departmentNames = computed(() => Object.fromEntries(props.departments.map((item) => [item.id, item.name])));
const visibleDepartments = computed(() => facultyId.value
    ? props.departments.filter((item) => Number(item.faculty_id) === Number(facultyId.value))
    : props.departments
);

const query = computed(() => ({
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
    faculty_id: facultyId.value || undefined,
    department_id: departmentId.value || undefined,
}));
const applyFilters = () => router.get(route('rector.reports'), query.value, {}, { preserveScroll: true });
const exportUrl = computed(() => route('rector.reports.export', query.value));
const printReport = () => window.print();
const onFacultyChange = () => {
    if (!visibleDepartments.value.some((item) => Number(item.id) === Number(departmentId.value))) {
        departmentId.value = '';
    }
};
const formatDateTime = (value) => value
    ? new Date(value).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })
    : '—';
const rateWidth = (value) => `${Math.min(100, Math.max(0, Number(value || 0)))}%`;
</script>

<template>
    <RectorLayout>
        <Head title="Feedback Reports" />
        <main class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Executive reporting</p>
                    <h1 class="mt-2 text-2xl font-black text-slate-950">Faculty & Department Reports</h1>
                    <p class="mt-1 text-sm text-slate-500">Generate institution-wide anonymous feedback performance reports.</p>
                </div>
                <div class="flex gap-2 print:hidden">
                    <button @click="printReport" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <PrinterIcon class="h-4 w-4" />Print
                    </button>
                    <a :href="exportUrl" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
                        <ArrowDownTrayIcon class="h-4 w-4" />Export CSV
                    </a>
                </div>
            </header>

            <section class="mt-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm print:hidden">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto]">
                    <label class="text-xs font-bold text-slate-600">From
                        <input v-model="dateFrom" type="date" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </label>
                    <label class="text-xs font-bold text-slate-600">To
                        <input v-model="dateTo" type="date" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </label>
                    <label class="text-xs font-bold text-slate-600">Faculty
                        <select v-model="facultyId" @change="onFacultyChange" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All faculties</option>
                            <option v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">{{ faculty.name }}</option>
                        </select>
                    </label>
                    <label class="text-xs font-bold text-slate-600">Department
                        <select v-model="departmentId" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All departments</option>
                            <option v-for="department in visibleDepartments" :key="department.id" :value="department.id">{{ department.name }}</option>
                        </select>
                    </label>
                    <button @click="applyFilters" class="flex items-center justify-center gap-2 self-end rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800">
                        <FunnelIcon class="h-4 w-4" />Generate
                    </button>
                </div>
            </section>

            <div class="mt-4 hidden text-xs text-slate-500 print:block">
                Generated {{ formatDateTime(report.generated_at) }}
            </div>

            <section class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <MetricCard label="Total feedback" :value="report.summary?.total ?? 0" :icon="DocumentChartBarIcon" tone="blue" />
                <MetricCard label="Open cases" :value="report.summary?.open ?? 0" :icon="ClockIcon" tone="amber" />
                <MetricCard label="Resolved" :value="report.summary?.resolved ?? 0" :icon="CheckCircleIcon" tone="emerald" />
                <MetricCard label="Urgent" :value="report.summary?.urgent ?? 0" :icon="ExclamationTriangleIcon" tone="rose" />
                <MetricCard label="Resolution rate" :value="`${report.summary?.resolution_rate ?? 0}%`" :icon="BuildingOffice2Icon" tone="violet" />
            </section>

            <section class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black text-slate-900">Faculty Performance</h2>
                    <p class="mt-1 text-xs text-slate-500">Feedback handling and resolution outcomes by faculty.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-3">Faculty</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Open</th><th class="px-4 py-3">Urgent</th><th class="px-4 py-3">Resolved</th><th class="min-w-52 px-4 py-3">Resolution performance</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in report.by_faculty ?? []" :key="row.id ?? 'none'">
                                <td class="px-5 py-4 font-bold text-slate-800">{{ facultyNames[row.id] ?? 'Unassigned faculty' }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ row.total }}</td>
                                <td class="px-4 py-4 font-bold text-amber-600">{{ row.open }}</td>
                                <td class="px-4 py-4 font-bold text-red-600">{{ row.urgent }}</td>
                                <td class="px-4 py-4 font-bold text-emerald-600">{{ row.resolved }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3"><div class="h-2 flex-1 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-blue-600" :style="{ width: rateWidth(row.resolution_rate) }"></div></div><span class="w-12 text-right text-xs font-black text-slate-700">{{ row.resolution_rate }}%</span></div>
                                    <p class="mt-1 text-[10px] text-slate-400">{{ row.average_resolution_hours ?? '—' }} average hours</p>
                                </td>
                            </tr>
                            <tr v-if="!(report.by_faculty?.length)"><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No faculty feedback matches these filters.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black text-slate-900">Department Performance</h2>
                    <p class="mt-1 text-xs text-slate-500">Detailed operational comparison across departments.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-3">Department</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Student</th><th class="px-4 py-3">Lecturer</th><th class="px-4 py-3">Escalated</th><th class="px-4 py-3">Open</th><th class="px-4 py-3">Resolution rate</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in report.by_department ?? []" :key="row.id ?? 'none'">
                                <td class="px-5 py-4 font-bold text-slate-800">{{ departmentNames[row.id] ?? 'Unassigned department' }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ row.total }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ row.student }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ row.lecturer }}</td>
                                <td class="px-4 py-4 font-bold text-orange-600">{{ row.escalated }}</td>
                                <td class="px-4 py-4 font-bold text-amber-600">{{ row.open }}</td>
                                <td class="px-4 py-4 font-black text-blue-700">{{ row.resolution_rate }}%</td>
                            </tr>
                            <tr v-if="!(report.by_department?.length)"><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-400">No department feedback matches these filters.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </RectorLayout>
</template>

<style>
@media print {
    nav { display: none !important; }
    body, .min-h-screen { background: white !important; }
    main { max-width: none !important; padding: 0 !important; }
    section { break-inside: avoid; box-shadow: none !important; }
}
</style>
