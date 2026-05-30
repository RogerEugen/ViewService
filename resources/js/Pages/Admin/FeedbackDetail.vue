<!-- Admin/FeedbackDetail.vue -->
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    feedback: { type: Object, default: null },
    suggestions: { type: Array, default: () => [] },
    user:     { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const respondForm        = useForm({ response: '' });
const showResolveConfirm = ref(false);
const resolveForm = useForm({ resolution: '' });

const submitResponse = () => {
    respondForm.post(route('admin.feedbacks.respond', props.feedback.id), {
        onSuccess: () => respondForm.reset(),
    });
};

const submitResolve = () => {
    resolveForm.post(route('admin.feedbacks.resolve', props.feedback.id), {
        onSuccess: () => showResolveConfirm.value = false,
    });
};

const statusColor = (s) => ({
    submitted:    'bg-blue-100 text-blue-700',
    under_review: 'bg-yellow-100 text-yellow-700',
    resolved:     'bg-green-100 text-green-700',
}[s] ?? 'bg-gray-100 text-gray-600');

const priorityColor = (p) => ({
    low:    'bg-gray-100 text-gray-600',
    medium: 'bg-blue-100 text-blue-700',
    high:   'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
}[p] ?? 'bg-gray-100 text-gray-600');

const formatDate = (d) => d ? new Date(d).toLocaleString() : '—';
const canRespond = computed(() => !['resolved','closed'].includes(props.feedback?.status));
const canResolve = computed(() => !['resolved','closed'].includes(props.feedback?.status));
</script>

<template>
    <AdminLayout>
        <Head title="Feedback Detail" />
        <template #header>
            <div class="flex items-center gap-3">
                <button @click="router.visit(route('admin.feedbacks'))"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    ← Back
                </button>
                <h2 class="text-xl font-semibold text-gray-800">Feedback Detail</h2>
            </div>
        </template>

        <div class="py-8 px-4 max-w-5xl mx-auto space-y-5">

            <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium">
                {{ flash.success }}
            </div>

            <div v-if="!feedback" class="text-center text-gray-400 py-16">Feedback not found.</div>

            <template v-else>

                <!-- Header -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Tracking code</p>
                            <span class="font-mono text-2xl font-black text-gray-900 tracking-widest">{{ feedback.tracking_code }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="priorityColor(feedback.priority)">{{ feedback.priority }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="statusColor(feedback.status)">{{ feedback.status?.replace('_', ' ') }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Category</p>
                            <p class="font-semibold text-gray-800">{{ feedback.category }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">From</p>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                :class="feedback.sender_role === 'student' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700'">
                                {{ feedback.sender_role }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Submitted</p>
                            <p class="text-xs text-gray-700">{{ formatDate(feedback.submitted_at) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Resolved</p>
                            <p class="text-xs text-gray-700">{{ formatDate(feedback.resolved_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">Feedback Content</h3>
                        <span class="text-xs text-green-600 font-medium">🔓 Decrypted</span>
                    </div>
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-4 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap min-h-[80px]">
                        {{ feedback.content }}
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Sender identity is never stored.</p>
                </div>

                <!-- Responses -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Responses ({{ feedback.responses?.length ?? 0 }})</h3>
                    <div v-if="feedback.responses?.length > 0" class="space-y-3">
                        <div v-for="(r, i) in feedback.responses" :key="i" class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold capitalize text-gray-700">{{ r.responder_role }}</span>
                                <span class="text-xs text-gray-400">{{ formatDate(r.responded_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ r.content }}</p>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-sm text-gray-400 rounded-lg bg-gray-50">No responses yet.</div>
                </div>

                <!-- Actions -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Admin Actions</h3>
                    <div v-if="suggestions.length > 0" class="mb-4 rounded-lg border border-indigo-100 bg-indigo-50 p-3">
                        <p class="text-xs font-semibold text-indigo-800 mb-2">Suggested Resolutions From Similar Issues</p>
                        <div class="space-y-2">
                            <div v-for="item in suggestions" :key="item.feedback_id" class="rounded border border-indigo-100 bg-white p-2">
                                <p class="text-xs text-gray-500">Similarity: {{ (item.similarity_score * 100).toFixed(0) }}%</p>
                                <p class="text-xs text-gray-700">{{ item.resolution }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="canRespond" class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Write a response</label>
                        <textarea v-model="respondForm.response" rows="4" maxlength="3000"
                            placeholder="Write your official response..."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-gray-700 focus:ring-1 focus:ring-gray-700 resize-none"></textarea>
                        <p v-if="respondForm.errors.response" class="mt-1 text-xs text-red-500">{{ respondForm.errors.response }}</p>
                        <button @click="submitResponse" :disabled="respondForm.processing || !respondForm.response"
                            class="mt-2 rounded-lg bg-gray-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-900 disabled:opacity-50">
                            {{ respondForm.processing ? 'Sending...' : 'Send Response' }}
                        </button>
                    </div>
                    <div class="flex gap-3 pt-3" :class="canRespond ? 'border-t border-gray-100' : ''">
                        <button v-if="canResolve" @click="showResolveConfirm = true"
                            class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-100">
                            ✓ Mark Resolved
                        </button>
                        <div v-if="feedback.status === 'resolved'" class="flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 px-4 py-2">
                            <span class="text-sm text-green-700 font-semibold">✓ Resolved on {{ formatDate(feedback.resolved_at) }}</span>
                        </div>
                    </div>
                </div>

            </template>
        </div>

        <!-- Resolve Modal -->
        <div v-if="showResolveConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-base font-bold text-gray-900 mb-2 text-center">Confirm Resolution</h3>
                <p class="text-sm text-gray-500 text-center mb-3">Mark this feedback as fully resolved?</p>
                <textarea v-model="resolveForm.resolution" rows="4" maxlength="2000"
                    placeholder="Optional: write what solution worked (used for future suggestions)"
                    class="mb-5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                <div class="flex gap-3">
                    <button @click="submitResolve" class="flex-1 rounded-xl bg-green-600 py-2.5 text-sm font-bold text-white hover:bg-green-700">
                        Yes, Resolve
                    </button>
                    <button @click="showResolveConfirm = false" class="flex-1 rounded-xl border border-gray-200 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>
