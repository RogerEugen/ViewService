<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    window:        { type: Object, default: null },
    lecturers:     { type: Array,  default: () => [] },
    error:         { type: String, default: null },
    user:          { type: Object, default: () => ({}) },
    department_id: { type: [Number, String], default: null },
    faculty_id:    { type: [Number, String], default: null },
    academic_year: { type: String, default: '' },
    semester:      { type: Number, default: 1 },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

// Track submitted courses this session
const submittedCodes = ref([]);

// ✅ Watch flash for eval_success — same pattern used in TrackFeedback
watch(flash, (f) => {
    if (f?.course_code && !submittedCodes.value.includes(f.course_code)) {
        submittedCodes.value.push(f.course_code);
    }
}, { deep: true });

const showForm = ref(false);

const form = useForm({
    window_id:              props.window?.id ?? '',
    course_code:            '',
    subject_name:           '',
    lecturer_id:            '',
    lecturer_name:          '',
    teaching_quality:       0,
    course_content:         0,
    assessment_fairness:    0,
    resources_available:    0,
    lecturer_accessibility: 0,
    overall_rating:         0,
    comments:               '',
});

const startNewEvaluation = () => {
    form.reset();
    form.window_id = props.window?.id ?? '';
    showForm.value = true;
};

const cancelForm = () => {
    showForm.value = false;
    form.reset();
};

const selectLecturer = (lecturer) => {
    form.lecturer_id   = parseInt(lecturer.id);
    form.lecturer_name = lecturer.name;
};

const submitEval = () => {
    form.post(route('student.evaluations.submit'), {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset();
            form.window_id = props.window?.id ?? '';
        },
    });
};

const criteria = [
    { key: 'teaching_quality',       label: 'Teaching Quality',       desc: 'How effectively does the lecturer deliver this course?' },
    { key: 'course_content',         label: 'Course Content',         desc: 'Is the content relevant, structured and up to date?' },
    { key: 'assessment_fairness',    label: 'Assessment Fairness',    desc: 'Are exams and assignments fair and clearly communicated?' },
    { key: 'resources_available',    label: 'Resources Available',    desc: 'Are sufficient learning materials and resources provided?' },
    { key: 'lecturer_accessibility', label: 'Lecturer Accessibility', desc: 'Is the lecturer available for questions and consultation?' },
    { key: 'overall_rating',         label: 'Overall Rating',         desc: 'Your overall experience with this course this semester' },
];

const starLabel = (n) => ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'][n];

const isFormComplete = computed(() =>
    form.course_code.trim() !== '' &&
    form.subject_name.trim() !== '' &&
    form.lecturer_id !== '' &&
    criteria.every(c => form[c.key] > 0)
);

const overallAvg = computed(() => {
    const rated = criteria.filter(c => form[c.key] > 0);
    if (!rated.length) return 0;
    return (rated.reduce((s, c) => s + form[c.key], 0) / rated.length).toFixed(1);
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
}) : '';

