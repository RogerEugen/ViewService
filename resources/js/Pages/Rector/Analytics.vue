<script setup>
import RectorLayout from '@/Layouts/RectorLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import MetricCard from '@/Components/MetricCard.vue';
import { UserGroupIcon, BookOpenIcon, BuildingOffice2Icon, CheckCircleIcon, ClockIcon, StarIcon, InboxIcon, ArrowUpRightIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    windows:       { type: Array,  default: () => [] },
    activeWindow:  { type: Object, default: null },
    overview:      { type: Object, default: () => ({}) },
    byFaculty:     { type: Array,  default: () => [] },
    trends:        { type: Array,  default: () => [] },
    feedbackStats: { type: Object, default: () => ({}) },
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

const resolutionRate = computed(() => {
    const t = props.feedbackStats.total ?? 0;
    const r = props.feedbackStats.resolved ?? 0;
    return t > 0 ? Math.round((r / t) * 100) : 0;
});

const maxTrend = computed(() =>
    Math.max(...(props.trends.map(t => t.count)), 1)
);

// Chart bar heights
const trendBarHeight = (count) =>
    Math.max(4, Math.round((count / maxTrend.value) * 100)) + 'px';

const systemAvg = computed(() =>
    props.overview?.system_averages ?? {}
);
</script>

<template>
    <RectorLayout>
        <Head title="Analytics" />
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Evaluation Analytics</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Campus-wide course evaluation insights</p>
                </div>
                <button @click="router.visit(route('rector.feedbacks'))"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                    ← Back to Feedbacks
                </button>
            </div>
        </template>

        <div class="py-8 px-4 max-w-7xl mx-auto space-y-6">

            <!-- No active window -->
            <div v-if="!activeWindow" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-10 text-center">
                <p class="text-sm font-semibold text-gray-500">No active evaluation window</p>
                <p class="text-xs text-gray-400 mt-1">Analytics will appear when an evaluation window is active with responses.</p>
            </div>

            <template v-if="activeWindow">

                <!-- Window info -->
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="mb-1 text-xs font-semibold text-emerald-600">Selected evaluation period</p>
                            <h3 class="font-bold text-lg">{{ activeWindow.title }}</h3>
                            <p class="text-xs text-slate-500">{{ activeWindow.academic_year }} — Semester {{ activeWindow.semester }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-black">{{ overview.total_evaluations ?? 0 }}</p>
                            <p class="text-xs text-slate-500">Total Submissions</p>
                        </div>
                    </div>
                </div>

                <!-- Key metrics row -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                    <MetricCard label="Students participated" :value="overview.unique_students" :icon="UserGroupIcon" tone="indigo" />
                    <MetricCard label="Courses evaluated" :value="overview.unique_courses" :icon="BookOpenIcon" tone="blue" />
                    <MetricCard label="Departments" :value="overview.unique_departments" :icon="BuildingOffice2Icon" tone="violet" />
                    <MetricCard label="With results" :value="overview.courses_with_results" :icon="CheckCircleIcon" tone="emerald" />
                    <MetricCard label="Pending below 5" :value="overview.courses_pending" :icon="ClockIcon" tone="amber" />
                    <MetricCard label="System average /5" :value="systemAvg.overall ?? '—'" :icon="StarIcon" tone="orange" />
                </div>

                <!-- 3-column grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- System averages breakdown -->
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
                                    <span class="font-bold" :class="ratingColor(item.val)">{{ item.val ?? '—' }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700"
                                        :class="ratingBg(item.val ?? 0)"
                                        :style="{ width: barWidth(item.val ?? 0) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top performing courses -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-green-500">★</span> Top Courses
                        </h3>
                        <div v-if="!overview.top_courses?.length" class="text-center text-xs text-gray-400 py-4">
                            No results yet
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="(c, i) in overview.top_courses" :key="c.course_code"
                                class="flex items-center gap-3">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-black"
                                    :class="i === 0 ? 'bg-yellow-100 text-yellow-700' : i === 1 ? 'bg-gray-100 text-gray-600' : 'bg-orange-100 text-orange-600'">
                                    {{ i + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-mono font-bold text-gray-900">{{ c.course_code }}</p>
                                    <p class="text-xs text-gray-400">{{ c.total_responses }} responses</p>
                                </div>
                                <span class="text-base font-black" :class="ratingColor(c.avg_overall)">
                                    {{ c.avg_overall }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Needs attention -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-red-500">⚠</span> Needs Attention
                        </h3>
                        <div v-if="!overview.needs_attention?.length" class="text-center text-xs text-gray-400 py-4">
                            All courses performing well
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="(c, i) in overview.needs_attention" :key="c.course_code"
                                class="flex items-center gap-3">
                                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-black text-red-600">
                                    !
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-mono font-bold text-gray-900">{{ c.course_code }}</p>
                                    <p class="text-xs text-gray-400">{{ c.total_responses }} responses</p>
                                </div>
                                <span class="text-base font-black text-red-600">{{ c.avg_overall }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submission trend chart -->
                <div v-if="trends.length > 0" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Evaluation Submissions Over Time</h3>
                    <div class="flex items-end gap-1 h-32">
                        <div v-for="t in trends" :key="t.date"
                            class="flex-1 flex flex-col items-center gap-1 group">
                            <div class="relative w-full">
                                <div class="w-full rounded-t bg-indigo-500 hover:bg-indigo-600 transition cursor-default"
                                    :style="{ height: Math.max(4, Math.round((t.count / maxTrend) * 112)) + 'px' }">
                                </div>
                                <!-- Tooltip -->
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:flex bg-gray-800 text-white text-xs rounded px-1.5 py-0.5 whitespace-nowrap">
                                    {{ t.count }} on {{ t.date }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-2">
                        <span class="text-xs text-gray-400">{{ trends[0]?.date }}</span>
                        <span class="text-xs text-gray-400">{{ trends[trends.length - 1]?.date }}</span>
                    </div>
                </div>

                <!-- Faculty comparison -->
                <div v-if="byFaculty.length > 0" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                    <div class="border-b border-gray-100 px-5 py-3 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700">Faculty Performance Comparison</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Faculty ID</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Courses</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Responses</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Teaching</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Content</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Overall</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500">Performance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="f in byFaculty" :key="f.faculty_id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs font-semibold text-gray-700">Faculty #{{ f.faculty_id }}</td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-600">{{ f.total_courses }}</td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-600">{{ f.total_responses }}</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(f.avg_teaching)">{{ f.avg_teaching }}</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold" :class="ratingColor(f.avg_content)">{{ f.avg_content }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-base font-black" :class="ratingColor(f.avg_overall)">{{ f.avg_overall }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-700"
                                                    :class="ratingBg(f.avg_overall)"
                                                    :style="{ width: barWidth(f.avg_overall) }">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Feedback stats section -->
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Campus Feedback Overview</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <MetricCard label="Total feedback" :value="feedbackStats.total" :icon="InboxIcon" tone="blue" />
                        <MetricCard label="Resolved" :value="feedbackStats.resolved" :icon="CheckCircleIcon" tone="emerald" />
                        <MetricCard label="Escalated" :value="feedbackStats.escalated" :icon="ArrowUpRightIcon" tone="orange" />
                        <MetricCard label="Urgent" :value="feedbackStats.urgent" :icon="ExclamationTriangleIcon" tone="rose" />
                    </div>
                    <!-- Resolution progress bar -->
                    <div class="mt-4">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500">Resolution Rate</span>
                            <span class="font-bold" :class="(feedbackStats.resolved / Math.max(feedbackStats.total, 1)) >= 0.7 ? 'text-green-600' : 'text-orange-600'">
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

            </template>
        </div>
    </RectorLayout>
</template>
