<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { listenToFeedback } from '@/lib/realtime';

const props = defineProps({
    feedback:      { type: Object, default: null },
    code:          { type: String, default: '' },
    error:         { type: String, default: '' },
    department_id: { type: Number, default: null },
});

// Search
const searchForm = useForm({ code: props.code ?? '' });

const search = () => {
    if (!searchForm.code.trim()) return;
    window.location.href = route('student.track') + '?code=' + searchForm.code.trim().toUpperCase();
};

// Follow-up — include tracking_code in the form
const followupForm = useForm({
    tracking_code: props.feedback?.tracking_code ?? '',
    message:       '',
});

const followupSent = ref(false);

const sendFollowup = () => {
    // Make sure tracking_code is set from the current feedback
    followupForm.tracking_code = props.feedback?.tracking_code ?? props.code;

    followupForm.post(route('student.feedback.followup'), {
        onSuccess: () => {
            followupSent.value = true;
            followupForm.reset('message');
        },
        onError: (errors) => {
            console.error('Followup errors:', errors);
        },
    });
};

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

const formatDate = (d) => d ? new Date(d).toLocaleString() : '—';

const canFollowUp = computed(() =>
    props.feedback &&
    !['resolved', 'closed'].includes(props.feedback.status)
);

let stopRealtime = () => {};
onMounted(() => {
    stopRealtime = listenToFeedback(props.feedback?.realtime_channel, () => {
        router.reload({ only: ['feedback'], preserveScroll: true });
    });
});
onUnmounted(() => stopRealtime());

