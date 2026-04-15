<script setup>
import RectorLayout from '@/Layouts/RectorLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    feedbacks: { type: Array,  default: () => [] },
    stats:     { type: Object, default: () => ({}) },
    user:      { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const statusFilter   = ref('all');
const priorityFilter = ref('all');
const senderFilter   = ref('all');
const searchQuery    = ref('');

const filtered = computed(() =>
    props.feedbacks.filter(f => {
        const sOk = statusFilter.value   === 'all' || f.status      === statusFilter.value;
        const pOk = priorityFilter.value === 'all' || f.priority    === priorityFilter.value;
        const rOk = senderFilter.value   === 'all' || f.sender_role === senderFilter.value;
        const qOk = !searchQuery.value   ||
            f.tracking_code?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            f.category?.toLowerCase().includes(searchQuery.value.toLowerCase());
        return sOk && pOk && rOk && qOk;
    })
);

const statusColor = (s) => ({
    submitted:    'bg-blue-100 text-blue-700',
    under_review: 'bg-yellow-100 text-yellow-700',
    escalated:    'bg-orange-100 text-orange-700',
    resolved:     'bg-green-100 text-green-700',
    closed:       'bg-gray-100 text-gray-600',
}[s] ?? 'bg-gray-100 text-gray-600');

const priorityColor = (p) => ({
    low:    'bg-gray-100 text-gray-600',
    medium: 'bg-blue-100 text-blue-700',
    high:   'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
}[p] ?? 'bg-gray-100 text-gray-600');

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';
const view = (id) => router.visit(route('rector.feedbacks.show', id));
</script>

<template>
    <RectorLayout>
        <Head title="Campus Feedbacks" />
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Campus Feedbacks</h2>
                    <p class="text-xs text-gray-400 mt-0.5">All feedback across the entire institution</p>
                </div>
                <a :href="route('rector.dashboard')"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    ← Dashboard
                </a>
            </div>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium">
                {{ flash.success }}
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
                <div class="rounded-xl border border-gray-200 bg-white p-3 text-center">
                    <p class="text-xl font-bold text-gray-900">{{ stats.total }}</p>
                    <p class="text-xs text-gray-400">Total</p>
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 text-center">
                    <p class="text-xl font-bold text-blue-700">{{ stats.submitted }}</p>
                    <p class="text-xs text-blue-500">New</p>
                </div>
                <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-3 text-center">
                    <p class="text-xl font-bold text-yellow-700">{{ stats.under_review }}</p>
                    <p class="text-xs text-yellow-500">In Review</p>
                </div>
                <div class="rounded-xl border border-orange-100 bg-orange-50 p-3 text-center">
                    <p class="text-xl font-bold text-orange-700">{{ stats.escalated }}</p>
                    <p class="text-xs text-orange-500">Escalated</p>
                </div>
                <div class="rounded-xl border border-green-100 bg-green-50 p-3 text-center">
                    <p class="text-xl font-bold text-green-700">{{ stats.resolved }}</p>
                    <p class="text-xs text-green-500">Resolved</p>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-3 text-center">
                    <p class="text-xl font-bold text-red-700">{{ stats.urgent }}</p>
                    <p class="text-xs text-red-500">Urgent</p>
                </div>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-3 text-center">
                    <p class="text-xl font-bold text-indigo-700">{{ stats.from_student }}</p>
                    <p class="text-xs text-indigo-500">Students</p>
                </div>
                <div class="rounded-xl border border-teal-100 bg-teal-50 p-3 text-center">
                    <p class="text-xl font-bold text-teal-700">{{ stats.from_lecturer }}</p>
                    <p class="text-xs text-teal-500">Lecturers</p>
                </div>
            </div>

            <!-- Search + Filters -->
            <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">

                <!-- Search -->
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by tracking code or category..."
                        class="w-full rounded-lg border border-gray-200 pl-9 pr-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                    />
                </div>

                <!-- Filter rows -->
                <div class="flex flex-wrap gap-4">
                    <div class="flex gap-1.5 items-center flex-wrap">
                        <span class="text-xs text-gray-400 font-medium">Status:</span>
                        <button v-for="s in ['all','submitted','under_review','escalated','resolved']" :key="s"
                            @click="statusFilter = s"
                            class="rounded-full px-3 py-1 text-xs font-medium border transition capitalize"
                            :class="statusFilter === s ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                        >{{ s.replace('_', ' ') }}</button>
                    </div>

                    <div class="flex gap-1.5 items-center flex-wrap">
                        <span class="text-xs text-gray-400 font-medium">From:</span>
                        <button v-for="r in ['all','student','lecturer']" :key="r"
                            @click="senderFilter = r"
                            class="rounded-full px-3 py-1 text-xs font-medium border transition capitalize"
                            :class="senderFilter === r ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                        >{{ r }}</button>
                    </div>

                    <div class="flex gap-1.5 items-center flex-wrap">
                        <span class="text-xs text-gray-400 font-medium">Priority:</span>
                        <button v-for="p in ['all','urgent','high','medium','low']" :key="p"
                            @click="priorityFilter = p"
                            class="rounded-full px-3 py-1 text-xs font-medium border transition capitalize"
                            :class="priorityFilter === p ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                        >{{ p }}</button>
                    </div>
                </div>

                <p class="text-xs text-gray-400">
                    Showing <strong>{{ filtered.length }}</strong> of <strong>{{ feedbacks.length }}</strong> feedbacks
                </p>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">From</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Escalated</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Responses</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="filtered.length === 0">
                            <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                                <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                                </svg>
                                No feedbacks match your filters.
                            </td>
                        </tr>
                        <tr
                            v-for="f in filtered" :key="f.id"
                            class="hover:bg-indigo-50/40 transition cursor-pointer"
                            @click="view(f.id)"
                        >
                            <td class="px-4 py-3.5 font-mono text-xs font-bold text-gray-900">{{ f.tracking_code }}</td>
                            <td class="px-4 py-3.5 text-gray-700 max-w-[180px] truncate">{{ f.category }}</td>
                            <td class="px-4 py-3.5">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                    :class="f.sender_role === 'student' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700'">
                                    {{ f.sender_role }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize" :class="priorityColor(f.priority)">
                                    {{ f.priority }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize" :class="statusColor(f.status)">
                                    {{ f.status?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                <span v-if="f.is_escalated" class="text-orange-600 font-semibold">
                                    {{ f.escalated_to === 'rector' ? 'HOD → Dean → Rector' : 'HOD → Dean' }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3.5 text-gray-600 text-center">{{ f.responses_count }}</td>
                            <td class="px-4 py-3.5 text-gray-500 text-xs whitespace-nowrap">{{ formatDate(f.submitted_at) }}</td>
                            <td class="px-4 py-3.5">
                                <button
                                    @click.stop="view(f.id)"
                                    class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                                >
                                    View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </RectorLayout>
</template>