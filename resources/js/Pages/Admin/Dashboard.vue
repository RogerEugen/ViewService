<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    stats:          { type: Object, default: () => ({}) },
    adminFeedbacks: { type: Array,  default: () => [] },
    user:           { type: Object, default: () => ({}) },
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
    <AdminLayout>
        <Head title="Admin Dashboard" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Admin Dashboard</h2>
                    <p class="text-xs text-gray-400 mt-0.5">System overview and management</p>
                </div>
            </div>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <!-- Welcome banner -->
            <div class="rounded-2xl bg-gradient-to-br from-gray-800 to-gray-900 px-6 py-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold">Welcome, {{ user?.name ?? 'Admin' }}</h3>
                        <p class="text-sm text-gray-300 mt-0.5">
                            You have
                            <strong class="text-white">{{ stats.feedback?.submitted ?? 0 }}</strong>
                            new feedbacks and
                            <strong class="text-yellow-400">{{ stats.feedback?.urgent ?? 0 }}</strong>
                            urgent issues requiring attention
                        </p>
                    </div>
                    <div class="hidden sm:flex h-16 w-16 items-center justify-center rounded-full bg-white/10">
                        <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Institution stats -->
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-3">Institution Structure</h3>
                <div class="grid grid-cols-3 gap-3">
                    <a :href="route('admin.ManageData')" class="rounded-xl border border-indigo-100 bg-indigo-50 p-5 text-center hover:bg-indigo-100 transition group">
                        <div class="flex h-10 w-10 mx-auto mb-3 items-center justify-center rounded-xl bg-indigo-100 group-hover:bg-indigo-200">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-black text-indigo-700">{{ stats.faculties }}</p>
                        <p class="text-xs text-indigo-500 mt-1 font-medium">Faculties</p>
                    </a>
                    <a :href="route('admin.ManageData')" class="rounded-xl border border-purple-100 bg-purple-50 p-5 text-center hover:bg-purple-100 transition group">
                        <div class="flex h-10 w-10 mx-auto mb-3 items-center justify-center rounded-xl bg-purple-100 group-hover:bg-purple-200">
                            <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-black text-purple-700">{{ stats.departments }}</p>
                        <p class="text-xs text-purple-500 mt-1 font-medium">Departments</p>
                    </a>
                    <a :href="route('admin.ManageData')" class="rounded-xl border border-teal-100 bg-teal-50 p-5 text-center hover:bg-teal-100 transition group">
                        <div class="flex h-10 w-10 mx-auto mb-3 items-center justify-center rounded-xl bg-teal-100 group-hover:bg-teal-200">
                            <svg class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-black text-teal-700">{{ stats.programs }}</p>
                        <p class="text-xs text-teal-500 mt-1 font-medium">Programs</p>
                    </a>
                </div>
            </div>

            <!-- Feedback overview -->
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-3">Campus Feedback Overview</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ stats.feedback?.total }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Total</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                        <p class="text-2xl font-bold text-blue-700">{{ stats.feedback?.submitted }}</p>
                        <p class="text-xs text-blue-500 mt-0.5">New</p>
                    </div>
                    <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-700">{{ stats.feedback?.under_review }}</p>
                        <p class="text-xs text-yellow-500 mt-0.5">In Review</p>
                    </div>
                    <div class="rounded-xl border border-orange-100 bg-orange-50 p-4 text-center">
                        <p class="text-2xl font-bold text-orange-700">{{ stats.feedback?.escalated }}</p>
                        <p class="text-xs text-orange-500 mt-0.5">Escalated</p>
                    </div>
                    <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-center">
                        <p class="text-2xl font-bold text-green-700">{{ stats.feedback?.resolved }}</p>
                        <p class="text-xs text-green-500 mt-0.5">Resolved</p>
                    </div>
                    <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center">
                        <p class="text-2xl font-bold text-red-700">{{ stats.feedback?.urgent }}</p>
                        <p class="text-xs text-red-500 mt-0.5">Urgent</p>
                    </div>
                </div>
            </div>

            <!-- Quick actions -->
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-3">Quick Actions</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <a :href="route('admin.ManageData')" class="rounded-xl border border-gray-200 bg-white p-4 flex items-center gap-3 hover:bg-gray-50 transition">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-100">
                            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-800">Add Faculty</p>
                            <p class="text-xs text-gray-400">Manage structure</p>
                        </div>
                    </a>
                    <a :href="route('admin.ManageData') + '?tab=departments'" class="rounded-xl border border-gray-200 bg-white p-4 flex items-center gap-3 hover:bg-gray-50 transition">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-purple-100">
                            <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-800">Add Department</p>
                            <p class="text-xs text-gray-400">Manage departments</p>
                        </div>
                    </a>
                    <a :href="route('admin.ManageData')" class="rounded-xl border border-gray-200 bg-white p-4 flex items-center gap-3 hover:bg-gray-50 transition">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-green-100">
                            <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-800">Import Users</p>
                            <p class="text-xs text-gray-400">CSV upload</p>
                        </div>
                    </a>
                    <a :href="route('admin.feedbacks')" class="rounded-xl border border-gray-200 bg-white p-4 flex items-center gap-3 hover:bg-gray-50 transition">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-orange-100">
                            <svg class="h-4 w-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-800">Feedbacks</p>
                            <p class="text-xs text-gray-400">{{ stats.feedback?.submitted }} new</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Admin feedback inbox preview -->
            <div v-if="adminFeedbacks.length > 0" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Feedbacks Sent to Admin</h3>
                        <p class="text-xs text-gray-400">Infrastructure and general suggestions</p>
                    </div>
                    <a :href="route('admin.feedbacks')" class="text-xs text-indigo-600 font-medium hover:underline">
                        View all →
                    </a>
                </div>
                <div class="divide-y divide-gray-50">
                    <div
                        v-for="f in adminFeedbacks" :key="f.id"
                        class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 cursor-pointer"
                        @click="router.visit(route('admin.feedbacks.show', f.id))"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ f.category }}</p>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ f.tracking_code }}</p>
                        </div>
                        <div class="flex items-center gap-2 ml-3 flex-shrink-0">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="priorityColor(f.priority)">{{ f.priority }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColor(f.status)">{{ f.status?.replace('_', ' ') }}</span>
                            <span class="text-xs text-gray-400">{{ formatDate(f.submitted_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>