const statusSteps = computed(() => {
    const current = props.feedback?.status ?? 'submitted';
    const order = ['submitted', 'under_review', 'escalated', 'resolved'];
    const currentIndex = Math.max(order.indexOf(current), 0);
    return [
        { key: 'submitted', label: 'Submitted', done: currentIndex >= 0 },
        { key: 'under_review', label: 'Under Review', done: currentIndex >= 1 || current === 'closed' },
        { key: 'escalated', label: 'Escalated (if needed)', done: currentIndex >= 2 || current === 'resolved' || current === 'closed' },
        { key: 'resolved', label: 'Resolved', done: current === 'resolved' || current === 'closed' },
    ];
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Track Feedback" />
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Track Feedback</h2>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-5">

            <!-- Search box -->
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Enter your tracking code</h3>
                <div class="flex gap-3">
                    <input
                        v-model="searchForm.code"
                        type="text"
                        placeholder="e.g. FB-2024-XKQT"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        @keyup.enter="search"
                    />
                    <button
                        @click="search"
                        :disabled="!searchForm.code.trim()"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                    >Track</button>
                </div>

                <!-- Error -->
                <div v-if="error" class="mt-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2.5 flex gap-2 items-start">
                    <svg class="h-4 w-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <p class="text-sm text-red-600 font-medium">{{ error }}</p>
                </div>

                <!-- Hint -->
                <p class="mt-2 text-xs text-gray-400">
                    Only tracking codes from feedback you submitted as a student will work here.
                </p>
            </div>

            <div v-if="!feedback && !error && !code" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                    </svg>
                    <p class="text-sm text-gray-500 font-medium">Enter a tracking code to check feedback status</p>
                    <p class="text-xs text-gray-400 mt-1">You received this code when you submitted your feedback</p>
                    <a :href="route('student.feedback')"
                        class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Submit New Feedback
                    </a>
                </div>
                <aside class="space-y-3">
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <h4 class="text-sm font-bold text-indigo-800 mb-1">How Tracking Works</h4>
                        <p class="text-xs text-indigo-700">Code format mfano: <span class="font-mono font-semibold">FB-2026-ABCD</span>.</p>
                        <p class="text-xs text-indigo-700 mt-1">Tumia code uliyopata baada ya kutuma feedback.</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <h4 class="text-sm font-bold text-amber-800 mb-1">Important</h4>
                        <p class="text-xs text-amber-700">Keep your tracking code safe. It cannot be recovered because the feedback is anonymous.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <h4 class="text-sm font-bold text-gray-800 mb-2">Expected Status Flow</h4>
                        <ul class="space-y-1.5 text-xs text-gray-600">
                            <li>1. Submitted</li>
                            <li>2. Under Review</li>
                            <li>3. Escalated (if required)</li>
                            <li>4. Resolved</li>
                        </ul>
                    </div>
                </aside>
            </div>

            <!-- Feedback result -->
            <div v-if="feedback" class="space-y-4">

                <!-- Status card -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Tracking code</p>
                            <span class="font-mono text-xl font-bold text-gray-900 tracking-widest">
                                {{ feedback.tracking_code }}
                            </span>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="statusColor(feedback.status)">
                            {{ feedback.status?.replace('_', ' ') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm mb-3">
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Category</p>
                            <p class="font-medium text-gray-800">{{ feedback.category ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Priority</p>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="priorityColor(feedback.priority)">
                                {{ feedback.priority }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Submitted</p>
                            <p class="text-gray-700 text-xs">{{ formatDate(feedback.submitted_at) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Resolved</p>
                            <p class="text-gray-700 text-xs">{{ formatDate(feedback.resolved_at) }}</p>
                        </div>
                    </div>

                    <div v-if="feedback.is_escalated" class="rounded-lg bg-orange-50 border border-orange-100 px-3 py-2 text-xs text-orange-700">
                        This feedback has been escalated to a higher authority for review.
                    </div>
                    <div v-if="feedback.status === 'resolved'" class="rounded-lg bg-green-50 border border-green-100 px-3 py-2 text-xs text-green-700">
                        ✓ This feedback has been resolved on {{ formatDate(feedback.resolved_at) }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Progress Timeline</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div v-for="step in statusSteps" :key="step.key"
                            class="rounded-lg border px-3 py-2 text-center text-xs font-semibold"
                            :class="step.done ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-400'">
                            {{ step.done ? '✓ ' : '' }}{{ step.label }}
                        </div>
                    </div>
                </div>

                <!-- Responses from institution -->
                <div v-if="feedback.responses?.length > 0" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Responses ({{ feedback.responses.length }})
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(resp, i) in feedback.responses" :key="i"
                            class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium capitalize text-indigo-700">
                                    {{ resp.responder_role }}
                                </span>
                                <span class="text-xs text-gray-400">{{ formatDate(resp.responded_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ resp.content }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-5 text-center">
                    <p class="text-sm text-gray-400">No responses yet. The institution will respond to your feedback soon.</p>
                </div>

                <!-- Follow-ups history -->
                <div v-if="feedback.followups?.length > 0" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Follow-up Messages ({{ feedback.followups.length }})
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(fu, i) in feedback.followups" :key="i"
                            class="rounded-lg border px-3 py-2.5"
                            :class="fu.direction === 'sender_to_recipient'
                                ? 'border-blue-100 bg-blue-50'
                                : 'border-green-100 bg-green-50'"
                        >
                            <div class="flex justify-between mb-1">
                                <span class="text-xs font-semibold"
                                    :class="fu.direction === 'sender_to_recipient' ? 'text-blue-700' : 'text-green-700'">
                                    {{ fu.direction === 'sender_to_recipient' ? '↑ You sent' : '↓ Institution replied' }}
                                </span>
                                <span class="text-xs text-gray-400">{{ formatDate(fu.sent_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ fu.content }}</p>
                        </div>
                    </div>
                </div>

                <!-- Follow-up SEND form -->
                <div v-if="canFollowUp" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">Add a follow-up message</h3>
                    <p class="text-xs text-gray-400 mb-4">
                        Your message will be sent anonymously. Only users from your department can follow up on this feedback.
                    </p>

                    <!-- Follow-up errors -->
                    <div v-if="followupForm.errors.message" class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-600">
                        {{ followupForm.errors.message }}
                    </div>

                    <div v-if="followupSent" class="rounded-lg bg-green-50 border border-green-100 px-4 py-3 flex items-center gap-2">
                        <svg class="h-4 w-4 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm text-green-700 font-medium">Follow-up sent successfully.</p>
                    </div>

                    <form v-else @submit.prevent="sendFollowup" class="space-y-3">
                        <!-- Hidden tracking code -->
                        <input type="hidden" :value="feedback.tracking_code" />

                        <textarea
                            v-model="followupForm.message"
                            rows="4"
                            maxlength="2000"
                            placeholder="Add more context, clarification, or ask a question anonymously..."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                            required
                        ></textarea>
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-400">{{ followupForm.message.length }}/2000</p>
                            <button
                                type="submit"
                                :disabled="followupForm.processing || !followupForm.message.trim()"
                                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                <span v-if="followupForm.processing" class="flex items-center gap-2">
                                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                    </svg>
                                    Sending...
                                </span>
                                <span v-else>Send Follow-up</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div v-else-if="feedback.status === 'resolved' || feedback.status === 'closed'"
                    class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4 text-center text-sm text-gray-400">
                    Follow-ups are not available for resolved or closed feedback.
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
