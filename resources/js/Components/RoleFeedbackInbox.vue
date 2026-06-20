<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import MetricCard from '@/Components/MetricCard.vue';
import {
    ArrowRightIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    FunnelIcon,
    InboxIcon,
    MagnifyingGlassIcon,
    PaperAirplaneIcon,
    ShieldCheckIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    feedbacks: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    role: { type: String, required: true },
});

const statusFilter = ref('all');
const priorityFilter = ref('all');
const senderFilter = ref('all');
const searchQuery = ref('');

const config = computed(() => ({
    hod: {
        eyebrow: 'Department feedback',
        title: 'Department Feedback Inbox',
        description: 'Review, respond, resolve, or escalate anonymous issues from your department.',
        route: 'hod.feedbacks.show',
        accent: 'blue',
    },
    dean: {
        eyebrow: 'Faculty feedback',
        title: 'Faculty Feedback Inbox',
        description: 'Manage direct and escalated feedback while protecting every sender’s identity.',
        route: 'dean.feedbacks.show',
        accent: 'violet',
    },
    rector: {
        eyebrow: 'University feedback',
        title: 'Campus Feedback Inbox',
        description: 'A complete, anonymous view of high-level feedback across the institution.',
        route: 'rector.feedbacks.show',
        accent: 'indigo',
    },
}[props.role]));

const filtered = computed(() => props.feedbacks.filter((feedback) => {
    const query = searchQuery.value.trim().toLowerCase();
    return (statusFilter.value === 'all' || feedback.status === statusFilter.value)
        && (priorityFilter.value === 'all' || feedback.priority === priorityFilter.value)
        && (senderFilter.value === 'all' || feedback.sender_role === senderFilter.value)
        && (!query
            || String(feedback.tracking_code ?? '').toLowerCase().includes(query)
            || String(feedback.category ?? '').toLowerCase().includes(query));
}));

const statsCards = computed(() => [
    { label: 'Total feedback', value: props.stats.total ?? 0, icon: InboxIcon, tone: 'blue' },
    { label: 'New submissions', value: props.stats.submitted ?? 0, icon: PaperAirplaneIcon, tone: 'indigo' },
    { label: 'In review', value: props.stats.under_review ?? 0, icon: ClockIcon, tone: 'amber' },
    { label: 'Resolved', value: props.stats.resolved ?? 0, icon: CheckCircleIcon, tone: 'emerald' },
    { label: 'Urgent', value: props.stats.urgent ?? 0, icon: ExclamationTriangleIcon, tone: 'rose' },
]);

