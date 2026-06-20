<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    ArrowPathRoundedSquareIcon,
    CheckBadgeIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ExclamationTriangleIcon,
    BuildingOffice2Icon,
    FireIcon,
    FunnelIcon,
    LightBulbIcon,
    RectangleGroupIcon,
} from '@heroicons/vue/24/outline';
import MetricCard from '@/Components/MetricCard.vue';

const props = defineProps({
    groups: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    role: { type: String, required: true },
});

const expanded = ref(props.groups[0]?.group_key ?? null);
const status = ref(props.filters.status ?? 'all');
const categoryId = ref(props.filters.category_id ?? '');
const minimumSize = ref(props.filters.minimum_group_size ?? 2);
const departmentId = ref(props.filters.department_id ?? '');

const routeName = computed(() => `${props.role}.recurring-issues`);
const departmentNames = computed(() => Object.fromEntries(
    props.departments.map((department) => [department.id, department.name])
));
const priorityGroups = computed(() => props.groups.filter((group) =>
    ['critical', 'high'].includes(group.investigation_level)
));

const applyFilters = () => router.get(route(routeName.value), {
    status: status.value,
    category_id: categoryId.value || undefined,
    department_id: departmentId.value || undefined,
    minimum_group_size: minimumSize.value,
}, {}, { preserveState: true, preserveScroll: true });

const detailRoute = (id) => route(`${props.role}.feedbacks.show`, id);
const formatDate = (date) => date
    ? new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—';
const statusClass = (value) => ({
    resolved: 'bg-emerald-50 text-emerald-700',
    escalated: 'bg-orange-50 text-orange-700',
    under_review: 'bg-amber-50 text-amber-700',
    submitted: 'bg-blue-50 text-blue-700',
}[value] ?? 'bg-slate-100 text-slate-600');
const investigationClass = (level) => ({
    critical: 'border-red-200 bg-red-50 text-red-700',
    high: 'border-orange-200 bg-orange-50 text-orange-700',
    moderate: 'border-amber-200 bg-amber-50 text-amber-700',
    watch: 'border-slate-200 bg-slate-50 text-slate-600',
}[level] ?? 'border-slate-200 bg-slate-50 text-slate-600');
</script>

