<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    feedback: { type: Object, default: null },
    code:     { type: String, default: '' },
    error:    { type: String, default: '' },
});

// Search form
const searchForm = useForm({ code: props.code ?? '' });

const search = () => {
    window.location.href = route('student.feedback.track') + '?code=' + searchForm.code.toUpperCase();
};

// Follow-up form
const followupForm = useForm({ message: '' });
const followupSent = ref(false);

const sendFollowup = () => {
    followupForm.post(route('student.feedback.followup'), {
        onSuccess: () => {
            followupSent.value = true;
            followupForm.reset();
        },
    });
};

// Status colours
const statusColor = (status) => {
    const map = {
        submitted:    'bg-blue-100 text-blue-700',
        under_review: 'bg-yellow-100 text-yellow-700',
        escalated:    'bg-orange-100 text-orange-700',
        resolved:     'bg-green-100 text-green-700',
        closed:       'bg-gray-100 text-gray-600',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600';
};

const priorityColor = (priority) => {
    const map = {
        low:    'bg-gray-100 text-gray-600',
        medium: 'bg-blue-100 text-blue-700',
        high:   'bg-orange-100 text-orange-700',
        urgent: 'bg-red-100 text-red-700',
    };
    return map[priority] ?? 'bg-gray-100 text-gray-600';
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString();
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Track Feedback" />

        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Track Feedback</h2>
        </template>

        <div class="py-8 px-4 max-w-2xl mx-auto space-y-6">

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
                        :disabled="!searchForm.code"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Track
                    </button>
                </div>
                <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
            </div>

            <!-- Feedback result -->
            <div v-if="feedback" class="space-y-4">

                <!-- Status card -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Tracking code</p>
                            <span class="font-mono text-lg font-bold text-gray-900 tracking-wider">
                                {{ feedback.tracking_code }}
                            </span>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="statusColor(feedback.status)">
                            {{ feedback.status?.replace('_', ' ') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
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
                            <p class="text-gray-700">{{ formatDate(feedback.submitted_at) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Resolved</p>
                            <p class="text-gray-700">{{ formatDate(feedback.resolved_at) }}</p>
                        </div>
                    </div>

                    <div v-if="feedback.is_escalated" class="mt-3 rounded-lg bg-orange-50 border border-orange-100 px-3 py-2 text-xs text-orange-700">
                        This feedback has been escalated to a higher authority.
                    </div>
                </div>

                <!-- Responses from institution -->
                <div v-if="feedback.responses?.length > 0" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Responses ({{ feedback.responses.length }})
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="(resp, i) in feedback.responses"
                            :key="i"
                            class="rounded-lg border border-gray-100 bg-gray-50 p-3"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium capitalize text-indigo-700">
                                    {{ resp.responder_role }}
                                </span>
                                <span class="text-xs text-gray-400">{{ formatDate(resp.responded_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ resp.content }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-5 text-center text-sm text-gray-400">
                    No responses yet. The institution will respond to your feedback soon.
                </div>

                <!-- Follow-up form -->
                <div v-if="feedback.status !== 'resolved' && feedback.status !== 'closed'"
                    class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Add a follow-up message</h3>

                    <div v-if="followupSent" class="rounded-lg bg-green-50 border border-green-100 px-3 py-2 text-sm text-green-700">
                        Follow-up sent successfully.
                    </div>

                    <form v-else @submit.prevent="sendFollowup" class="space-y-3">
                        <textarea
                            v-model="followupForm.message"
                            rows="3"
                            maxlength="2000"
                            placeholder="Add more context or ask a question anonymously..."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                            required
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="followupForm.processing || !followupForm.message"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ followupForm.processing ? 'Sending...' : 'Send Follow-up' }}
                        </button>
                    </form>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>