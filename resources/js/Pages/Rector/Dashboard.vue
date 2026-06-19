<script setup>
import RectorLayout from '@/Layouts/RectorLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ChatBubbleLeftRightIcon, CheckCircleIcon, InboxIcon, StarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recent: { type: Array, default: () => [] },
    user: { type: Object, default: () => ({}) },
});

const resolutionRate = computed(() => props.stats.total
    ? Math.round((props.stats.resolved / props.stats.total) * 100)
    : 0);

const trendBars = computed(() => [
    props.stats.from_student ?? 0,
    props.stats.submitted ?? 0,
    props.stats.under_review ?? 0,
    props.stats.total ?? 0,
    props.stats.resolved ?? 0,
    props.stats.from_lecturer ?? 0,
]);
const maxTrend = computed(() => Math.max(...trendBars.value, 1));

const statusClass = (status) => ({
    submitted: 'bg-slate-100 text-slate-600',
    under_review: 'bg-amber-50 text-amber-700',
    escalated: 'bg-blue-50 text-blue-700',
    resolved: 'bg-emerald-50 text-emerald-700',
}[status] ?? 'bg-slate-100 text-slate-600');
</script>

<template>
    <RectorLayout>
        <Head title="Executive Dashboard" />

        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black text-slate-950">Executive Dashboard</h1>
                    <p class="mt-1 text-xs text-slate-500">Real-time anonymous feedback across the university</p>
                </div>
                <div class="flex gap-2">
                    <button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700" @click="router.visit(route('rector.analytics'))">View Analytics</button>
                    <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white" @click="router.visit(route('communications.index'))"><ChatBubbleLeftRightIcon class="h-4 w-4" /> Communication</button>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between"><span class="rounded-xl bg-blue-50 p-2.5 text-blue-600"><InboxIcon class="h-5 w-5" /></span><span class="text-[10px] font-bold text-emerald-600">Campus wide</span></div>
                    <p class="mt-4 text-xs font-medium text-slate-500">Total Feedback</p><p class="mt-1 text-2xl font-black text-slate-950">{{ stats.total ?? 0 }}</p>
                    <div class="mt-4 h-1 rounded-full bg-slate-100"><div class="h-1 w-3/4 rounded-full bg-blue-600"></div></div>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between"><span class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600"><CheckCircleIcon class="h-5 w-5" /></span><span class="text-[10px] font-bold text-emerald-600">{{ stats.resolved ?? 0 }} resolved</span></div>
                    <p class="mt-4 text-xs font-medium text-slate-500">Resolution Rate</p><p class="mt-1 text-2xl font-black text-slate-950">{{ resolutionRate }}%</p>
                    <div class="mt-4 h-1 rounded-full bg-slate-100"><div class="h-1 rounded-full bg-emerald-500" :style="{ width: `${resolutionRate}%` }"></div></div>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between"><span class="rounded-xl bg-amber-50 p-2.5 text-amber-600"><StarIcon class="h-5 w-5" /></span><span class="text-[10px] font-bold text-rose-500">{{ stats.urgent ?? 0 }} urgent</span></div>
                    <p class="mt-4 text-xs font-medium text-slate-500">Executive Attention Score</p><p class="mt-1 text-2xl font-black text-slate-950">{{ Math.max(0, 100 - ((stats.urgent ?? 0) * 5)) }}%</p>
                    <div class="mt-4 h-1 rounded-full bg-slate-100"><div class="h-1 w-4/5 rounded-full bg-amber-400"></div></div>
                </article>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between"><div><h2 class="text-sm font-black text-slate-900">Source Comparison</h2><p class="mt-1 text-xs text-slate-400">Anonymous feedback by sender role</p></div><span class="rounded-lg border border-slate-200 px-3 py-2 text-[10px] font-bold text-slate-500">Current period</span></div>
                    <div class="mt-7 space-y-6">
                        <div><div class="mb-2 flex justify-between text-xs font-semibold"><span>Student Feedback</span><span class="text-blue-600">{{ stats.from_student ?? 0 }} records</span></div><div class="h-3 overflow-hidden rounded-full bg-blue-100"><div class="h-full rounded-full bg-blue-600" :style="{ width: `${((stats.from_student ?? 0) / Math.max(stats.total ?? 0, 1)) * 100}%` }"></div></div></div>
                        <div><div class="mb-2 flex justify-between text-xs font-semibold"><span>Lecturer Feedback</span><span class="text-indigo-600">{{ stats.from_lecturer ?? 0 }} records</span></div><div class="h-3 overflow-hidden rounded-full bg-indigo-100"><div class="h-full rounded-full bg-indigo-400" :style="{ width: `${((stats.from_lecturer ?? 0) / Math.max(stats.total ?? 0, 1)) * 100}%` }"></div></div></div>
                        <div><div class="mb-2 flex justify-between text-xs font-semibold"><span>Currently Escalated</span><span class="text-amber-600">{{ stats.escalated ?? 0 }} records</span></div><div class="h-3 overflow-hidden rounded-full bg-amber-100"><div class="h-full rounded-full bg-amber-400" :style="{ width: `${((stats.escalated ?? 0) / Math.max(stats.total ?? 0, 1)) * 100}%` }"></div></div></div>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between"><div><h2 class="text-sm font-black text-slate-900">Feedback Trends</h2><p class="mt-1 text-xs text-slate-400">Executive workflow snapshot</p></div><span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-bold text-blue-600">LIVE</span></div>
                    <div class="mt-8 flex h-48 items-end gap-3">
                        <div v-for="(value, index) in trendBars" :key="index" class="flex h-full flex-1 flex-col justify-end">
                            <div class="rounded-t-lg" :class="index === 3 ? 'bg-blue-600' : 'bg-blue-200'" :style="{ height: `${Math.max((value / maxTrend) * 100, 5)}%` }"></div>
                            <span class="mt-3 text-center text-[9px] font-bold text-slate-400">{{ ['STU','NEW','REV','ALL','RES','LEC'][index] }}</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><h2 class="text-sm font-black text-slate-900">Priority High-Level Feedback</h2><p class="mt-1 text-xs text-slate-400">Records requiring executive oversight</p></div><button class="text-xs font-bold text-blue-600" @click="router.visit(route('rector.feedbacks'))">View all records →</button></div>
                <div v-if="recent.length === 0" class="px-5 py-12 text-center text-sm text-slate-400">No active executive feedback.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400"><tr><th class="px-5 py-3">Source</th><th class="px-5 py-3">Summary</th><th class="px-5 py-3">Urgency</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-for="item in recent" :key="item.id" class="hover:bg-blue-50/40">
                                <td class="px-5 py-4 font-bold capitalize text-slate-700">● {{ item.sender_role }}</td>
                                <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ item.category }}</p><p class="mt-1 font-mono text-[10px] text-slate-400">{{ item.tracking_code }}</p></td>
                                <td class="px-5 py-4 font-bold uppercase" :class="item.priority === 'urgent' ? 'text-rose-600' : 'text-blue-600'">{{ item.priority }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 font-bold capitalize" :class="statusClass(item.status)">{{ item.status?.replace('_', ' ') }}</span></td>
                                <td class="px-5 py-4 text-right"><button class="font-bold text-blue-600" @click="router.visit(route('rector.feedbacks.show', item.id))">Review</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </RectorLayout>
</template>
