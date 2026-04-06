<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    categories:    { type: Array,  default: () => [] },
    user:          { type: Object, default: () => ({}) },
    department_id: { type: Number, default: null },
});

const page         = usePage();
const submitted    = ref(false);
const trackingCode = ref('');
const copied       = ref(false);

const form = useForm({
    category_id: '',
    content:     '',
    priority:    'medium',
});

// Watch for flash success from server
watch(() => page.props.flash, (flash) => {
    if (flash?.tracking_code) {
        submitted.value    = true;
        trackingCode.value = flash.tracking_code;
    }
}, { deep: true });

const submit = () => {
    form.post(route('student.feedback.submit'), {
        onSuccess: () => {
            form.reset();
        },
    });
};

const copyCode = () => {
    navigator.clipboard.writeText(trackingCode.value);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

const submitAnother = () => {
    submitted.value    = false;
    trackingCode.value = '';
};

const priorities = [
    { value: 'low',    label: 'Low',    bg: 'bg-gray-100',   text: 'text-gray-700',   ring: 'ring-gray-400'   },
    { value: 'medium', label: 'Medium', bg: 'bg-blue-100',   text: 'text-blue-700',   ring: 'ring-blue-400'   },
    { value: 'high',   label: 'High',   bg: 'bg-orange-100', text: 'text-orange-700', ring: 'ring-orange-400' },
    { value: 'urgent', label: 'Urgent', bg: 'bg-red-100',    text: 'text-red-700',    ring: 'ring-red-400'    },
];

const selectedCategory = computed(() =>
    props.categories.find(c => c.id == form.category_id)
);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Submit Feedback" />

        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Submit Feedback</h2>
        </template>

        <div class="py-8 px-4 max-w-2xl mx-auto">

            <!-- ── SUCCESS STATE ────────────────────────────── -->
            <div v-if="submitted && trackingCode" class="space-y-4">

                <!-- Big success card -->
                <div class="rounded-2xl bg-white border border-green-200 shadow-sm overflow-hidden">
                    <!-- Green header -->
                    <div class="bg-green-500 px-6 py-8 text-center">
                        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
                            <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">Feedback Submitted!</h3>
                        <p class="mt-1 text-sm text-green-100">Your feedback has been sent anonymously</p>
                    </div>

                    <!-- Tracking code section -->
                    <div class="px-6 py-6 text-center">
                        <p class="text-sm text-gray-500 mb-3">
                            Save this tracking code to check your feedback status later.
                            <strong class="text-gray-700">Do not share it with anyone.</strong>
                        </p>

                        <!-- Tracking code display -->
                        <div class="inline-flex items-center gap-3 rounded-xl bg-gray-50 border-2 border-dashed border-gray-300 px-6 py-4 mb-5">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Your tracking code</p>
                                <span class="font-mono text-2xl font-bold text-gray-900 tracking-widest">
                                    {{ trackingCode }}
                                </span>
                            </div>
                            <button
                                @click="copyCode"
                                class="flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-medium transition"
                                :class="copied ? 'bg-green-100 text-green-700' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'"
                            >
                                <svg v-if="!copied" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ copied ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>

                        <!-- Info -->
                        <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-left mb-5">
                            <p class="text-xs text-blue-700 font-medium mb-1">What happens next?</p>
                            <ul class="text-xs text-blue-600 space-y-0.5">
                                <li>• Your feedback has been routed to the appropriate authority</li>
                                <li>• You will receive a response anonymously</li>
                                <li>• Use your tracking code to check status at any time</li>
                            </ul>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex gap-3">
                            
                              <a  :href="route('student.feedback.track') + '?code=' + trackingCode"
                                class="flex-1 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 text-center transition"
                            >
                                Track this feedback
                            </a>
                            <button
                                @click="submitAnother"
                                class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                            >
                                Submit another
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── FEEDBACK FORM ─────────────────────────────── -->
            <div v-else class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">

                <!-- Form header -->
                <div class="border-b border-gray-100 px-6 py-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Anonymous Feedback</h3>
                        <p class="text-xs text-gray-400">Your identity will never be revealed</p>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <!-- Anonymity notice -->
                    <div class="mb-5 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5 flex gap-2 items-start">
                        <svg class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-blue-700">
                            Your identity is completely anonymous. No personal information is stored with this feedback.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Feedback Category <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.category_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white"
                                required
                            >
                                <option value="">Select a category...</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-500">{{ form.errors.category_id }}</p>

                            <!-- Routing hint -->
                            <div v-if="selectedCategory" class="mt-2 flex items-center gap-2 text-xs">
                                <span class="text-gray-400">Will be sent to:</span>
                                <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-indigo-700 font-semibold capitalize">
                                    {{ selectedCategory.routes_to }}
                                </span>
                                <span v-if="selectedCategory.description" class="text-gray-400">— {{ selectedCategory.description }}</span>
                            </div>
                        </div>

                        <!-- Priority -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level</label>
                            <div class="flex gap-2 flex-wrap">
                                <button
                                    v-for="p in priorities"
                                    :key="p.value"
                                    type="button"
                                    @click="form.priority = p.value"
                                    class="rounded-full px-4 py-1.5 text-xs font-semibold border transition"
                                    :class="form.priority === p.value
                                        ? `${p.bg} ${p.text} border-transparent ring-2 ring-offset-1 ${p.ring}`
                                        : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'"
                                >{{ p.label }}</button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Your Feedback <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="form.content"
                                rows="7"
                                maxlength="5000"
                                placeholder="Describe your feedback clearly. Be specific about the issue, when it happened, and what you would like to see improved..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                                required
                            ></textarea>
                            <div class="mt-1 flex justify-between items-center">
                                <p v-if="form.errors.content" class="text-xs text-red-500">{{ form.errors.content }}</p>
                                <span
                                    class="ml-auto text-xs"
                                    :class="form.content.length > 4500 ? 'text-orange-500' : 'text-gray-400'"
                                >{{ form.content.length }}/5000</span>
                            </div>
                        </div>

                        <!-- Submit button -->
                        <button
                            type="submit"
                            :disabled="form.processing || !form.category_id || form.content.length < 10"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-3.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Submitting anonymously...
                            </span>
                            <span v-else>Submit Feedback Anonymously</span>
                        </button>

                        <p class="text-center text-xs text-gray-400">
                            You will receive a unique tracking code after submission
                        </p>

                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>