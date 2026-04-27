<script setup>
import HodLayout from '@/Layouts/HodLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    windows:       { type: Array,  default: () => [] },
    results:       { type: Array,  default: () => [] },
    activeWindow:  { type: Object, default: null },
    department_id: { type: Number, default: null },
    user:          { type: Object, default: () => ({}) },
});

const selectedWindow = ref(props.activeWindow?.id ?? null);

const ratingColor = (v) => {
    if (v >= 4.5) return 'text-green-600';
    if (v >= 3.5) return 'text-blue-600';
    if (v >= 2.5) return 'text-yellow-600';
    return 'text-red-600';
};

const ratingBg = (v) => {
    if (v >= 4.5) return 'bg-green-500';
    if (v >= 3.5) return 'bg-blue-500';
    if (v >= 2.5) return 'bg-yellow-500';
    return 'bg-red-400';
};

const barWidth = (v) => Math.round((v / 5) * 100) + '%';

const gradeLabel = (v) => {
    if (v >= 4.5) return { label: 'Excellent', color: 'bg-green-100 text-green-700' };
    if (v >= 3.5) return { label: 'Good', color: 'bg-blue-100 text-blue-700' };
    if (v >= 2.5) return { label: 'Average', color: 'bg-yellow-100 text-yellow-700' };
    return { label: 'Needs Improvement', color: 'bg-red-100 text-red-700' };
};

const deptAvg = computed(() => {
    if (!props.results.length) return 0;
    return (props.results.reduce((s, r) => s + r.avg_overall, 0) / props.results.length).toFixed(2);
});

const totalResponses = computed(() =>
    props.results.reduce((s, r) => s + r.total_responses, 0)
);
</script>

<template>
    <HodLayout>
        <Head title="Evaluation Results" />
        <template #header>
            <div class="flex items-center gap-3">
                <button @click="router.visit(route('hod.dashboard'))"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    ← Dashboard
                </button>
                <h2 class="text-xl font-semibold text-gray-800">Department Evaluation Results</h2>
            </div>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <!-- Active window banner -->
            <div v-if="activeWindow" class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                            </span>
                            <span class="text-xs text-indigo-200">Currently Active</span>
                        </div>
                        <h3 class="font-bold">{{ activeWindow.title }}</h3>
                        <p class="text-xs text-indigo-200">{{ activeWindow.academic_year }} — Semester {{ activeWindow.semester }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-black">{{ results.length }}</p>
                        <p class="text-xs text-indigo-200">Courses with results</p>
                    </div>
                </div>
            </div>

            <!-- No active window -->
            <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center">
                <p class="text-sm text-gray-500">No active evaluation window</p>
            </div>

            <!-- Department summary stats -->
            <div v-if="results.length > 0" class="grid grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-center">
                    <p class="text-2xl font-black text-indigo-600">{{ results.length }}</p>
                    <p class="text-xs text-gray-400">Courses Evaluated</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-center">
                    <p class="text-2xl font-black text-blue-600">{{ totalResponses }}</p>
                    <p class="text-xs text-gray-400">Total Responses</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-center">
                    <p class="text-2xl font-black" :class="ratingColor(+deptAvg)">{{ deptAvg }}</p>
                    <p class="text-xs text-gray-400">Dept Average /5</p>
                </div>
            </div>

            <!-- Threshold notice -->
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 flex gap-2">
                <svg class="h-4 w-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-xs text-amber-700">
                    Only courses with <strong>5+ evaluations</strong> are shown to protect student anonymity.
                </p>
            </div>

            <!-- No results yet -->
            <div v-if="results.length === 0 && activeWindow" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z"/>
                </svg>
                <p class="text-sm font-semibold text-gray-500">No evaluation results yet</p>
                <p class="text-xs text-gray-400 mt-1">Results appear when courses receive 5 or more evaluations.</p>
            </div>

            <!-- Results table + cards -->
            <div v-if="results.length > 0" class="space-y-4">

                <!-- Summary table -->
                <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                    <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700">All Courses — Department Overview</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Course</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Lecturer</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Responses</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Teaching</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Content</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Assessment</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Resources</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Access.</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Overall</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Grade</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="r in results" :key="r.course_code" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="font-mono text-xs font-bold text-gray-900">{{ r.course_code }}</p>
                                        <p v-if="r.subject_name" class="text-xs text-gray-500 truncate max-w-32">{{ r.subject_name }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ r.lecturer_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-600">{{ r.total_responses }}</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(r.avg_teaching_quality)">{{ r.avg_teaching_quality }}</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(r.avg_course_content)">{{ r.avg_course_content }}</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(r.avg_assessment_fairness)">{{ r.avg_assessment_fairness }}</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(r.avg_resources)">{{ r.avg_resources }}</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(r.avg_accessibility)">{{ r.avg_accessibility }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-base font-black" :class="ratingColor(r.avg_overall)">{{ r.avg_overall }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="gradeLabel(r.avg_overall).color">
                                            {{ gradeLabel(r.avg_overall).label }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bar chart visual for each course -->
                <div v-for="r in results" :key="'card-' + r.course_code"
                    class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                    <div class="border-b border-gray-100 px-5 py-4 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="font-mono text-sm font-bold text-gray-900">{{ r.course_code }}</span>
                                <span v-if="r.subject_name" class="text-sm text-gray-600">— {{ r.subject_name }}</span>
                                <span :class="gradeLabel(r.avg_overall).color" class="rounded-full px-2 py-0.5 text-xs font-semibold">
                                    {{ gradeLabel(r.avg_overall).label }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-400">
                                <span>{{ r.total_responses }} responses</span>
                                <span v-if="r.lecturer_name">· {{ r.lecturer_name }}</span>
                            </div>
                        </div>
                        <p class="text-3xl font-black" :class="ratingColor(r.avg_overall)">
                            {{ r.avg_overall }}<span class="text-sm font-normal text-gray-400">/5</span>
                        </p>
                    </div>
                    <div class="px-5 py-4 space-y-3">
                        <div v-for="(item, idx) in [
                            { label: 'Teaching Quality',    val: r.avg_teaching_quality },
                            { label: 'Course Content',      val: r.avg_course_content },
                            { label: 'Assessment Fairness', val: r.avg_assessment_fairness },
                            { label: 'Resources',           val: r.avg_resources },
                            { label: 'Accessibility',       val: r.avg_accessibility },
                        ]" :key="idx" class="flex items-center gap-3">
                            <span class="w-36 flex-shrink-0 text-xs text-gray-500">{{ item.label }}</span>
                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="ratingBg(item.val)"
                                    :style="{ width: barWidth(item.val) }"></div>
                            </div>
                            <span class="w-8 text-right text-xs font-bold" :class="ratingColor(item.val)">{{ item.val }}</span>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 px-5 py-2.5 bg-gray-50 text-xs text-gray-400">
                        Dept avg: <strong :class="ratingColor(r.dept_avg_overall)">{{ r.dept_avg_overall }}</strong>
                        <span v-if="r.avg_overall >= r.dept_avg_overall" class="ml-2 text-green-600 font-semibold">
                            ↑ Above average
                        </span>
                        <span v-else class="ml-2 text-orange-600 font-semibold">↓ Below average</span>
                    </div>
                </div>
            </div>

        </div>
    </HodLayout>
</template>