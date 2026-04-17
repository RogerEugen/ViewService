<!-- Admin/Feedbacks.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    feedbacks: { type: Array,  default: () => [] },
    stats:     { type: Object, default: () => ({}) },
    user:      { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const statusFilter = ref('all');
const searchQuery  = ref('');

const filtered = computed(() =>
    props.feedbacks.filter(f => {
        const sOk = statusFilter.value === 'all' || f.status === statusFilter.value;
        const qOk = !searchQuery.value ||
            f.tracking_code?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            f.category?.toLowerCase().includes(searchQuery.value.toLowerCase());
        return sOk && qOk;
    })
);

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
        <Head title="Admin Feedbacks" />
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Feedbacks Sent to Admin</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Infrastructure and general suggestions</p>
                </div>
                <a :href="route('admin.dashboard')" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    ← Dashboard
                </a>
            </div>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium">
                {{ flash.success }}
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                    <p class="text-xs text-gray-400">Total</p>
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-700">{{ stats.submitted }}</p>
                    <p class="text-xs text-blue-500">New</p>
                </div>
                <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-700">{{ stats.under_review }}</p>
                    <p class="text-xs text-yellow-500">In Review</p>
                </div>
                <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-center">
                    <p class="text-2xl font-bold text-green-700">{{ stats.resolved }}</p>
                    <p class="text-xs text-green-500">Resolved</p>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center">
                    <p class="text-2xl font-bold text-red-700">{{ stats.urgent }}</p>
                    <p class="text-xs text-red-500">Urgent</p>
                </div>
            </div>

            <!-- Search + filter -->
            <div class="flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="searchQuery" type="text" placeholder="Search tracking code or category..."
                        class="w-full rounded-lg border border-gray-200 pl-9 pr-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"/>
                </div>
                <div class="flex gap-1.5">
                    <button v-for="s in ['all','submitted','under_review','resolved']" :key="s"
                        @click="statusFilter = s"
                        class="rounded-full px-3 py-1.5 text-xs font-medium border transition capitalize"
                        :class="statusFilter === s ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200'">
                        {{ s.replace('_', ' ') }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">From</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="filtered.length === 0">
                            <td colspan="7" class="py-12 text-center text-gray-400">No feedbacks found.</td>
                        </tr>
                        <tr v-for="f in filtered" :key="f.id" class="hover:bg-gray-50 cursor-pointer" @click="router.visit(route('admin.feedbacks.show', f.id))">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-gray-900">{{ f.tracking_code }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ f.category }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                    :class="f.sender_role === 'student' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700'">
                                    {{ f.sender_role }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize" :class="priorityColor(f.priority)">{{ f.priority }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize" :class="statusColor(f.status)">{{ f.status?.replace('_', ' ') }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ formatDate(f.submitted_at) }}</td>
                            <td class="px-4 py-3">
                                <button @click.stop="router.visit(route('admin.feedbacks.show', f.id))"
                                    class="rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-900">
                                    View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AdminLayout>
</template>