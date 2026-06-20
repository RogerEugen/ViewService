<script setup>
import LectureLayout from '@/Layouts/LectureLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import MetricCard from '@/Components/MetricCard.vue';
import { StarIcon, ChatBubbleLeftRightIcon, BookOpenIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    windows:       { type: Array,  default: () => [] },
    results:       { type: Array,  default: () => [] },
    department_id: { type: Number, default: null },
    lecturer_id:   { type: Number, default: null },   // ✅ NEW
    selectedWindow:{ type: Object, default: null },
    user:          { type: Object, default: () => ({}) },
});

const ratingColor = (avg) => {
    if (avg >= 4.5) return 'text-green-600';
    if (avg >= 3.5) return 'text-blue-600';
    if (avg >= 2.5) return 'text-yellow-600';
    return 'text-red-600';
};

const ratingBg = (avg) => {
    if (avg >= 4.5) return 'bg-green-500';
    if (avg >= 3.5) return 'bg-blue-500';
    if (avg >= 2.5) return 'bg-yellow-500';
    return 'bg-red-400';
};

const barWidth = (avg) => Math.round((avg / 5) * 100) + '%';

const activeWindow = computed(() => props.selectedWindow ?? props.windows.find(w => w.is_open) ?? props.windows[0]);
const selectedWindowId = ref(activeWindow.value?.id ?? null);
const changeWindow = () => router.get(route('lecture.evaluations'), {
    window_id: selectedWindowId.value,
}, { preserveScroll: true });

// Grade label
const gradeLabel = (avg) => {
    if (avg >= 4.5) return { label: 'Excellent', color: 'bg-green-100 text-green-800' };
    if (avg >= 3.5) return { label: 'Good', color: 'bg-blue-100 text-blue-800' };
    if (avg >= 2.5) return { label: 'Average', color: 'bg-yellow-100 text-yellow-800' };
    if (avg >= 1.5) return { label: 'Below Average', color: 'bg-orange-100 text-orange-800' };
    return { label: 'Poor', color: 'bg-red-100 text-red-800' };
};
</script>