const statusClass = (status) => ({
    submitted: 'bg-blue-50 text-blue-700 ring-blue-100',
    under_review: 'bg-amber-50 text-amber-700 ring-amber-100',
    escalated: 'bg-orange-50 text-orange-700 ring-orange-100',
    resolved: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
}[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200');

const priorityClass = (priority) => ({
    low: 'bg-slate-100 text-slate-600',
    medium: 'bg-blue-50 text-blue-700',
    high: 'bg-orange-50 text-orange-700',
    urgent: 'bg-rose-50 text-rose-700',
}[priority] ?? 'bg-slate-100 text-slate-600');

const formatLabel = (value) => String(value ?? '')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
const formatDate = (value) => value
    ? new Date(value).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
    : '—';
const view = (id) => router.visit(route(config.value.route, id));
</script>

<template>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-7 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-slate-200 bg-white px-6 py-6 shadow-sm sm:px-7">
            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                <div class="max-w-2xl">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-blue-600">
                        <ShieldCheckIcon class="h-4 w-4" />
                        {{ config.eyebrow }}
                    </div>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950">{{ config.title }}</h1>
                    <p class="mt-1 max-w-xl text-sm leading-6 text-slate-500">{{ config.description }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-3">
                        <p class="text-2xl font-black text-slate-950">{{ filtered.length }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Visible</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-3">
                        <p class="text-2xl font-black text-slate-950">{{ stats.escalated ?? 0 }}</p>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Escalated</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <MetricCard
                v-for="card in statsCards"
                :key="card.label"
                :label="card.label"
                :value="card.value"
                :icon="card.icon"
                :tone="card.tone"
            />
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-[1fr_180px_180px_180px_auto]">
                <label class="relative">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-slate-400" />
                    <input v-model="searchQuery" type="search" placeholder="Search tracking code or category..."
                        class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-9 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </label>
                <select v-model="statusFilter" class="rounded-xl border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">All statuses</option>
                    <option value="submitted">New</option>
                    <option value="under_review">In review</option>
                    <option value="escalated">Escalated</option>
                    <option value="resolved">Resolved</option>
                </select>
                <select v-model="priorityFilter" class="rounded-xl border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">All priorities</option>
                    <option value="urgent">Urgent</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <select v-model="senderFilter" class="rounded-xl border-slate-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">All senders</option>
                    <option value="student">Students</option>
                    <option value="lecturer">Lecturers</option>
                </select>
                <div class="flex items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 text-xs font-black text-slate-600">
                    <FunnelIcon class="h-4 w-4" /> {{ filtered.length }} records
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-[10px] font-black uppercase tracking-[0.15em] text-slate-400">
                            <th class="px-5 py-4">Feedback</th>
                            <th class="px-5 py-4">Sender</th>
                            <th class="px-5 py-4">Priority</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Responses</th>
                            <th class="px-5 py-4">Submitted</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="feedback in filtered" :key="feedback.id" class="cursor-pointer transition hover:bg-blue-50/50" @click="view(feedback.id)">
                            <td class="px-5 py-4">
                                <p class="font-mono text-xs font-black text-slate-950">{{ feedback.tracking_code }}</p>
                                <p class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ feedback.category }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold capitalize text-slate-600">
                                    <UserGroupIcon class="h-4 w-4 text-slate-400" /> {{ feedback.sender_role }}
                                </span>
                            </td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-[10px] font-black" :class="priorityClass(feedback.priority)">{{ formatLabel(feedback.priority) }}</span></td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-[10px] font-black ring-1" :class="statusClass(feedback.status)">{{ formatLabel(feedback.status) }}</span></td>
                            <td class="px-5 py-4 text-center text-sm font-black text-slate-700">{{ feedback.responses_count ?? 0 }}</td>
                            <td class="px-5 py-4 text-xs font-medium text-slate-500">{{ formatDate(feedback.submitted_at) }}</td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100" @click.stop="view(feedback.id)">
                                    Review <ArrowRightIcon class="h-3.5 w-3.5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <button v-for="feedback in filtered" :key="feedback.id" type="button" class="w-full p-4 text-left hover:bg-slate-50" @click="view(feedback.id)">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-black text-slate-950">{{ feedback.tracking_code }}</p>
                            <p class="mt-1 truncate text-sm font-bold text-slate-700">{{ feedback.category }}</p>
                        </div>
                        <ArrowRightIcon class="h-5 w-5 flex-none text-slate-400" />
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black" :class="priorityClass(feedback.priority)">{{ formatLabel(feedback.priority) }}</span>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black ring-1" :class="statusClass(feedback.status)">{{ formatLabel(feedback.status) }}</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black capitalize text-slate-600">{{ feedback.sender_role }}</span>
                    </div>
                </button>
            </div>

            <div v-if="filtered.length === 0" class="grid min-h-64 place-items-center p-8 text-center">
                <div>
                    <ChatBubbleLeftRightIcon class="mx-auto h-10 w-10 text-slate-300" />
                    <p class="mt-3 font-black text-slate-700">No feedback matches these filters</p>
                    <p class="mt-1 text-sm text-slate-400">Change a dropdown or search term to see more records.</p>
                </div>
            </div>
        </section>
    </div>
</template>
