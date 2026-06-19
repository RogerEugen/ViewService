<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onUnmounted } from 'vue';

const props = defineProps({
    categories:    { type: Array,  default: () => [] },
    user:          { type: Object, default: () => ({}) },
    department_id: { type: Number, default: null },
});

// ── State ────────────────────────────────────────────────────
const showPopup    = ref(false);
const trackingCode = ref('');
const copied       = ref(false);
const countdown    = ref(10);
let   timer        = null;

const form = useForm({
    category_id: '',
    content:     '',
    priority:    'medium',
});

// ── Submit ───────────────────────────────────────────────────
const submit = () => {
    form.post(route('student.feedback.submit'), {
        preserveScroll: true,
        onSuccess: (page) => {
            // ✅ Read flash directly from page object Inertia passes here
            const code = page.props.flash?.tracking_code ?? '';

            if (code) {
                trackingCode.value = code;
                showPopup.value    = true;
                countdown.value    = 10;
                copied.value       = false;
                startCountdown();
            }

            form.reset();
        },
        onError: () => {
            // errors handled by form.errors below
        },
    });
};

// ── Countdown ────────────────────────────────────────────────
const startCountdown = () => {
    if (timer) clearInterval(timer);
    timer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(timer);
            showPopup.value = false;
        }
    }, 1000);
};

const dismissPopup = () => {
    if (timer) clearInterval(timer);
    showPopup.value = false;
};

const keepOpen = () => {
    // User clicked "keep" — stop countdown but keep popup visible
    if (timer) clearInterval(timer);
    countdown.value = 0;
};

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

// ── Copy ─────────────────────────────────────────────────────
const copyCode = () => {
    navigator.clipboard.writeText(trackingCode.value);
    copied.value = true;
    setTimeout(() => copied.value = false, 2500);
};

// ── Priorities ───────────────────────────────────────────────
const priorities = [
    { value: 'low',    label: 'Low',    bg: 'bg-gray-100',   text: 'text-gray-700',   ring: 'ring-gray-400'   },
    { value: 'medium', label: 'Medium', bg: 'bg-blue-100',   text: 'text-blue-700',   ring: 'ring-blue-400'   },
    { value: 'high',   label: 'High',   bg: 'bg-orange-100', text: 'text-orange-700', ring: 'ring-orange-400' },
    { value: 'urgent', label: 'Urgent', bg: 'bg-red-100',    text: 'text-red-700',    ring: 'ring-red-400'    },
];

const selectedCategory = computed(() =>
    props.categories.find(c => c.id == form.category_id)
);

const selectedCategoryGuide = computed(() => {
    const name = String(selectedCategory.value?.name ?? '').toLowerCase();
    const desc = String(selectedCategory.value?.description ?? '').toLowerCase();
    const text = `${name} ${desc}`;

    if (text.includes('exam')) {
        return 'Example: delayed examination results, unclear marks, or an examination timetable problem.';
    }
    if (text.includes('academic') || text.includes('course')) {
        return 'Example: unclear teaching, insufficient course content, or a curriculum alignment concern.';
    }
    if (text.includes('harassment') || text.includes('misconduct')) {
        return 'Example: inappropriate language, threats, harassment, or other misconduct affecting a student.';
    }
    if (text.includes('infrastructure') || text.includes('facility')) {
        return 'Example: an unsuitable classroom, damaged furniture or equipment, or unavailable essential services.';
    }

    return 'Example: briefly describe the issue, when and where it happened, and its impact on you or the class.';
});