<template>
    <LectureLayout>
        <Head title="Evaluation Results" />
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">My Course Evaluation Results</h2>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">
            <div class="flex justify-end">
                <select v-model="selectedWindowId" @change="changeWindow"
                    class="rounded-xl border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option v-for="windowItem in windows" :key="windowItem.id" :value="windowItem.id">
                        {{ windowItem.title }} · Semester {{ windowItem.semester }}
                    </option>
                </select>
            </div>

            <!-- Active window -->
            <div v-if="activeWindow" class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <div class="flex items-center gap-2 mb-1">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-400"></span>
                    </span>
                    <p class="text-xs font-semibold text-emerald-600">Selected evaluation period</p>
                </div>
                <h3 class="font-bold">{{ activeWindow.title }}</h3>
                <p class="text-xs text-slate-500">{{ activeWindow.academic_year }} — Semester {{ activeWindow.semester }}</p>
            </div>

            <!-- Personal info banner -->
            <div class="rounded-xl bg-white border border-gray-200 px-5 py-4 flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100">
                    <span class="text-lg font-bold text-indigo-600">
                        {{ user?.name?.charAt(0)?.toUpperCase() ?? 'L' }}
                    </span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ user?.name }}</p>
                    <p class="text-xs text-gray-400">
                        Showing evaluation results for your courses only
                    </p>
                </div>
                <div class="ml-auto text-right">
                    <p class="text-xl font-black text-indigo-600">{{ results.length }}</p>
                    <p class="text-xs text-gray-400">Course{{ results.length !== 1 ? 's' : '' }} evaluated</p>
                </div>
            </div>

            <!-- Threshold info -->
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 flex gap-2">
                <svg class="h-4 w-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-xs text-amber-700">
                    Results are only visible when a course has received at least
                    <strong>5 evaluations</strong> to protect student anonymity.
                    These results are <strong>private to you only</strong>.
                </p>
            </div>

            <!-- No results -->
            <div v-if="results.length === 0" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z"/>
                </svg>
                <p class="text-sm font-semibold text-gray-500">No results available yet</p>
                <p class="text-xs text-gray-400 mt-1">
                    Results will appear here once your courses receive at least 5 student evaluations.
                </p>
            </div>

            <!-- Results cards -->
            <div v-else class="space-y-5">

                <!-- Summary row -->
                <div v-if="results.length > 1" class="grid grid-cols-3 gap-4">
                    <MetricCard label="Overall average /5" :value="(results.reduce((s, r) => s + r.avg_overall, 0) / results.length).toFixed(1)" :icon="StarIcon" tone="amber" />
                    <MetricCard label="Total responses" :value="results.reduce((s, r) => s + r.total_responses, 0)" :icon="ChatBubbleLeftRightIcon" tone="blue" />
                    <MetricCard label="Courses" :value="results.length" :icon="BookOpenIcon" tone="indigo" />
                </div>

                <!-- Individual course cards -->
                <div v-for="r in results" :key="r.course_code"
                    class="rounded-xl border border-gray-200 bg-white overflow-hidden">

                    <!-- Card header -->
                    <div class="border-b border-gray-100 px-5 py-4 flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-mono text-sm font-bold text-gray-900">{{ r.course_code }}</span>
                                <span v-if="r.subject_name" class="text-sm text-gray-600">— {{ r.subject_name }}</span>
                                <span :class="gradeLabel(r.avg_overall).color"
                                    class="rounded-full px-2 py-0.5 text-xs font-semibold">
                                    {{ gradeLabel(r.avg_overall).label }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">
                                    {{ r.total_responses }} student responses
                                </span>
                                <span class="text-xs text-gray-400">{{ r.window }}</span>
                            </div>
                        </div>
                        <div class="text-right ml-4 flex-shrink-0">
                            <p class="text-3xl font-black leading-none" :class="ratingColor(r.avg_overall)">
                                {{ r.avg_overall }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">out of 5</p>
                        </div>
                    </div>

                    <!-- Rating breakdown bars -->
                    <div class="px-5 py-4 space-y-3">
                        <div v-for="(item, idx) in [
                            { label: 'Teaching Quality',    val: r.avg_teaching_quality },
                            { label: 'Course Content',      val: r.avg_course_content },
                            { label: 'Assessment Fairness', val: r.avg_assessment_fairness },
                            { label: 'Resources',           val: r.avg_resources },
                            { label: 'Accessibility',       val: r.avg_accessibility },
                        ]" :key="idx" class="flex items-center gap-3">
                            <p class="text-xs text-gray-500 w-40 flex-shrink-0">{{ item.label }}</p>
                            <div class="flex-1 h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700"
                                    :class="ratingBg(item.val)"
                                    :style="{ width: barWidth(item.val) }">
                                </div>
                            </div>
                            <span class="text-xs font-bold w-8 text-right" :class="ratingColor(item.val)">
                                {{ item.val }}
                            </span>
                        </div>
                    </div>

                    <!-- Comparison footer -->
                    <div class="border-t border-gray-100 px-5 py-3 bg-gray-50 flex items-center gap-6 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-400">Dept Average:</span>
                            <span class="font-bold" :class="ratingColor(r.dept_avg_overall)">
                                {{ r.dept_avg_overall }}
                            </span>
                        </div>
                        <div>
                            <span v-if="r.avg_overall > r.dept_avg_overall"
                                class="flex items-center gap-1 text-green-600 font-semibold">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/>
                                </svg>
                                Above dept average (+{{ (r.avg_overall - r.dept_avg_overall).toFixed(2) }})
                            </span>
                            <span v-else-if="r.avg_overall < r.dept_avg_overall"
                                class="flex items-center gap-1 text-orange-600 font-semibold">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3"/>
                                </svg>
                                Below dept average ({{ (r.avg_overall - r.dept_avg_overall).toFixed(2) }})
                            </span>
                            <span v-else class="text-gray-500 font-semibold">
                                Equal to dept average
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </LectureLayout>
</template>