<template>
    <main class="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Recurring issue intelligence</p>
                <h1 class="mt-2 text-2xl font-black text-slate-950">Similar Feedback Groups</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">
                    Related anonymous feedback is grouped to reveal repeated concerns and reuse solutions that already worked.
                </p>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">
                <CheckBadgeIcon class="h-4 w-4" />
                Identity-safe analysis
            </div>
        </div>

        <section class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <MetricCard label="Feedback analysed" :value="summary.feedbacks_analysed ?? 0" :icon="ArrowPathRoundedSquareIcon" tone="blue" />
            <MetricCard label="Recurring groups" :value="summary.recurring_groups ?? 0" :icon="RectangleGroupIcon" tone="violet" />
            <MetricCard label="Grouped feedback" :value="summary.grouped_feedbacks ?? 0" :icon="FunnelIcon" tone="amber" />
            <MetricCard label="Groups with solutions" :value="summary.groups_with_solution ?? 0" :icon="LightBulbIcon" tone="emerald" />
            <MetricCard label="Priority investigations" :value="summary.priority_investigations ?? 0" :icon="FireIcon" tone="rose" />
        </section>

        <section class="mt-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto]">
                <label class="text-xs font-bold text-slate-600">
                    Status
                    <select v-model="status" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all">All statuses</option>
                        <option value="open">Open feedback</option>
                        <option value="resolved">Resolved feedback</option>
                    </select>
                </label>
                <label v-if="role !== 'hod'" class="text-xs font-bold text-slate-600">
                    Department
                    <select v-model="departmentId" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All departments</option>
                        <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
                    </select>
                </label>
                <label class="text-xs font-bold text-slate-600">
                    Category
                    <select v-model="categoryId" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </label>
                <label class="text-xs font-bold text-slate-600">
                    Minimum occurrences
                    <select v-model="minimumSize" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option :value="2">2 or more</option>
                        <option :value="3">3 or more</option>
                        <option :value="5">5 or more</option>
                        <option :value="1">Include unique issues</option>
                    </select>
                </label>
                <button @click="applyFilters" class="self-end rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
                    Apply filters
                </button>
            </div>
        </section>

        <section v-if="priorityGroups.length" class="mt-5 rounded-xl border border-red-100 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-red-50 text-red-600"><FireIcon class="h-5 w-5" /></div>
                <div>
                    <h2 class="text-sm font-black text-slate-950">Priority Investigations</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Repeated open, urgent or escalated concerns requiring closer investigation.</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                <button
                    v-for="group in priorityGroups"
                    :key="`priority-${group.group_key}`"
                    type="button"
                    class="rounded-xl border border-slate-200 p-4 text-left transition hover:border-red-200 hover:bg-red-50/30"
                    @click="expanded = group.group_key"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-900">{{ group.title }}</p>
                            <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                <BuildingOffice2Icon class="h-4 w-4" />
                                {{ departmentNames[group.department_id] ?? `Department #${group.department_id ?? '—'}` }}
                            </p>
                        </div>
                        <span class="rounded-md border px-2 py-1 text-[10px] font-black uppercase" :class="investigationClass(group.investigation_level)">
                            {{ group.investigation_level }}
                        </span>
                    </div>
                    <div class="mt-3 flex items-center gap-4 text-xs">
                        <span class="font-bold text-slate-700">{{ group.feedback_count }} reports</span>
                        <span class="font-bold text-amber-600">{{ group.open_count }} open</span>
                        <span v-if="group.urgent_count" class="font-bold text-red-600">{{ group.urgent_count }} urgent</span>
                        <span class="ml-auto font-black text-slate-400">Score {{ group.investigation_score }}</span>
                    </div>
                </button>
            </div>
        </section>

        <section v-if="groups.length" class="mt-5 space-y-3">
            <article v-for="group in groups" :key="group.group_key" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <button
                    class="grid w-full gap-4 p-5 text-left hover:bg-slate-50 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                    @click="expanded = expanded === group.group_key ? null : group.group_key"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-blue-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-blue-700">{{ group.category }}</span>
                            <span class="flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">
                                <BuildingOffice2Icon class="h-3.5 w-3.5" />
                                {{ departmentNames[group.department_id] ?? `Department #${group.department_id ?? '—'}` }}
                            </span>
                            <span class="rounded-md border px-2 py-1 text-[10px] font-black uppercase" :class="investigationClass(group.investigation_level)">
                                {{ group.investigation_level }}
                            </span>
                            <span v-if="group.urgent_count" class="flex items-center gap-1 rounded-md bg-red-50 px-2 py-1 text-[10px] font-black text-red-700">
                                <ExclamationTriangleIcon class="h-3.5 w-3.5" />{{ group.urgent_count }} urgent
                            </span>
                        </div>
                        <h2 class="mt-2 truncate text-base font-black text-slate-950">{{ group.title }}</h2>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span v-for="keyword in group.keywords" :key="keyword" class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                                {{ keyword }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-5 sm:justify-end">
                        <div class="text-center"><p class="text-xl font-black text-slate-950">{{ group.feedback_count }}</p><p class="text-[10px] uppercase text-slate-400">Reports</p></div>
                        <div class="text-center"><p class="text-xl font-black text-amber-600">{{ group.open_count }}</p><p class="text-[10px] uppercase text-slate-400">Open</p></div>
                        <div class="text-center"><p class="text-xl font-black text-emerald-600">{{ group.resolved_count }}</p><p class="text-[10px] uppercase text-slate-400">Resolved</p></div>
                        <ChevronUpIcon v-if="expanded === group.group_key" class="h-5 w-5 text-slate-400" />
                        <ChevronDownIcon v-else class="h-5 w-5 text-slate-400" />
                    </div>
                </button>

                <div v-if="expanded === group.group_key" class="grid border-t border-slate-100 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.6fr)]">
                    <div class="p-5">
                        <h3 class="text-xs font-black uppercase tracking-wide text-slate-500">Feedback in this group</h3>
                        <div class="mt-3 divide-y divide-slate-100">
                            <a
                                v-for="member in group.members"
                                :key="member.id"
                                :href="detailRoute(member.id)"
                                class="grid gap-3 py-3 hover:bg-slate-50 sm:grid-cols-[minmax(0,1fr)_auto]"
                            >
                                <div>
                                    <p class="text-sm leading-6 text-slate-700">{{ member.preview }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ member.sender_role }} · {{ departmentNames[member.department_id] ?? `Department #${member.department_id ?? '—'}` }} · {{ formatDate(member.submitted_at) }}
                                    </p>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="rounded-md px-2 py-1 text-[10px] font-bold capitalize" :class="statusClass(member.status)">
                                        {{ member.status.replace('_', ' ') }}
                                    </span>
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-bold capitalize text-slate-600">{{ member.priority }}</span>
                                </div>
                            </a>
                        </div>
                    </div>
                    <aside class="border-t border-slate-100 bg-slate-50 p-5 lg:border-l lg:border-t-0">
                        <div class="flex items-center gap-2">
                            <div class="grid h-9 w-9 place-items-center rounded-lg bg-amber-100 text-amber-700"><LightBulbIcon class="h-5 w-5" /></div>
                            <div><p class="text-xs font-black uppercase tracking-wide text-slate-500">Suggested solution</p><p class="text-xs text-slate-400">From resolved similar feedback</p></div>
                        </div>
                        <p v-if="group.suggested_solution" class="mt-4 rounded-lg border border-emerald-100 bg-white p-4 text-sm leading-6 text-slate-700">
                            {{ group.suggested_solution }}
                        </p>
                        <div v-else class="mt-4 rounded-lg border border-dashed border-slate-300 bg-white p-4">
                            <p class="text-sm font-bold text-slate-700">No proven solution yet</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Resolve one case with a detailed resolution note and it will become reusable here.</p>
                        </div>
                        <p class="mt-4 text-xs text-slate-400">Latest activity: {{ formatDate(group.latest_at) }}</p>
                    </aside>
                </div>
            </article>
        </section>

        <section v-else class="mt-5 grid min-h-64 place-items-center rounded-xl border border-dashed border-slate-300 bg-white text-center">
            <div>
                <RectangleGroupIcon class="mx-auto h-10 w-10 text-slate-300" />
                <p class="mt-3 text-sm font-black text-slate-700">No matching recurring groups</p>
                <p class="mt-1 text-xs text-slate-500">Try reducing the minimum occurrences or changing the filters.</p>
            </div>
        </section>
    </main>
</template>
