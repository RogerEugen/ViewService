<script setup>
import LectureLayout from '@/Layouts/LectureLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    feedback: { type: Object, default: null },
    code:     { type: String, default: '' },
    error:    { type: String, default: '' },
});

const searchCode = ref(props.code ?? '');

const search = () => {
    if (!searchCode.value.trim()) return;
    window.location.href = route('lecture.feedback.track') + '?code=' + searchCode.value.trim().toUpperCase();
};

const followupForm = useForm({
    tracking_code: props.feedback?.tracking_code ?? '',
    message:       '',
});
const followupSent = ref(false);

const sendFollowup = () => {
    followupForm.post(route('lecture.feedback.followup'), {
        onSuccess: () => {
            followupSent.value = true;
            followupForm.reset('message');
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
</script>

<template>
    <LectureLayout>
        <Head title="Track Feedback" />
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Track Feedback</h2>
        </template>

        <div class="py-8 px-4 max-w-2xl mx-auto space-y-6">

            <!-- Search -->
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Enter your tracking code</h3>
                <div class="flex gap-3">
                    <input
                        v-model="searchCode"
                        type="text"
                        placeholder="e.g. FB-2024-XKQT"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        @keyup.enter="search"
                    />
                    <button
                        @click="search"
                        :disabled="!searchCode.trim()"
                        class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50"
                    >Track</button>
                </div>

                <div v-if="error" class="mt-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2.5 flex gap-2">
                    <svg class="h-4 w-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <p class="text-sm text-red-600">{{ error }}</p>
                </div>

                <div class="mt-3 rounded-lg bg-purple-50 border border-purple-100 px-3 py-2 text-xs text-purple-700">
                    Only tracking codes from feedback you submitted as a lecturer will work here.
                </div>
            </div>

            <!-- Result -->
            <div v-if="feedback" class="space-y-4">

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
                            <p class="text-xs text-gray-400 mb-0.5">Routed to</p>
                            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 capitalize">
                                {{ feedback.routed_to }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Submitted</p>
                            <p class="text-gray-700 text-xs">{{ formatDate(feedback.submitted_at) }}</p>
                        </div>
                    </div>

                    <div v-if="feedback.is_escalated" class="rounded-lg bg-orange-50 border border-orange-100 px-3 py-2 text-xs text-orange-700">
                        This feedback has been escalated to a higher authority.
                    </div>
                    <div v-if="feedback.status === 'resolved'" class="rounded-lg bg-green-50 border border-green-100 px-3 py-2 text-xs text-green-700">
                        This feedback has been resolved. {{ formatDate(feedback.resolved_at) }}
                    </div>
                </div>

                <!-- Responses -->
                <div v-if="feedback.responses?.length > 0" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Responses ({{ feedback.responses.length }})</h3>
                    <div class="space-y-3">
                        <div v-for="(resp, i) in feedback.responses" :key="i" class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium capitalize text-purple-700">
                                    {{ resp.responder_role }}
                                </span>
                                <span class="text-xs text-gray-400">{{ formatDate(resp.responded_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ resp.content }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-5 text-center text-sm text-gray-400">
                    No responses yet. The Rector will respond to your feedback soon.
                </div>

                <!-- Follow-up -->
                <div v-if="feedback.status !== 'resolved' && feedback.status !== 'closed'" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Add a follow-up message</h3>
                    <div v-if="followupSent" class="rounded-lg bg-green-50 border border-green-100 px-3 py-2 text-sm text-green-700">
                        Follow-up sent successfully.
                    </div>
                    <form v-else @submit.prevent="sendFollowup" class="space-y-3">
                        <input type="hidden" v-model="followupForm.tracking_code"/>
                        <textarea
                            v-model="followupForm.message"
                            rows="3"
                            maxlength="2000"
                            placeholder="Add more context anonymously..."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 resize-none"
                            required
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="followupForm.processing || !followupForm.message"
                            class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50"
                        >
                            {{ followupForm.processing ? 'Sending...' : 'Send Follow-up' }}
                        </button>
                    </form>
                </div>

            </div>

            <!-- No result yet -->
            <div v-else-if="!error && !code" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center">
                <p class="text-sm text-gray-400">Enter your tracking code above to check your feedback status.</p>
                <a :href="route('lecture.FeedBack')" class="mt-3 inline-block rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">
                    Submit Feedback
                </a>
            </div>

        </div>
    </LectureLayout>
</template>