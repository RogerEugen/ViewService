<script setup>
import HodLayout from '@/Layouts/HodLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowRightIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    InboxIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    evalStats: { type: Object, default: () => ({}) },
    recent: { type: Array, default: () => [] },
    user: { type: Object, default: () => ({}) },
});

const resolutionRate = computed(() => props.stats.total
    ? Math.round((props.stats.resolved / props.stats.total) * 100)
    : 0);

const responseScore = computed(() => Math.max(
    0,
    100 - Math.round(((props.stats.submitted ?? 0) / Math.max(props.stats.total ?? 0, 1)) * 100),
));

const chartBars = computed(() => [
    { label: 'Total', value: props.stats.total ?? 0, color: 'bg-blue-200' },
    { label: 'New', value: props.stats.submitted ?? 0, color: 'bg-blue-300' },
    { label: 'Review', value: props.stats.under_review ?? 0, color: 'bg-blue-400' },
    { label: 'Resolved', value: props.stats.resolved ?? 0, color: 'bg-blue-600' },
    { label: 'Urgent', value: props.stats.urgent ?? 0, color: 'bg-rose-300' },
]);

const maxChartValue = computed(() => Math.max(...chartBars.value.map((item) => item.value), 1));

const statusClass = (status) => ({
    submitted: 'bg-amber-50 text-amber-700',
    under_review: 'bg-blue-50 text-blue-700',
    escalated: 'bg-orange-50 text-orange-700',
    resolved: 'bg-emerald-50 text-emerald-700',
}[status] ?? 'bg-slate-100 text-slate-600');

const priorityClass = (priority) => ({
    urgent: 'text-rose-600',
    high: 'text-orange-600',
    medium: 'text-blue-600',
    low: 'text-emerald-600',
}[priority] ?? 'text-slate-500');

const formatDate = (value) => value
    ? new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—';
</script>

<template>
    <HodLayout>
        <Head title="Departmental Dashboard" />

        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-950">Departmental Dashboard</h1>
                <p class="mt-1 text-xs text-slate-500">Welcome back, {{ user?.name }}. Here is what needs your attention.</p>
            </div>
        </template>

        <div class="mx-auto max-w-[1500px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <span class="rounded-xl bg-blue-50 p-2.5 text-blue-600"><InboxIcon class="h-5 w-5" /></span>
                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-600">Live</span>
                    </div>
                    <p class="mt-4 text-xs font-medium text-slate-500">Total Feedback</p>
                    <p class="mt-1 text-2xl font-black text-slate-950">{{ stats.total ?? 0 }}</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <span class="rounded-xl bg-amber-50 p-2.5 text-amber-600"><ClockIcon class="h-5 w-5" /></span>
                        <span class="text-[10px] font-bold text-slate-400">{{ stats.submitted ?? 0 }} new</span>
                    </div>
                    <p class="mt-4 text-xs font-medium text-slate-500">Response Score</p>
                    <p class="mt-1 text-2xl font-black text-slate-950">{{ responseScore }}%</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <span class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600"><CheckCircleIcon class="h-5 w-5" /></span>
                        <span class="text-[10px] font-bold text-emerald-600">{{ stats.resolved ?? 0 }} closed</span>
                    </div>
                    <p class="mt-4 text-xs font-medium text-slate-500">Resolution Rate</p>
                    <p class="mt-1 text-2xl font-black text-slate-950">{{ resolutionRate }}%</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <span class="rounded-xl bg-rose-50 p-2.5 text-rose-600"><ExclamationTriangleIcon class="h-5 w-5" /></span>
                        <span class="text-[10px] font-bold text-rose-600">Priority</span>
                    </div>
                    <p class="mt-4 text-xs font-medium text-slate-500">Urgent Issues</p>
                    <p class="mt-1 text-2xl font-black text-slate-950">{{ stats.urgent ?? 0 }}</p>
                </article>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-black text-slate-900">Feedback Volume</h2>
                            <p class="mt-1 text-xs text-slate-400">Current department workflow distribution</p>
                        </div>
                        <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600" @click="router.visit(route('hod.feedbacks'))">View details</button>
                    </div>
                    <div class="mt-8 flex h-52 items-end gap-3">
                        <div v-for="item in chartBars" :key="item.label" class="flex h-full flex-1 flex-col justify-end">
                            <span class="mb-2 text-center text-xs font-bold text-slate-700">{{ item.value }}</span>
                            <div
                                class="min-h-2 rounded-t-lg transition-all duration-700"
                                :class="item.color"
                                :style="{ height: `${Math.max((item.value / maxChartValue) * 100, 4)}%` }"
                            ></div>
                            <span class="mt-3 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ item.label }}</span>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Department Performance</h2>
                        <p class="mt-1 text-xs text-slate-400">Feedback and course evaluation indicators</p>
                    </div>
                    <div class="mt-7 space-y-6">
                        <div>
                            <div class="mb-2 flex justify-between text-xs font-semibold"><span class="text-slate-600">Feedback resolved</span><span class="text-blue-600">{{ resolutionRate }}%</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-blue-600" :style="{ width: `${resolutionRate}%` }"></div></div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between text-xs font-semibold"><span class="text-slate-600">Response score</span><span class="text-emerald-600">{{ responseScore }}%</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-emerald-500" :style="{ width: `${responseScore}%` }"></div></div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between text-xs font-semibold"><span class="text-slate-600">Average course rating</span><span class="text-amber-600">{{ evalStats.avg_overall ?? 0 }}/5</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-amber-400" :style="{ width: `${((evalStats.avg_overall ?? 0) / 5) * 100}%` }"></div></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <button class="rounded-xl bg-blue-600 px-4 py-3 text-xs font-bold text-white hover:bg-blue-700" @click="router.visit(route('hod.evaluations'))">Course Evaluations</button>
                            <button class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-xs font-bold text-slate-700 hover:bg-slate-50" @click="router.visit(route('communications.index'))">
                                <ChatBubbleLeftRightIcon class="h-4 w-4" /> Communication
                            </button>
                        </div>
                    </div>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-black text-slate-900">Recent Submissions</h2>
                        <p class="mt-1 text-xs text-slate-400">Latest anonymous department feedback</p>
                    </div>
                    <button class="inline-flex items-center gap-1 text-xs font-bold text-blue-600" @click="router.visit(route('hod.feedbacks'))">View all <ArrowRightIcon class="h-3.5 w-3.5" /></button>
                </div>
                <div v-if="recent.length === 0" class="px-5 py-12 text-center text-sm text-slate-400">No pending feedback is available.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                            <tr><th class="px-5 py-3">Feedback ID</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Priority</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Submitted</th><th class="px-5 py-3 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-for="item in recent" :key="item.id" class="hover:bg-blue-50/40">
                                <td class="px-5 py-4 font-mono font-bold text-slate-500">{{ item.tracking_code }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ item.category }}</td>
                                <td class="px-5 py-4 font-bold capitalize" :class="priorityClass(item.priority)">{{ item.priority }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 font-bold capitalize" :class="statusClass(item.status)">{{ item.status?.replace('_', ' ') }}</span></td>
                                <td class="px-5 py-4 text-slate-500">{{ formatDate(item.submitted_at) }}</td>
                                <td class="px-5 py-4 text-right"><button class="font-bold text-blue-600" @click="router.visit(route('hod.feedbacks.show', item.id))">Review</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </HodLayout>
</template>
