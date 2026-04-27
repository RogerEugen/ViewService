<script setup>
import DeanLayout from '@/Layouts/DeanLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    windows:      { type: Array,  default: () => [] },
    results:      { type: Array,  default: () => [] },
    activeWindow: { type: Object, default: null },
    faculty_id:   { type: Number, default: null },
    user:         { type: Object, default: () => ({}) },
});

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

// Group results by department
const byDepartment = computed(() => {
    const groups = {};
    props.results.forEach(r => {
        const key = r.department_id ?? 'unknown';
        if (!groups[key]) groups[key] = [];
        groups[key].push(r);
    });
    return groups;
});

const facultyAvg = computed(() => {
    if (!props.results.length) return 0;
    return (props.results.reduce((s, r) => s + r.avg_overall, 0) / props.results.length).toFixed(2);
});

const totalResponses = computed(() =>
    props.results.reduce((s, r) => s + r.total_responses, 0)
);
</script>

<template>
    <DeanLayout>
        <Head title="Faculty Evaluations" />
        <template #header>
            <div class="flex items-center gap-3">
                <button @click="router.visit(route('dean.dashboard'))"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    ← Dashboard
                </button>
                <h2 class="text-xl font-semibold text-gray-800">Faculty Evaluation Results</h2>
            </div>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <!-- Window banner -->
            <div v-if="activeWindow" class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-5 py-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                            </span>
                            <span class="text-xs text-purple-200">Currently Active</span>
                        </div>
                        <h3 class="font-bold">{{ activeWindow.title }}</h3>
                        <p class="text-xs text-purple-200">{{ activeWindow.academic_year }} — Semester {{ activeWindow.semester }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-black">{{ results.length }}</p>
                        <p class="text-xs text-purple-200">Courses with results</p>
                    </div>
                </div>
            </div>

            <!-- Faculty summary -->
            <div v-if="results.length > 0" class="grid grid-cols-4 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-center">
                    <p class="text-2xl font-black text-purple-600">{{ Object.keys(byDepartment).length }}</p>
                    <p class="text-xs text-gray-400">Departments</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-center">
                    <p class="text-2xl font-black text-indigo-600">{{ results.length }}</p>
                    <p class="text-xs text-gray-400">Courses</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-center">
                    <p class="text-2xl font-black text-blue-600">{{ totalResponses }}</p>
                    <p class="text-xs text-gray-400">Responses</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-center">
                    <p class="text-2xl font-black" :class="ratingColor(+facultyAvg)">{{ facultyAvg }}</p>
                    <p class="text-xs text-gray-400">Faculty Average</p>
                </div>
            </div>

            <!-- Threshold info -->
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 flex gap-2">
                <svg class="h-4 w-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-xs text-amber-700">
                    Only courses with <strong>5+ evaluations</strong> are shown. Student identities are fully protected.
                </p>
            </div>

            <!-- No results -->
            <div v-if="results.length === 0" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <p class="text-sm font-semibold text-gray-500">No evaluation results available yet</p>
                <p class="text-xs text-gray-400 mt-1">Results appear after courses receive 5 or more student evaluations.</p>
            </div>

            <!-- Faculty-wide table -->
            <div v-if="results.length > 0" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700">All Courses — Faculty Overview</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Course</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Responses</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Overall</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Dept Avg</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Faculty Avg</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Grade</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">vs Faculty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="r in results" :key="r.course_code" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <p class="font-mono text-xs font-bold text-gray-900">{{ r.course_code }}</p>
                                    <p class="text-xs text-gray-400">Dept {{ r.department_id }}</p>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-gray-600">{{ r.total_responses }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-base font-black" :class="ratingColor(r.avg_overall)">{{ r.avg_overall }}</span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(r.dept_avg_overall)">{{ r.dept_avg_overall }}</td>
                                <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(r.faculty_avg_overall)">{{ r.faculty_avg_overall }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="gradeLabel(r.avg_overall).color">
                                        {{ gradeLabel(r.avg_overall).label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-semibold">
                                    <span v-if="r.avg_overall >= r.faculty_avg_overall" class="text-green-600">
                                        ↑ +{{ (r.avg_overall - r.faculty_avg_overall).toFixed(2) }}
                                    </span>
                                    <span v-else class="text-orange-600">
                                        ↓ {{ (r.avg_overall - r.faculty_avg_overall).toFixed(2) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </DeanLayout>
</template>