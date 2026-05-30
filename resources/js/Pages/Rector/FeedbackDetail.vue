<script setup>
import RectorLayout from '@/Layouts/RectorLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    feedback: { type: Object, default: null },
    user:     { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const respondForm        = useForm({ response: '' });
const showResolveConfirm = ref(false);

const submitResponse = () => {
    respondForm.post(route('rector.feedbacks.respond', props.feedback.id), {
        onSuccess: () => respondForm.reset(),
    });
};

const submitResolve = () => {
    router.post(route('rector.feedbacks.resolve', props.feedback.id), {}, {
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
const canRespond = computed(() => !['resolved', 'closed'].includes(props.feedback?.status));
const canResolve = computed(() => !['resolved', 'closed'].includes(props.feedback?.status));

// Build escalation trail
const escalationTrail = computed(() => {
    if (!props.feedback?.is_escalated) return [];
    const trail = ['Sender (Anonymous)'];
    // Check response history to see who handled it
    const roles = (props.feedback.responses ?? []).map(r => r.responder_role);
    if (roles.includes('hod'))  trail.push('HOD');
    if (roles.includes('dean')) trail.push('Dean');
    trail.push('Rector (You)');
    return trail;
});
</script>

<template>
    <RectorLayout>
        <Head title="Feedback Detail" />
        <template #header>
            <div class="flex items-center gap-3">
                <button @click="router.visit(route('rector.feedbacks'))"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    ← Back to Feedbacks
                </button>
                <h2 class="text-xl font-semibold text-gray-800">Feedback Detail</h2>
            </div>
        </template>

        <div class="py-8 px-4 max-w-5xl mx-auto space-y-5">

            <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                {{ flash.success }}
            </div>

            <div v-if="!feedback" class="text-center text-gray-400 py-16">
                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Feedback not found.
            </div>

            <template v-else>

                <!-- Header card -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Tracking code</p>
                            <span class="font-mono text-2xl font-black text-gray-900 tracking-widest">
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
                            <p class="font-semibold text-gray-800">{{ feedback.category }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Sender role</p>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                                :class="feedback.sender_role === 'student' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700'">
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

                    <!-- Escalation trail -->
                    <div v-if="feedback.is_escalated && escalationTrail.length > 0" class="rounded-lg bg-orange-50 border border-orange-100 px-4 py-3">
                        <p class="text-xs font-semibold text-orange-700 mb-2">Escalation Trail</p>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <template v-for="(step, i) in escalationTrail" :key="i">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="i === escalationTrail.length - 1
                                        ? 'bg-orange-200 text-orange-800 font-bold'
                                        : 'bg-orange-100 text-orange-600'">
                                    {{ step }}
                                </span>
                                <svg v-if="i < escalationTrail.length - 1" class="h-3 w-3 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Feedback content -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Feedback Content</h3>
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                            🔓 Decrypted
                        </span>
                    </div>
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-4 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap min-h-[80px]">
                        {{ feedback.content }}
                    </div>
                    <p class="mt-2 text-xs text-gray-400">
                        Content decrypted for authorised Rector viewing only. Sender identity is never stored.
                    </p>
                </div>

                <!-- Full response history -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Full Response History ({{ feedback.responses?.length ?? 0 }})
                    </h3>
                    <div v-if="feedback.responses?.length > 0" class="space-y-3">
                        <div v-for="(r, i) in feedback.responses" :key="i"
                            class="rounded-lg border px-4 py-3"
                            :class="r.is_escalation ? 'border-orange-100 bg-orange-50' : 'border-gray-100 bg-gray-50'"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                                        :class="{
                                            'bg-blue-100 text-blue-700': r.responder_role === 'hod',
                                            'bg-purple-100 text-purple-700': r.responder_role === 'dean',
                                            'bg-indigo-100 text-indigo-700': r.responder_role === 'rector',
                                        }"
                                    >{{ r.responder_role }}</span>
                                    <span v-if="r.is_escalation" class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
                                        escalation note
                                    </span>
                                </div>
                                <span class="text-xs text-gray-400">{{ formatDate(r.responded_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ r.content }}</p>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-400 text-center py-6 rounded-lg bg-gray-50">
                        No responses yet — be the first to respond.
                    </div>
                </div>

                <!-- Follow-ups -->
                <div v-if="feedback.followups?.length > 0" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        Anonymous Follow-ups ({{ feedback.followups.length }})
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(f, i) in feedback.followups" :key="i"
                            class="rounded-lg border px-4 py-3"
                            :class="f.direction === 'sender_to_recipient'
                                ? 'border-blue-100 bg-blue-50'
                                : 'border-green-100 bg-green-50'"
                        >
                            <div class="flex justify-between mb-1">
                                <span class="text-xs font-semibold"
                                    :class="f.direction === 'sender_to_recipient' ? 'text-blue-700' : 'text-green-700'"
                                >
                                    {{ f.direction === 'sender_to_recipient' ? '← From Sender' : '→ From Institution' }}
                                </span>
                                <span class="text-xs text-gray-400">{{ formatDate(f.sent_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ f.content }}</p>
                        </div>
                    </div>
                </div>

                <!-- Action panel -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Rector Actions</h3>

                    <!-- Respond -->
                    <div v-if="canRespond" class="mb-5">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">
                            Write your response (anonymous sender will see this via tracking code)
                        </label>
                        <textarea
                            v-model="respondForm.response"
                            rows="5"
                            maxlength="3000"
                            placeholder="Write your official response as Rector. This will be visible to the anonymous sender when they check their tracking code."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                        ></textarea>
                        <div class="mt-1 flex items-center justify-between">
                            <p v-if="respondForm.errors.response" class="text-xs text-red-500">{{ respondForm.errors.response }}</p>
                            <span class="ml-auto text-xs text-gray-400">{{ respondForm.response.length }}/3000</span>
                        </div>
                        <button
                            @click="submitResponse"
                            :disabled="respondForm.processing || !respondForm.response"
                            class="mt-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ respondForm.processing ? 'Sending Response...' : 'Send Official Response' }}
                        </button>
                    </div>

                    <!-- Status info + resolve -->
                    <div class="flex flex-wrap gap-3 pt-3" :class="canRespond ? 'border-t border-gray-100' : ''">
                        <button
                            v-if="canResolve"
                            @click="showResolveConfirm = true"
                            class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-100"
                        >
                            ✓ Mark as Resolved
                        </button>

                        <div v-if="feedback.status === 'resolved'" class="flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 px-4 py-2">
                            <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm text-green-700 font-semibold">Resolved on {{ formatDate(feedback.resolved_at) }}</span>
                        </div>

                        <div v-if="feedback.is_escalated" class="flex items-center gap-2 rounded-lg bg-orange-50 border border-orange-100 px-4 py-2">
                            <svg class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <span class="text-sm text-orange-700 font-medium">This feedback was escalated through the chain</span>
                        </div>
                    </div>
                </div>

            </template>
        </div>

        <!-- Resolve Confirm Modal -->
        <div v-if="showResolveConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-center text-base font-bold text-gray-900 mb-1">Mark as Resolved</h3>
                <p class="text-center text-sm text-gray-500 mb-5">
                    This will mark the feedback as fully resolved. The anonymous sender will see this status when they check their tracking code.
                </p>
                <div class="flex gap-3">
                    <button @click="submitResolve"
                        class="flex-1 rounded-xl bg-green-600 px-4 py-3 text-sm font-bold text-white hover:bg-green-700">
                        Confirm — Mark Resolved
                    </button>
                    <button @click="showResolveConfirm = false"
                        class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

    </RectorLayout>
</template>
