<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import MetricCard from '@/Components/MetricCard.vue';
import { UserGroupIcon, BookOpenIcon, BuildingOffice2Icon, CheckCircleIcon, ClockIcon, StarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    windows:       { type: Array,  default: () => [] },
    activeWindow:  { type: Object, default: null },
    overview:      { type: Object, default: () => ({}) },
    byFaculty:     { type: Array,  default: () => [] },
    trends:        { type: Array,  default: () => [] },
    feedbackStats: { type: Object, default: () => ({}) },
    faculties:     { type: Array,  default: () => [] },
    departments:   { type: Array,  default: () => [] },
    user:          { type: Object, default: () => ({}) },
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

const barWidth = (v, max = 5) => Math.round((v / max) * 100) + '%';
const maxTrend = computed(() => Math.max(...props.trends.map(t => t.count), 1));

const getFacultyName = (id) => props.faculties.find(f => f.id == id)?.name ?? `Faculty #${id}`;
const systemAvg = computed(() => props.overview?.system_averages ?? {});
</script>

<template>
    <AdminLayout>
        <Head title="Analytics" />
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">System Analytics</h2>
                <p class="text-xs text-gray-400 mt-0.5">Evaluation and feedback analytics overview</p>
            </div>
        </template>

        <div class="py-8 px-4 max-w-7xl mx-auto space-y-6">

            <div v-if="!activeWindow" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-10 text-center">
                <p class="text-sm font-semibold text-gray-500">No active evaluation window</p>
                <button @click="router.visit(route('admin.evaluation-windows'))"
                    class="mt-3 text-sm text-indigo-600 font-medium hover:underline">
                    Create Evaluation Window →
                </button>
            </div>

            <template v-if="activeWindow">

                <!-- Window banner -->
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="mb-1 text-xs font-semibold text-emerald-600">Selected evaluation period</p>
                            <h3 class="font-bold">{{ activeWindow.title }}</h3>
                            <p class="text-xs text-slate-500">{{ activeWindow.academic_year }} — Semester {{ activeWindow.semester }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-black text-slate-950">{{ overview.total_evaluations ?? 0 }}</p>
                            <p class="text-xs text-slate-500">Total Submissions</p>
                        </div>
                    </div>
                </div>

                <!-- Metrics -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <MetricCard label="Students" :value="overview.unique_students" :icon="UserGroupIcon" tone="indigo" />
                    <MetricCard label="Courses" :value="overview.unique_courses" :icon="BookOpenIcon" tone="blue" />
                    <MetricCard label="Departments" :value="overview.unique_departments" :icon="BuildingOffice2Icon" tone="violet" />
                    <MetricCard label="With results" :value="overview.courses_with_results" :icon="CheckCircleIcon" tone="emerald" />
                    <MetricCard label="Pending" :value="overview.courses_pending" :icon="ClockIcon" tone="amber" />
                    <MetricCard label="Average /5" :value="systemAvg.overall ?? '—'" :icon="StarIcon" tone="orange" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- System averages -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">System-Wide Averages</h3>
                        <div class="space-y-3">
                            <div v-for="(item, idx) in [
                                { label: 'Teaching Quality',    val: systemAvg.teaching_quality },
                                { label: 'Course Content',      val: systemAvg.course_content },
                                { label: 'Assessment Fairness', val: systemAvg.assessment_fairness },
                                { label: 'Resources',           val: systemAvg.resources },
                                { label: 'Accessibility',       val: systemAvg.accessibility },
                                { label: 'Overall Rating',      val: systemAvg.overall },
                            ]" :key="idx" class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">{{ item.label }}</span>
                                    <span class="font-bold" :class="ratingColor(item.val ?? 0)">{{ item.val ?? '—' }}</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700"
                                        :class="ratingBg(item.val ?? 0)"
                                        :style="{ width: barWidth(item.val ?? 0) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feedback overview -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">Feedback Statistics</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="(item, i) in [
                                { label: 'Total',      val: feedbackStats.total,        color: 'text-gray-900' },
                                { label: 'New',        val: feedbackStats.submitted,    color: 'text-blue-600' },
                                { label: 'In Review',  val: feedbackStats.under_review, color: 'text-yellow-600' },
                                { label: 'Escalated',  val: feedbackStats.escalated,    color: 'text-orange-600' },
                                { label: 'Resolved',   val: feedbackStats.resolved,     color: 'text-green-600' },
                                { label: 'Urgent',     val: feedbackStats.urgent,       color: 'text-red-600' },
                            ]" :key="i" class="rounded-lg bg-gray-50 px-3 py-3 text-center">
                                <p class="text-xl font-black" :class="item.color">{{ item.val ?? 0 }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ item.label }}</p>
                            </div>
                        </div>

                        <!-- Resolution bar -->
                        <div class="mt-4 space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Resolution Rate</span>
                                <span class="font-bold text-green-600">
                                    {{ feedbackStats.total > 0 ? Math.round((feedbackStats.resolved / feedbackStats.total) * 100) : 0 }}%
                                </span>
                            </div>
                            <div class="h-3 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full bg-green-500 transition-all duration-700"
                                    :style="{ width: (feedbackStats.total > 0 ? Math.round((feedbackStats.resolved / feedbackStats.total) * 100) : 0) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trend chart -->
                <div v-if="trends.length > 0" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Daily Submission Trend</h3>
                    <div class="flex items-end gap-1 h-24">
                        <div v-for="t in trends" :key="t.date"
                            class="flex-1 flex flex-col items-center group relative">
                            <div class="w-full rounded-t bg-indigo-400 hover:bg-indigo-600 transition"
                                :style="{ height: Math.max(4, Math.round((t.count / maxTrend) * 88)) + 'px' }">
                            </div>
                            <div class="absolute bottom-full mb-1 hidden group-hover:block bg-gray-900 text-white text-xs rounded px-1.5 py-0.5 whitespace-nowrap z-10">
                                {{ t.count }} — {{ t.date }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-400">
                        <span>{{ trends[0]?.date }}</span>
                        <span>{{ trends[trends.length - 1]?.date }}</span>
                    </div>
                </div>

                <!-- Faculty comparison -->
                <div v-if="byFaculty.length > 0" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                    <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700">Faculty Comparison</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead><tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Faculty</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Courses</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Responses</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Teaching</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Content</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Overall</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Performance</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="f in byFaculty" :key="f.faculty_id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-xs font-semibold text-gray-700">{{ getFacultyName(f.faculty_id) }}</td>
                                <td class="px-4 py-3 text-center text-xs text-gray-600">{{ f.total_courses }}</td>
                                <td class="px-4 py-3 text-center text-xs text-gray-600">{{ f.total_responses }}</td>
                                <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(f.avg_teaching)">{{ f.avg_teaching }}</td>
                                <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(f.avg_content)">{{ f.avg_content }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-base font-black" :class="ratingColor(f.avg_overall)">{{ f.avg_overall }}</span>
                                </td>
                                <td class="px-4 py-3 min-w-24">
                                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-full rounded-full" :class="ratingBg(f.avg_overall)"
                                            :style="{ width: barWidth(f.avg_overall) }"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </template>
        </div>
    </AdminLayout>
</template>