const selectedLecturerObj = computed(() =>
    props.lecturers.find(l => l.id === form.lecturer_id || l.id == form.lecturer_id)
);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Course Evaluations" />
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Course Evaluations</h2>
        </template>

        <div class="py-8 px-4 max-w-3xl mx-auto space-y-5">

            <!-- ✅ Success flash — uses flash directly like other pages -->
            <div v-if="flash.eval_success"
                class="rounded-xl bg-green-50 border border-green-200 px-4 py-4 flex items-start gap-3">
                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-green-800">{{ flash.eval_success }}</p>
                    <p class="text-xs text-green-600 mt-0.5">You can evaluate another course using the button below.</p>
                </div>
            </div>

            <!-- Error -->
            <div v-if="flash.error" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ flash.error }}
            </div>

            <!-- Service error -->
            <div v-if="error && !window" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ error }}
            </div>

            <!-- No window -->
            <div v-if="!window && !error" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v11.25A2.25 2.25 0 009 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3"/>
                </svg>
                <p class="text-sm font-semibold text-gray-600">No evaluation window is currently open</p>
                <p class="text-xs text-gray-400 mt-1">Course evaluations are available during designated periods only.</p>
            </div>

            <template v-if="window">

                <!-- Window banner -->
                <div class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-400"></span>
                                </span>
                                <span class="text-xs font-semibold text-indigo-200 uppercase tracking-wide">Evaluation Open</span>
                            </div>
                            <h3 class="text-base font-bold">{{ window.title }}</h3>
                            <p class="text-xs text-indigo-200 mt-0.5">{{ window.academic_year }} — Semester {{ window.semester }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-indigo-200">Closes</p>
                            <p class="text-sm font-semibold">{{ formatDate(window.closes_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Anonymity notice -->
                <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 flex gap-2 items-start">
                    <svg class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-blue-700">
                        <strong>Your evaluations are anonymous.</strong>
                        Results are only revealed after at least 5 students evaluate the same course.
                    </p>
                </div>

                <!-- Submitted this session -->
                <div v-if="submittedCodes.length > 0" class="rounded-xl border border-green-200 bg-green-50 overflow-hidden">
                    <div class="border-b border-green-100 px-5 py-3">
                        <h3 class="text-sm font-semibold text-green-800">✓ Evaluated This Session ({{ submittedCodes.length }})</h3>
                    </div>
                    <div class="px-5 py-3 flex flex-wrap gap-2">
                        <span v-for="code in submittedCodes" :key="code"
                            class="rounded-full bg-green-100 border border-green-200 px-3 py-1 text-xs font-mono font-bold text-green-700">
                            ✓ {{ code }}
                        </span>
                    </div>
                </div>

                <!-- Start button -->
                <div v-if="!showForm" class="text-center">
                    <button @click="startNewEvaluation"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white hover:bg-indigo-700 transition shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        {{ submittedCodes.length === 0 ? 'Start Course Evaluation' : 'Evaluate Another Course' }}
                    </button>
                    <p class="mt-2 text-xs text-gray-400">You can evaluate as many courses as you have this semester</p>
                </div>

                <!-- Evaluation form -->
                <div v-if="showForm" class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                    <div class="border-b border-gray-100 bg-gray-50 px-5 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Course Evaluation Form</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Fill course details then rate your lecturer</p>
                        </div>
                        <button @click="cancelForm"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>

                    <div class="px-5 py-5 space-y-6">

                        <!-- Form error -->
                        <div v-if="form.errors.error" class="rounded-lg bg-red-50 border border-red-200 px-3 py-2.5 text-sm text-red-700">
                            {{ form.errors.error }}
                        </div>

                        <!-- Section 1: Course Details -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-white text-xs font-bold">1</div>
                                <h4 class="text-sm font-semibold text-gray-800">Course Details</h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Course Code <span class="text-red-500">*</span></label>
                                    <input v-model="form.course_code" type="text" placeholder="e.g. ITU 08207"
                                        maxlength="20"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"/>
                                    <p v-if="form.errors.course_code" class="mt-1 text-xs text-red-500">{{ form.errors.course_code }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Subject Name <span class="text-red-500">*</span></label>
                                    <input v-model="form.subject_name" type="text" placeholder="e.g. Database Systems"
                                        maxlength="150"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"/>
                                    <p v-if="form.errors.subject_name" class="mt-1 text-xs text-red-500">{{ form.errors.subject_name }}</p>
                                </div>
                            </div>

                            <!-- Lecturer selection -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Select Lecturer <span class="text-red-500">*</span></label>
                                <div v-if="lecturers.length === 0" class="rounded-lg bg-yellow-50 border border-yellow-200 px-3 py-2.5 text-xs text-yellow-700">
                                    No lecturers found for your department.
                                </div>
                                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <button v-for="lec in lecturers" :key="lec.id" type="button"
                                        @click="selectLecturer(lec)"
                                        class="flex items-center gap-3 rounded-xl border-2 px-4 py-3 text-left transition"
                                        :class="form.lecturer_id == lec.id
                                            ? 'border-indigo-500 bg-indigo-50'
                                            : 'border-gray-200 bg-white hover:border-indigo-200'">
                                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full"
                                            :class="form.lecturer_id == lec.id ? 'bg-indigo-600' : 'bg-gray-100'">
                                            <span class="text-sm font-bold"
                                                :class="form.lecturer_id == lec.id ? 'text-white' : 'text-gray-600'">
                                                {{ lec.name.charAt(0) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold truncate"
                                                :class="form.lecturer_id == lec.id ? 'text-indigo-800' : 'text-gray-800'">
                                                {{ lec.name }}
                                            </p>
                                            <p class="text-xs text-gray-400">{{ lec.staff_number ?? 'Lecturer' }}</p>
                                        </div>
                                        <svg v-if="form.lecturer_id == lec.id"
                                            class="h-4 w-4 text-indigo-600 ml-auto flex-shrink-0"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="selectedLecturerObj" class="mt-2 rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-2 text-xs text-indigo-700">
                                    Evaluating: <strong>{{ selectedLecturerObj.name }}</strong>
                                    for <strong>{{ form.subject_name || form.course_code || 'this course' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        <!-- Section 2: Ratings -->
                        <div class="space-y-5">
                            <div class="flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-white text-xs font-bold">2</div>
                                <h4 class="text-sm font-semibold text-gray-800">Rate Lecturer & Course</h4>
                                <span class="ml-auto text-xs text-gray-400">{{ criteria.filter(c => form[c.key] > 0).length }}/{{ criteria.length }} rated</span>
                            </div>

                            <div v-for="criterion in criteria" :key="criterion.key" class="space-y-2">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ criterion.label }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ criterion.desc }}</p>
                                    </div>
                                    <span v-if="form[criterion.key] > 0"
                                        class="ml-3 flex-shrink-0 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700">
                                        {{ starLabel(form[criterion.key]) }}
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <button v-for="star in [1,2,3,4,5]" :key="star" type="button"
                                        @click="form[criterion.key] = star"
                                        class="flex-1 rounded-xl py-3 text-sm font-bold transition border-2"
                                        :class="form[criterion.key] === star
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : form[criterion.key] > star
                                                ? 'bg-indigo-100 text-indigo-600 border-indigo-200'
                                                : 'bg-white text-gray-300 border-gray-200 hover:border-indigo-300'">
                                        {{ star }}
                                    </button>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300"
                                        :class="{
                                            'bg-green-500': form[criterion.key] >= 4,
                                            'bg-blue-500':  form[criterion.key] === 3,
                                            'bg-yellow-500':form[criterion.key] === 2,
                                            'bg-red-500':   form[criterion.key] === 1,
                                        }"
                                        :style="{ width: (form[criterion.key] / 5 * 100) + '%' }">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        <!-- Section 3: Comments -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-white text-xs font-bold">3</div>
                                <h4 class="text-sm font-semibold text-gray-800">Comments <span class="text-gray-400 font-normal text-xs">(optional)</span></h4>
                            </div>
                            <textarea v-model="form.comments" rows="3" maxlength="2000"
                                placeholder="Additional feedback about this course or lecturer..."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"></textarea>
                            <p class="text-right text-xs text-gray-400">{{ form.comments.length }}/2000</p>
                        </div>

                        <!-- Rating Summary -->
                        <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-semibold text-gray-600">Rating Summary</p>
                                <span v-if="+overallAvg > 0" class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                    :class="+overallAvg >= 4 ? 'bg-green-100 text-green-700' : +overallAvg >= 3 ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'">
                                    Avg: {{ overallAvg }}/5
                                </span>
                            </div>
                            <div class="grid grid-cols-6 gap-1.5">
                                <div v-for="c in criteria" :key="c.key" class="text-center">
                                    <div class="rounded-lg py-2" :class="form[c.key] > 0 ? 'bg-indigo-600' : 'bg-gray-100'">
                                        <p class="text-sm font-bold" :class="form[c.key] > 0 ? 'text-white' : 'text-gray-300'">
                                            {{ form[c.key] > 0 ? form[c.key] : '—' }}
                                        </p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1 leading-tight">{{ c.label.split(' ')[0] }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit button -->
                        <button @click="submitEval" :disabled="!isFormComplete || form.processing"
                            class="w-full rounded-xl py-4 text-sm font-bold transition"
                            :class="isFormComplete ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Submitting anonymously...
                            </span>
                            <span v-else-if="!form.course_code || !form.subject_name">Enter course code and subject name to continue</span>
                            <span v-else-if="!form.lecturer_id">Select a lecturer to continue</span>
                            <span v-else-if="!isFormComplete">Rate all {{ criteria.length }} criteria to submit</span>
                            <span v-else>Submit Evaluation Anonymously</span>
                        </button>
                        <p class="text-center text-xs text-gray-400">After submitting you can evaluate another course.</p>

                    </div>
                </div>

            </template>
        </div>
    </AuthenticatedLayout>
</template>