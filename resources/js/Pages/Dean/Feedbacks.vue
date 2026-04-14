<script setup>
import DeanLayout from '@/Layouts/DeanLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    feedbacks:  { type: Array,  default: () => [] },
    stats:      { type: Object, default: () => ({}) },
    faculty_id: { type: Number, default: null },
    user:       { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const statusFilter   = ref('all');
const priorityFilter = ref('all');
const senderFilter   = ref('all');

const filtered = computed(() =>
    props.feedbacks.filter(f => {
        const sOk = statusFilter.value   === 'all' || f.status      === statusFilter.value;
        const pOk = priorityFilter.value === 'all' || f.priority    === priorityFilter.value;
        const rOk = senderFilter.value   === 'all' || f.sender_role === senderFilter.value;
        return sOk && pOk && rOk;
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
const view = (id) => router.visit(route('dean.feedbacks.show', id));
</script>

<template>
    <DeanLayout>
        <Head title="Faculty Feedback Inbox" />
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Faculty Feedback Inbox</h2>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium">
                {{ flash.success }}
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Total</p>
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-700">{{ stats.submitted }}</p>
                    <p class="text-xs text-blue-500 mt-0.5">New</p>
                </div>
                <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-700">{{ stats.under_review }}</p>
                    <p class="text-xs text-yellow-500 mt-0.5">In Review</p>
                </div>
                <div class="rounded-xl border border-orange-100 bg-orange-50 p-4 text-center">
                    <p class="text-2xl font-bold text-orange-700">{{ stats.escalated }}</p>
                    <p class="text-xs text-orange-500 mt-0.5">Escalated</p>
                </div>
                <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-center">
                    <p class="text-2xl font-bold text-green-700">{{ stats.resolved }}</p>
                    <p class="text-xs text-green-500 mt-0.5">Resolved</p>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center">
                    <p class="text-2xl font-bold text-red-700">{{ stats.urgent }}</p>
                    <p class="text-xs text-red-500 mt-0.5">Urgent</p>
                </div>
            </div>

            <!-- Info banner -->
            <div class="rounded-lg bg-purple-50 border border-purple-100 px-4 py-3 text-xs text-purple-700">
                You are viewing feedback escalated to your faculty from HODs, and direct feedback routed to Dean.
                You can respond, escalate to Rector, or resolve.
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 items-center">
                <div class="flex gap-1.5 flex-wrap">
                    <span class="text-xs text-gray-400 self-center">Status:</span>
                    <button v-for="s in ['all','submitted','under_review','escalated','resolved']" :key="s"
                        @click="statusFilter = s"
                        class="rounded-full px-3 py-1 text-xs font-medium border transition capitalize"
                        :class="statusFilter === s ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-600 border-gray-200'"
                    >{{ s.replace('_', ' ') }}</button>
                </div>
                <div class="flex gap-1.5 ml-auto">
                    <span class="text-xs text-gray-400 self-center">From:</span>
                    <button v-for="r in ['all','student','lecturer']" :key="r"
                        @click="senderFilter = r"
                        class="rounded-full px-3 py-1 text-xs font-medium border transition capitalize"
                        :class="senderFilter === r ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200'"
                    >{{ r }}</button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Tracking Code</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Category</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">From</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Priority</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Escalated</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="filtered.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-gray-400">No feedbacks found.</td>
                        </tr>
                        <tr v-for="f in filtered" :key="f.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-gray-900">{{ f.tracking_code }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ f.category }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                    :class="f.sender_role === 'student' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700'">
                                    {{ f.sender_role }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="priorityColor(f.priority)">
                                    {{ f.priority }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColor(f.status)">
                                    {{ f.status?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                <span v-if="f.is_escalated" class="text-orange-600 font-medium">
                                    From HOD
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(f.submitted_at) }}</td>
                            <td class="px-4 py-3">
                                <button @click="view(f.id)"
                                    class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-700">
                                    View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </DeanLayout>
</template>