// SVG circle countdown
const radius      = 22;
const circumference = 2 * Math.PI * radius;
const dashOffset  = computed(() =>
    circumference - (countdown.value / 10) * circumference
);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Submit Feedback" />

        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Submit Feedback</h2>
        </template>

        <!-- ── TRACKING CODE POPUP OVERLAY ───────────────────────── -->
        <teleport to="body">
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showPopup"
                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm px-4"
                    @click.self="keepOpen"
                >
                    <transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 scale-90 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                    >
                        <div v-if="showPopup" class="w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden">

                            <!-- ── Top green bar ── -->
                            <div class="bg-gradient-to-br from-green-500 to-emerald-600 px-6 py-6 relative">

                                <!-- Circular countdown top-right -->
                                <div class="absolute top-4 right-4">
                                    <svg :width="radius * 2 + 8" :height="radius * 2 + 8" class="-rotate-90">
                                        <!-- Background ring -->
                                        <circle
                                            :cx="radius + 4" :cy="radius + 4" :r="radius"
                                            fill="none"
                                            stroke="rgba(255,255,255,0.25)"
                                            stroke-width="3"
                                        />
                                        <!-- Progress ring -->
                                        <circle
                                            :cx="radius + 4" :cy="radius + 4" :r="radius"
                                            fill="none"
                                            stroke="white"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                            :stroke-dasharray="circumference"
                                            :stroke-dashoffset="dashOffset"
                                            class="transition-all duration-1000 ease-linear"
                                        />
                                    </svg>
                                    <!-- Number in center -->
                                    <span class="absolute inset-0 flex items-center justify-center text-white text-sm font-bold">
                                        {{ countdown > 0 ? countdown : '✓' }}
                                    </span>
                                </div>

                                <!-- Success icon + title -->
                                <div class="text-center">
                                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
                                        <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-bold text-white">Feedback Submitted!</h3>
                                    <p class="mt-1 text-sm text-green-100">Sent completely anonymously</p>
                                </div>
                            </div>

                            <!-- ── Warning banner ── -->
                            <div class="bg-amber-50 border-b border-amber-100 px-5 py-3 flex gap-2.5 items-start">
                                <svg class="h-4 w-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <div>
                                    <p class="text-xs font-bold text-amber-800">⚠ Save your tracking code now!</p>
                                    <p class="text-xs text-amber-700 mt-0.5">
                                        The system will <strong>NOT store</strong> this code to protect your anonymity.
                                        This popup disappears in
                                        <strong>{{ countdown > 0 ? countdown + ' seconds' : 'a moment' }}</strong>.
                                    </p>
                                </div>
                            </div>

                            <!-- ── Tracking code ── -->
                            <div class="px-6 py-5 space-y-4">

                                <!-- Code display box -->
                                <div class="rounded-xl border-2 border-dashed border-indigo-200 bg-indigo-50 px-5 py-4">
                                    <p class="text-xs text-indigo-400 text-center mb-1 font-medium">Your Tracking Code</p>
                                    <p class="text-center font-mono text-3xl font-black text-indigo-900 tracking-widest">
                                        {{ trackingCode }}
                                    </p>
                                </div>

                                <!-- Copy button -->
                                <button
                                    @click="copyCode"
                                    class="w-full flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold transition"
                                    :class="copied
                                        ? 'bg-green-100 text-green-700 border border-green-200'
                                        : 'bg-indigo-600 text-white hover:bg-indigo-700'"
                                >
                                    <svg v-if="!copied" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ copied ? '✓ Code Copied to Clipboard!' : 'Copy Tracking Code' }}
                                </button>

                                <!-- Progress bar -->
                                <div v-if="countdown > 0">
                                    <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                        <div
                                            class="h-full bg-green-400 rounded-full transition-all duration-1000 ease-linear"
                                            :style="{ width: (countdown / 10 * 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p class="mt-1 text-center text-xs text-gray-400">
                                        Popup closes in {{ countdown }}s — copy your code now
                                    </p>
                                </div>

                                <!-- Action buttons -->
                                <div class="flex gap-2 pt-1">
                                    
                                    <a    :href="route('student.track') + '?code=' + trackingCode"
                                        class="flex-1 text-center rounded-xl border border-indigo-200 bg-white px-3 py-2.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-50"
                                    >
                                        Track Feedback
                                    </a>
                                    <button
                                        @click="dismissPopup"
                                        class="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                                    >
                                        Close
                                    </button>
                                </div>

                            </div>
                        </div>
                    </transition>
                </div>
            </transition>
        </teleport>

        <!-- ── FEEDBACK FORM ─────────────────────────────────────── -->
        <div class="py-8 px-4 max-w-7xl mx-auto">

            <!-- Inline success banner (shown after popup closes) -->
            <transition
                enter-active-class="transition duration-500 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div
                    v-if="!showPopup && trackingCode"
                    class="mb-5 rounded-xl bg-green-50 border border-green-200 px-4 py-3 flex items-start gap-3"
                >
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-green-800">Feedback submitted successfully!</p>
                        <p class="text-xs text-green-600 mt-0.5">
                            Your tracking code:
                            <span class="font-mono font-bold tracking-wider ml-1">{{ trackingCode }}</span>
                        </p>
                        <div class="mt-2 flex gap-2">
                            
                            <a    :href="route('student.track') + '?code=' + trackingCode"
                                class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700"
                            >Track feedback</a>
                            <button
                                @click="copyCode"
                                class="rounded-lg border border-green-300 px-3 py-1.5 text-xs font-semibold text-green-700 hover:bg-green-100"
                            >{{ copied ? 'Copied!' : 'Copy code' }}</button>
                        </div>
                    </div>
                    <button @click="trackingCode = ''" class="text-green-400 hover:text-green-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </transition>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form card -->
            <div class="lg:col-span-2 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">

                <div class="border-b border-gray-100 px-6 py-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Anonymous Feedback</h3>
                        <p class="text-xs text-gray-400">Valid feedback remains anonymous</p>
                    </div>
                </div>

                <div class="px-6 py-5">

                    <div class="mb-5 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5 flex gap-2 items-start">
                        <svg class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-blue-700">
                            Valid feedback is anonymous and stores no personal identity. Repeated abusive-language attempts are blocked and may create a restricted Dean conduct review.
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
                                    v-for="p in priorities" :key="p.value" type="button"
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
                                placeholder="Describe your feedback clearly and in detail..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                                required
                            ></textarea>
                            <div class="mt-1 flex justify-between items-center">
                                <p v-if="form.errors.content" class="text-xs text-red-500">{{ form.errors.content }}</p>
                                <span class="ml-auto text-xs" :class="form.content.length > 4500 ? 'text-orange-500' : 'text-gray-400'">
                                    {{ form.content.length }}/5000
                                </span>
                            </div>
                        </div>

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
            <aside class="space-y-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <h4 class="text-sm font-bold text-emerald-800 mb-1">100% Anonymous Guarantee</h4>
                    <p class="text-xs text-emerald-700 leading-relaxed">
                        Feedback yako ni anonymous kwa asilimia 100. Mfumo huu hauhifadhi jina lako, email yako,
                        registration number yako, wala taarifa yoyote ya kukutambulisha.
                    </p>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <h4 class="text-sm font-bold text-amber-800 mb-2">Caution Kabla Ya Kutuma</h4>
                    <ul class="text-xs text-amber-700 space-y-1.5">
                        <li>• Usitie jina lako au taarifa binafsi ndani ya maelezo.</li>
                        <li>• Andika ukweli wa tukio, epuka matusi au lugha ya kuumiza.</li>
                        <li>• State the specific issue, location, and impact so it can be addressed quickly.</li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                    <h4 class="text-sm font-bold text-indigo-800 mb-2">Category Guide</h4>
                    <p class="text-xs text-indigo-700 mb-2">
                        <span class="font-semibold">Category selected:</span>
                        <span class="ml-1">{{ selectedCategory?.name || 'None yet' }}</span>
                    </p>
                    <p class="text-xs text-indigo-700 leading-relaxed">
                        {{ selectedCategoryGuide }}
                    </p>
                    <p v-if="selectedCategory?.description" class="mt-2 text-xs text-indigo-600">
                        {{ selectedCategory.description }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <h4 class="mb-2 text-sm font-bold text-gray-800">Example of a Helpful Report</h4>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        "Category: Examination Concerns. The midterm scheduled for May 15, 2026 started two hours late,
                        and the instructions were unclear. This caused confusion for many students."
                    </p>
                </div>
            </aside>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
