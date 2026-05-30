<script setup>
import HodLayout from '@/Layouts/HodLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats:     { type: Object, default: () => ({}) },
    evalStats: { type: Object, default: () => ({}) },
    recent:    { type: Array,  default: () => [] },
    user:      { type: Object, default: () => ({}) },
});

const resolutionRate = computed(() => {
    if (!props.stats.total) return 0;
    return Math.round((props.stats.resolved / props.stats.total) * 100);
});

const statusColor = (s) => ({
    submitted:    'bg-blue-100 text-blue-700',
    under_review: 'bg-yellow-100 text-yellow-700',
    escalated:    'bg-orange-100 text-orange-700',
    resolved:     'bg-green-100 text-green-700',
}[s] ?? 'bg-gray-100 text-gray-600');

const priorityColor = (p) => ({
    low:    'bg-gray-100 text-gray-500',
    medium: 'bg-blue-100 text-blue-700',
    high:   'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
}[p] ?? 'bg-gray-100 text-gray-600');

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB') : '—';

const ratingColor = (v) => {
    if (v >= 4) return 'text-green-600';
    if (v >= 3) return 'text-blue-600';
    if (v >= 2) return 'text-yellow-600';
    return 'text-red-600';
};
</script>

<template>
    <HodLayout>
        <Head title="HOD Dashboard" />
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">HOD Dashboard</h2>
                <p class="text-xs text-gray-400 mt-0.5">Welcome back, {{ user?.name }}</p>
            </div>
        </template>

        <div class="py-8 px-4 max-w-7xl mx-auto space-y-6">

            <!-- Feedback Stats -->
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Feedback Overview</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                        <p class="text-2xl font-black text-gray-900">{{ stats.total ?? 0 }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Total</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                        <p class="text-2xl font-black text-blue-700">{{ stats.submitted ?? 0 }}</p>
                        <p class="text-xs text-blue-500 mt-0.5">New</p>
                    </div>
                    <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-4 text-center">
                        <p class="text-2xl font-black text-yellow-700">{{ stats.under_review ?? 0 }}</p>
                        <p class="text-xs text-yellow-500 mt-0.5">In Review</p>
                    </div>
                    <div class="rounded-xl border border-orange-100 bg-orange-50 p-4 text-center">
                        <p class="text-2xl font-black text-orange-700">{{ stats.escalated ?? 0 }}</p>
                        <p class="text-xs text-orange-500 mt-0.5">Escalated</p>
                    </div>
                    <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-center">
                        <p class="text-2xl font-black text-green-700">{{ stats.resolved ?? 0 }}</p>
                        <p class="text-xs text-green-500 mt-0.5">Resolved</p>
                    </div>
                    <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center">
                        <p class="text-2xl font-black text-red-700">{{ stats.urgent ?? 0 }}</p>
                        <p class="text-xs text-red-500 mt-0.5">Urgent</p>
                    </div>
                </div>
            </div>

            <!-- Two column layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left: Resolution + Eval stats -->
                <div class="space-y-4">

                    <!-- Resolution rate -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Resolution Rate</h3>
                        <div class="flex items-end gap-3 mb-3">
                            <p class="text-4xl font-black text-gray-900">{{ resolutionRate }}%</p>
                            <p class="text-xs text-gray-400 mb-1.5">of all feedback</p>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-green-500 transition-all duration-700"
                                :style="{ width: resolutionRate + '%' }"></div>
                        </div>
                        <div class="mt-3 flex justify-between text-xs text-gray-400">
                            <span>0%</span>
                            <span>Target: 80%</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <!-- Evaluation summary -->
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-5">
                        <h3 class="text-sm font-semibold text-indigo-800 mb-4">Course Evaluations</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-indigo-600">Courses Evaluated</span>
                                <span class="text-lg font-black text-indigo-800">{{ evalStats.total_courses }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-indigo-600">Total Responses</span>
                                <span class="text-lg font-black text-indigo-800">{{ evalStats.total_responses }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-indigo-600">Avg Overall Rating</span>
                                <span class="text-lg font-black" :class="ratingColor(evalStats.avg_overall)">
                                    {{ evalStats.avg_overall }}/5
                                </span>
                            </div>
                        </div>
                        <button @click="router.visit(route('hod.evaluations'))"
                            class="mt-4 w-full rounded-lg bg-indigo-600 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                            View Evaluation Results →
                        </button>
                    </div>

                    <!-- Quick actions -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Quick Actions</h3>
                        <div class="space-y-2">
                            <button @click="router.visit(route('hod.feedbacks'))"
                                class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 text-left flex items-center justify-between">
                                <span>View All Feedbacks</span>
                                <span class="text-gray-400">→</span>
                            </button>
                            <button @click="router.visit(route('hod.evaluations'))"
                                class="w-full rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 text-left flex items-center justify-between">
                                <span>View Course Evaluations</span>
                                <span>→</span>
                            </button>
                        </div>
                        <div class="mt-3 rounded-lg border border-purple-100 bg-purple-50 px-3 py-2.5">
                            <p class="text-xs font-semibold text-purple-800">Smart Resolution Assistant</p>
                            <p class="text-xs text-purple-600 mt-0.5">
                                Mfumo unapendekeza suluhisho kutoka matatizo yaliyowahi kutatuliwa ili kurahisisha kazi ya HOD.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right: Recent feedbacks -->
                <div class="lg:col-span-2">
                    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                        <div class="border-b border-gray-100 px-5 py-3 bg-gray-50 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Recent Pending Feedback</h3>
                            <button @click="router.visit(route('hod.feedbacks'))"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                View all →
                            </button>
                        </div>

                        <div v-if="recent.length === 0" class="px-5 py-10 text-center">
                            <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                            </svg>
                            <p class="text-sm text-gray-400">No pending feedbacks</p>
                        </div>

                        <div v-else class="divide-y divide-gray-50">
                            <div v-for="f in recent" :key="f.id"
                                class="px-5 py-4 hover:bg-gray-50 cursor-pointer transition"
                                @click="router.visit(route('hod.feedbacks.show', f.id))">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-mono text-xs font-bold text-gray-900">{{ f.tracking_code }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="priorityColor(f.priority)">
                                                {{ f.priority }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 truncate">{{ f.category }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(f.submitted_at) }}</p>
                                    </div>
                                    <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="statusColor(f.status)">
                                        {{ f.status?.replace('_', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </HodLayout>
</template>
