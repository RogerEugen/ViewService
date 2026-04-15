<script setup>
import RectorLayout from '@/Layouts/RectorLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    stats:  { type: Object, default: () => ({}) },
    recent: { type: Array,  default: () => [] },
    user:   { type: Object, default: () => ({}) },
});

const statusColor = (s) => ({
    submitted:    'bg-blue-100 text-blue-700',
    under_review: 'bg-yellow-100 text-yellow-700',
    escalated:    'bg-orange-100 text-orange-700',
    resolved:     'bg-green-100 text-green-700',
}[s] ?? 'bg-gray-100 text-gray-600');

const priorityColor = (p) => ({
    low:    'bg-gray-100 text-gray-600',
    medium: 'bg-blue-100 text-blue-700',
    high:   'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
}[p] ?? 'bg-gray-100 text-gray-600');

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';
</script>

<template>
    <RectorLayout>
        <Head title="Rector Dashboard" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Rector Dashboard</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Campus-wide feedback overview</p>
                </div>
                <a :href="route('rector.feedbacks')"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    View All Feedbacks
                </a>
            </div>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <!-- Welcome banner -->
            <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 px-6 py-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold">
                            Welcome, {{ user?.name ?? 'Rector' }}
                        </h3>
                        <p class="text-sm text-indigo-200 mt-0.5">
                            You have
                            <strong class="text-white">{{ stats.submitted + stats.under_review }}</strong>
                            active feedbacks requiring attention
                        </p>
                    </div>
                    <div class="hidden sm:flex h-16 w-16 items-center justify-center rounded-full bg-white/10">
                        <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6.75v6.75"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="rounded-xl border border-gray-200 bg-white p-5 text-center">
                    <p class="text-3xl font-black text-gray-900">{{ stats.total }}</p>
                    <p class="text-xs text-gray-400 mt-1 font-medium">Total Campus</p>
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-5 text-center">
                    <p class="text-3xl font-black text-blue-700">{{ stats.submitted }}</p>
                    <p class="text-xs text-blue-500 mt-1 font-medium">New</p>
                </div>
                <div class="rounded-xl border border-orange-100 bg-orange-50 p-5 text-center">
                    <p class="text-3xl font-black text-orange-700">{{ stats.escalated }}</p>
                    <p class="text-xs text-orange-500 mt-1 font-medium">Escalated to You</p>
                </div>
                <div class="rounded-xl border border-green-100 bg-green-50 p-5 text-center">
                    <p class="text-3xl font-black text-green-700">{{ stats.resolved }}</p>
                    <p class="text-xs text-green-500 mt-1 font-medium">Resolved</p>
                </div>
            </div>

            <!-- Secondary stats row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-700">{{ stats.under_review }}</p>
                    <p class="text-xs text-yellow-500 mt-0.5">In Review</p>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center">
                    <p class="text-2xl font-bold text-red-700">{{ stats.urgent }}</p>
                    <p class="text-xs text-red-500 mt-0.5">Urgent Priority</p>
                </div>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-700">{{ stats.from_student }}</p>
                    <p class="text-xs text-indigo-500 mt-0.5">From Students</p>
                </div>
                <div class="rounded-xl border border-teal-100 bg-teal-50 p-4 text-center">
                    <p class="text-2xl font-bold text-teal-700">{{ stats.from_lecturer }}</p>
                    <p class="text-xs text-teal-500 mt-0.5">From Lecturers</p>
                </div>
            </div>

            <!-- Recent active feedbacks -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Active Feedbacks</h3>
                        <p class="text-xs text-gray-400">Feedbacks requiring your attention</p>
                    </div>
                    <a :href="route('rector.feedbacks')" class="text-xs text-indigo-600 font-medium hover:underline">
                        View all →
                    </a>
                </div>

                <div v-if="recent.length === 0" class="px-5 py-10 text-center text-sm text-gray-400">
                    No active feedbacks at this time.
                </div>

                <div v-else class="divide-y divide-gray-50">
                    <div
                        v-for="f in recent"
                        :key="f.id"
                        class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition cursor-pointer"
                        @click="router.visit(route('rector.feedbacks.show', f.id))"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex-shrink-0">
                                <span class="font-mono text-xs font-bold text-gray-500">{{ f.tracking_code }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ f.category }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-xs text-gray-400 capitalize">from {{ f.sender_role }}</span>
                                    <span v-if="f.is_escalated" class="text-xs text-orange-500 font-medium">• escalated</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="priorityColor(f.priority)">
                                {{ f.priority }}
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColor(f.status)">
                                {{ f.status?.replace('_', ' ') }}
                            </span>
                            <span class="text-xs text-gray-400">{{ formatDate(f.submitted_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resolution rate card -->
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Campus Feedback Overview</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                    <!-- Resolution rate -->
                    <div class="rounded-lg bg-gray-50 p-4 text-center">
                        <p class="text-xs text-gray-400 mb-2">Resolution Rate</p>
                        <p class="text-2xl font-bold"
                            :class="stats.total > 0 && (stats.resolved / stats.total) > 0.7 ? 'text-green-600' : 'text-orange-500'"
                        >
                            {{ stats.total > 0 ? Math.round((stats.resolved / stats.total) * 100) : 0 }}%
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ stats.resolved }} of {{ stats.total }} resolved</p>
                    </div>

                    <!-- Escalation rate -->
                    <div class="rounded-lg bg-gray-50 p-4 text-center">
                        <p class="text-xs text-gray-400 mb-2">Escalation Rate</p>
                        <p class="text-2xl font-bold"
                            :class="stats.total > 0 && (stats.escalated / stats.total) > 0.3 ? 'text-red-600' : 'text-blue-600'"
                        >
                            {{ stats.total > 0 ? Math.round((stats.escalated / stats.total) * 100) : 0 }}%
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ stats.escalated }} escalated</p>
                    </div>

                    <!-- Urgent rate -->
                    <div class="rounded-lg bg-gray-50 p-4 text-center">
                        <p class="text-xs text-gray-400 mb-2">Urgent Issues</p>
                        <p class="text-2xl font-bold"
                            :class="stats.urgent > 0 ? 'text-red-600' : 'text-green-600'"
                        >
                            {{ stats.urgent }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">requiring immediate action</p>
                    </div>

                </div>
            </div>

        </div>
    </RectorLayout>
</template>