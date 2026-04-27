<script setup>
import DeanLayout from '@/Layouts/DeanLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats:      { type: Object, default: () => ({}) },
    evalStats:  { type: Object, default: () => ({}) },
    recent:     { type: Array,  default: () => [] },
    faculty_id: { type: Number, default: null },
    user:       { type: Object, default: () => ({}) },
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
    return 'text-yellow-600';
};
</script>

<template>
    <DeanLayout>
        <Head title="Dean Dashboard" />
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Dean Dashboard</h2>
                <p class="text-xs text-gray-400 mt-0.5">Welcome back, {{ user?.name }}</p>
            </div>
        </template>

        <div class="py-8 px-4 max-w-7xl mx-auto space-y-6">

            <!-- Stats -->
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Faculty Feedback Overview</h3>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left: Metrics -->
                <div class="space-y-4">

                    <!-- Resolution rate -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Faculty Resolution Rate</h3>
                        <div class="flex items-end gap-3 mb-3">
                            <p class="text-4xl font-black text-gray-900">{{ resolutionRate }}%</p>
                            <p class="text-xs text-gray-400 mb-1.5">resolved</p>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-purple-500 transition-all duration-700"
                                :style="{ width: resolutionRate + '%' }"></div>
                        </div>
                    </div>

                    <!-- Evaluation overview -->
                    <div class="rounded-xl border border-purple-100 bg-purple-50 p-5">
                        <h3 class="text-sm font-semibold text-purple-800 mb-4">Faculty Evaluation Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-purple-600">Departments Evaluated</span>
                                <span class="text-lg font-black text-purple-800">{{ evalStats.departments }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-purple-600">Courses Evaluated</span>
                                <span class="text-lg font-black text-purple-800">{{ evalStats.total_courses }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-purple-600">Total Responses</span>
                                <span class="text-lg font-black text-purple-800">{{ evalStats.total_responses }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-purple-600">Faculty Avg Rating</span>
                                <span class="text-lg font-black" :class="ratingColor(evalStats.avg_overall)">
                                    {{ evalStats.avg_overall }}/5
                                </span>
                            </div>
                        </div>
                        <button @click="router.visit(route('dean.evaluations'))"
                            class="mt-4 w-full rounded-lg bg-purple-600 py-2 text-xs font-semibold text-white hover:bg-purple-700">
                            View All Evaluations →
                        </button>
                    </div>

                    <!-- Quick actions -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Quick Actions</h3>
                        <div class="space-y-2">
                            <button @click="router.visit(route('dean.feedbacks'))"
                                class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 text-left flex items-center justify-between">
                                <span>Faculty Feedback Inbox</span>
                                <span class="text-gray-400">→</span>
                            </button>
                            <button @click="router.visit(route('dean.evaluations'))"
                                class="w-full rounded-lg border border-purple-200 bg-purple-50 px-4 py-2.5 text-xs font-medium text-purple-700 hover:bg-purple-100 text-left flex items-center justify-between">
                                <span>Course Evaluation Results</span>
                                <span>→</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Recent -->
                <div class="lg:col-span-2">
                    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                        <div class="border-b border-gray-100 px-5 py-3 bg-gray-50 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Recent Pending Feedback</h3>
                            <button @click="router.visit(route('dean.feedbacks'))"
                                class="text-xs font-medium text-purple-600 hover:text-purple-700">
                                View all →
                            </button>
                        </div>
                        <div v-if="recent.length === 0" class="px-5 py-10 text-center">
                            <p class="text-sm text-gray-400">No pending feedbacks</p>
                        </div>
                        <div v-else class="divide-y divide-gray-50">
                            <div v-for="f in recent" :key="f.id"
                                class="px-5 py-4 hover:bg-gray-50 cursor-pointer transition"
                                @click="router.visit(route('dean.feedbacks.show', f.id))">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-mono text-xs font-bold text-gray-900">{{ f.tracking_code }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="priorityColor(f.priority)">
                                                {{ f.priority }}
                                            </span>
                                            <span v-if="f.is_escalated" class="rounded-full bg-orange-100 px-2 py-0.5 text-xs text-orange-600">
                                                From HOD
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
    </DeanLayout>
</template>