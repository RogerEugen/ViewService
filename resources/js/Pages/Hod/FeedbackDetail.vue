<script setup>
import HodLayout from '@/Layouts/HodLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    feedback: { type: Object, default: null },
    user:     { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const respondForm = useForm({ response: '' });
const escalateForm = useForm({ note: '' });
const showEscalateModal = ref(false);
const showResolveConfirm = ref(false);

const submitResponse = () => {
    respondForm.post(route('hod.feedbacks.respond', props.feedback.id), {
        onSuccess: () => respondForm.reset(),
    });
};

const submitEscalate = () => {
    escalateForm.post(route('hod.feedbacks.escalate', props.feedback.id), {
        onSuccess: () => {
            showEscalateModal.value = false;
            escalateForm.reset();
        },
    });
};

const submitResolve = () => {
    router.post(route('hod.feedbacks.resolve', props.feedback.id), {}, {
        onSuccess: () => showResolveConfirm.value = false,
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
const canRespond   = computed(() => !['resolved','closed','escalated'].includes(props.feedback?.status));
const canEscalate  = computed(() => !['resolved','closed','escalated'].includes(props.feedback?.status));
const canResolve   = computed(() => !['resolved','closed'].includes(props.feedback?.status));
</script>

<template>
    <HodLayout>
        <Head title="Feedback Detail" />
        <template #header>
            <div class="flex items-center gap-3">
                <button @click="router.visit(route('hod.feedbacks'))"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    ← Back
                </button>
                <h2 class="text-xl font-semibold text-gray-800">Feedback Detail</h2>
            </div>
        </template>

        <div class="py-8 px-4 max-w-3xl mx-auto space-y-5">

            <!-- Flash -->
            <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium">
                {{ flash.success }}
            </div>

            <div v-if="!feedback" class="text-center text-gray-400 py-12">Feedback not found.</div>

            <template v-else>

                <!-- Header card -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Tracking code</p>
                            <span class="font-mono text-xl font-bold text-gray-900 tracking-widest">
                                {{ feedback.tracking_code }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="priorityColor(feedback.priority)">
                                {{ feedback.priority }}
                            </span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="statusColor(feedback.status)">
                                {{ feedback.status?.replace('_', ' ') }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Category</p>
                            <p class="font-medium text-gray-800">{{ feedback.category }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Sender role</p>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 capitalize">
                                {{ feedback.sender_role }}
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

                    <!-- Escalation notice -->
                    <div v-if="feedback.is_escalated" class="rounded-lg bg-orange-50 border border-orange-100 px-3 py-2 text-xs text-orange-700">
                        Escalated to <strong class="capitalize">{{ feedback.escalated_to }}</strong>
                    </div>
                </div>

                <!-- Feedback content (decrypted) -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Feedback Content</h3>
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap">
                        {{ feedback.content }}
                    </div>
                    <p class="mt-2 text-xs text-gray-400">
                        Content is decrypted for authorised viewing only. Sender identity is never stored.
                    </p>
                </div>

                <!-- Responses history -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Response History ({{ feedback.responses?.length ?? 0 }})
                    </h3>
                    <div v-if="feedback.responses?.length > 0" class="space-y-3">
                        <div v-for="(r, i) in feedback.responses" :key="i"
                            class="rounded-lg border px-4 py-3"
                            :class="r.is_escalation ? 'border-orange-100 bg-orange-50' : 'border-gray-100 bg-gray-50'"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium capitalize text-indigo-700">
                                        {{ r.responder_role }}
                                    </span>
                                    <span v-if="r.is_escalation" class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
                                        Escalation note
                                    </span>
                                </div>
                                <span class="text-xs text-gray-400">{{ formatDate(r.responded_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ r.content }}</p>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-400 text-center py-4">
                        No responses yet.
                    </div>
                </div>

                <!-- Follow-ups from sender -->
                <div v-if="feedback.followups?.length > 0" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Follow-ups from Sender ({{ feedback.followups.length }})
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(f, i) in feedback.followups" :key="i" class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                            <div class="flex justify-between mb-1">
                                <span class="text-xs font-medium text-blue-700 capitalize">{{ f.direction.replace('_', ' → ') }}</span>
                                <span class="text-xs text-gray-400">{{ formatDate(f.sent_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ f.content }}</p>
                        </div>
                    </div>
                </div>

                <!-- Action panel -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Actions</h3>

                    <!-- Respond form -->
                    <div v-if="canRespond" class="mb-5">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Write a response</label>
                        <textarea
                            v-model="respondForm.response"
                            rows="4"
                            maxlength="3000"
                            placeholder="Write your response to this feedback. The sender will see this anonymously."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                        ></textarea>
                        <p v-if="respondForm.errors.response" class="mt-1 text-xs text-red-500">{{ respondForm.errors.response }}</p>
                        <button
                            @click="submitResponse"
                            :disabled="respondForm.processing || !respondForm.response"
                            class="mt-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ respondForm.processing ? 'Sending...' : 'Send Response' }}
                        </button>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex flex-wrap gap-3 pt-3 border-t border-gray-100">
                        <button
                            v-if="canEscalate"
                            @click="showEscalateModal = true"
                            class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700 hover:bg-orange-100"
                        >
                            Escalate to Dean
                        </button>
                        <button
                            v-if="canResolve"
                            @click="showResolveConfirm = true"
                            class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-100"
                        >
                            Mark as Resolved
                        </button>
                        <span v-if="feedback.status === 'resolved'" class="text-sm text-green-600 font-medium py-2">
                            ✓ Resolved on {{ formatDate(feedback.resolved_at) }}
                        </span>
                        <span v-if="feedback.status === 'escalated'" class="text-sm text-orange-600 font-medium py-2">
                            Escalated to {{ feedback.escalated_to }}
                        </span>
                    </div>
                </div>

            </template>

        </div>

        <!-- Escalate Modal -->
        <div v-if="showEscalateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Escalate to Dean</h3>
                <p class="text-xs text-gray-500 mb-4">
                    This feedback will be transferred to the Dean. The sender's identity remains anonymous.
                </p>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Note to Dean (optional)</label>
                <textarea
                    v-model="escalateForm.note"
                    rows="3"
                    maxlength="1000"
                    placeholder="Add context for the Dean..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 resize-none mb-4"
                ></textarea>
                <div class="flex gap-3">
                    <button
                        @click="submitEscalate"
                        :disabled="escalateForm.processing"
                        class="flex-1 rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-50"
                    >
                        {{ escalateForm.processing ? 'Escalating...' : 'Confirm Escalate' }}
                    </button>
                    <button
                        @click="showEscalateModal = false"
                        class="flex-1 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Resolve Confirm Modal -->
        <div v-if="showResolveConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Mark as Resolved</h3>
                <p class="text-sm text-gray-500 mb-5">
                    Confirm that this feedback has been addressed and can be closed. The sender will see it as resolved.
                </p>
                <div class="flex gap-3">
                    <button
                        @click="submitResolve"
                        class="flex-1 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700"
                    >
                        Yes, Mark Resolved
                    </button>
                    <button
                        @click="showResolveConfirm = false"
                        class="flex-1 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

    </HodLayout>
</template>