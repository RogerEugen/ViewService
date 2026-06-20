<script setup>
import { computed, ref } from 'vue';
import { ArrowTrendingUpIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    trends: { type: Array, default: () => [] },
    title: { type: String, default: 'Evaluation Submissions Over Time' },
});

const hoveredIndex = ref(null);
const chart = { left: 58, right: 970, top: 18, bottom: 124 };

const points = computed(() => {
    const max = Math.max(...props.trends.map((item) => Number(item.count)), 1);
    const range = chart.right - chart.left;

    return props.trends.map((item, index) => {
        const x = props.trends.length === 1
            ? (chart.left + chart.right) / 2
            : chart.left + (index / (props.trends.length - 1)) * range;
        const y = chart.bottom - (Number(item.count) / max) * (chart.bottom - chart.top);

        return {
            ...item,
            count: Number(item.count),
            x,
            y,
            tooltipY: y < 48 ? y + 14 : y - 38,
            tooltipTextY: y < 48 ? y + 30 : y - 22,
        };
    });
});

const maxCount = computed(() => Math.max(...props.trends.map((item) => Number(item.count)), 1));
const total = computed(() => props.trends.reduce((sum, item) => sum + Number(item.count), 0));
const peak = computed(() => props.trends.reduce(
    (highest, item) => Number(item.count) > Number(highest?.count ?? -1) ? item : highest,
    null,
));
const linePoints = computed(() => points.value.map((point) => `${point.x},${point.y}`).join(' '));
const areaPath = computed(() => {
    if (points.value.length < 2) return '';
    return `M ${points.value[0].x} ${chart.bottom} L ${points.value.map((point) => `${point.x} ${point.y}`).join(' L ')} L ${points.value.at(-1).x} ${chart.bottom} Z`;
});
const gridLines = computed(() => [0, 0.5, 1].map((ratio) => ({
    y: chart.bottom - ratio * (chart.bottom - chart.top),
    label: Math.round(maxCount.value * ratio),
})).reverse());
const visibleLabels = computed(() => {
    if (points.value.length <= 6) return points.value;
    return points.value.filter((_, index) =>
        index === 0
        || index === points.value.length - 1
        || index % Math.ceil(points.value.length / 5) === 0
    );
});
const formatDate = (value) => new Date(`${value}T00:00:00`).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
});
</script>

<template>
    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-black text-slate-900">{{ title }}</h3>
                <p class="mt-1 text-xs text-slate-500">Daily evaluation activity for the selected period</p>
            </div>
            <div class="flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700">
                <ArrowTrendingUpIcon class="h-4 w-4" />
                <span class="text-sm font-black">{{ total }}</span>
                <span class="text-[11px] font-semibold">submissions</span>
            </div>
        </div>

        <div v-if="trends.length === 1" class="mt-4 grid grid-cols-[minmax(0,1fr)_auto] items-end gap-5 rounded-xl bg-slate-50 px-4 py-4">
            <div>
                <div class="mb-2 flex items-center justify-between gap-3 text-xs">
                    <span class="font-semibold text-slate-500">{{ formatDate(trends[0].date) }}</span>
                    <span class="font-black text-slate-900">{{ trends[0].count }} submissions</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full w-full rounded-full bg-blue-600"></div>
                </div>
                <p class="mt-2 text-[11px] text-slate-400">All recorded evaluation activity occurred on this day.</p>
            </div>
            <div class="hidden h-14 w-14 place-items-center rounded-xl border border-blue-100 bg-white text-center sm:grid">
                <p class="text-xl font-black leading-none text-blue-600">{{ trends[0].count }}</p>
                <p class="mt-1 text-[9px] font-bold uppercase tracking-wide text-slate-400">Peak</p>
            </div>
        </div>

        <div v-else-if="trends.length > 1" class="mt-4 overflow-x-auto">
            <div class="relative min-w-[620px]">
                <svg viewBox="0 0 1000 158" class="h-[158px] w-full" role="img" :aria-label="title">
                    <defs>
                        <linearGradient id="trendArea" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.22" />
                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.02" />
                        </linearGradient>
                    </defs>

                    <g v-for="line in gridLines" :key="line.y">
                        <line :x1="chart.left" :x2="chart.right" :y1="line.y" :y2="line.y" stroke="#e2e8f0" stroke-dasharray="4 5" />
                        <text :x="chart.left - 14" :y="line.y + 4" text-anchor="end" class="fill-slate-400 text-[11px]">{{ line.label }}</text>
                    </g>

                    <path v-if="areaPath" :d="areaPath" fill="url(#trendArea)" />
                    <polyline
                        v-if="points.length > 1"
                        :points="linePoints"
                        fill="none"
                        stroke="#2563eb"
                        stroke-width="4"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <line
                        v-else
                        :x1="points[0].x"
                        :x2="points[0].x"
                        :y1="points[0].y"
                        :y2="chart.bottom"
                        stroke="#bfdbfe"
                        stroke-width="3"
                        stroke-dasharray="5 5"
                    />

                    <g
                        v-for="(point, index) in points"
                        :key="point.date"
                        class="cursor-pointer"
                        @mouseenter="hoveredIndex = index"
                        @mouseleave="hoveredIndex = null"
                    >
                        <circle :cx="point.x" :cy="point.y" r="10" fill="#dbeafe" />
                        <circle :cx="point.x" :cy="point.y" r="5" fill="#2563eb" stroke="white" stroke-width="2" />
                        <rect
                            :x="point.x - 28"
                            :y="point.tooltipY"
                            width="56"
                            height="24"
                            rx="8"
                            :fill="hoveredIndex === index || points.length === 1 ? '#0f172a' : '#ffffff'"
                            :stroke="hoveredIndex === index || points.length === 1 ? '#0f172a' : '#e2e8f0'"
                        />
                        <text
                            :x="point.x"
                            :y="point.tooltipTextY"
                            text-anchor="middle"
                            :fill="hoveredIndex === index || points.length === 1 ? '#ffffff' : '#334155'"
                            class="text-[11px] font-bold"
                        >{{ point.count }}</text>
                    </g>

                    <text
                        v-for="point in visibleLabels"
                        :key="`label-${point.date}`"
                        :x="point.x"
                        y="151"
                        text-anchor="middle"
                        class="fill-slate-400 text-[11px]"
                    >{{ formatDate(point.date) }}</text>
                </svg>
            </div>
        </div>

        <div v-else class="grid min-h-32 place-items-center text-center">
            <div>
                <CalendarDaysIcon class="mx-auto h-9 w-9 text-slate-300" />
                <p class="mt-3 text-sm font-bold text-slate-600">No submission activity yet</p>
            </div>
        </div>

        <div v-if="peak && trends.length > 1" class="mt-2 flex items-center gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
            <ArrowTrendingUpIcon class="h-4 w-4 text-blue-600" />
            Peak activity: <strong class="text-slate-700">{{ peak.count }} submissions</strong>
            on {{ formatDate(peak.date) }}.
        </div>
    </section>
</template>
