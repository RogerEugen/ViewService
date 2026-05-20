<script setup>
import LectureLayout from '@/Layouts/LectureLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user:            { type: Object, default: () => ({}) },
    categories:      { type: Array,  default: () => [] },
    recentFeedbacks: { type: Array,  default: () => [] },
    departmentId:    { type: Number, default: null },
    profile:         { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const statusColor = (s) => ({
    submitted:    'bg-blue-100 text-blue-700',
    under_review: 'bg-yellow-100 text-yellow-700',
    escalated:    'bg-orange-100 text-orange-700',
    resolved:     'bg-green-100 text-green-700',
    closed:       'bg-gray-100 text-gray-600',
}[s] ?? 'bg-gray-100 text-gray-600');

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const resolvedCount = computed(() =>
    props.recentFeedbacks.filter(f => f.status === 'resolved').length
);

const firstName = computed(() => props.user?.first_name ?? props.user?.name?.split(' ')[0] ?? 'Lecturer');
const goToFeedback = () => router.visit(route('lecture.feedback'));
</script>

<template>
    <LectureLayout>
        <Head title="Lecturer Dashboard" />

        <div class="min-h-screen bg-gray-50">
            <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

                <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm font-semibold text-green-800">
                    {{ flash.success }}
                    <span v-if="flash.tracking_code" class="ml-2 font-mono">({{ flash.tracking_code }})</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Lecturer Feedback Center</h1>
                            <p class="text-sm text-gray-500 mt-1">Submit anonymous feedback and track recent submissions from your department.</p>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-6">
                            <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Feedback Categories
                            </h2>

                            <div v-if="categories.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    @click="goToFeedback"
                                    class="rounded-xl border border-gray-200 p-4 text-left hover:border-purple-300 hover:bg-purple-50 transition flex items-start gap-3"
                                >
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100">
                                        <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ cat.name }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ cat.description }}</p>
                                    </div>
                                </button>
                            </div>

                            <div v-else class="text-center py-8">
                                <p class="text-sm text-gray-400">No lecturer feedback categories available.</p>
                                <button @click="goToFeedback" class="mt-2 text-sm text-purple-600 font-medium hover:underline">
                                    Go to feedback form →
                                </button>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                                    </svg>
                                    Recent Submissions <span class="text-xs text-gray-400 font-normal">from your department</span>
                                </h2>
                                <button @click="router.visit(route('lecture.track'))" class="text-xs font-semibold text-purple-600 hover:text-purple-700">
                                    Track Feedback →
                                </button>
                            </div>

                            <div v-if="recentFeedbacks.length === 0" class="px-5 py-10 text-center">
                                <p class="text-sm text-gray-400">No feedback submissions yet from your department.</p>
                            </div>

                            <div v-else class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="f in recentFeedbacks" :key="f.id" class="hover:bg-gray-50">
                                            <td class="px-5 py-3 text-sm font-medium text-gray-800">{{ f.category ?? 'Unknown' }}</td>
                                            <td class="px-5 py-3">
                                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusColor(f.status)">
                                                    {{ f.status }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 text-xs text-gray-400">{{ formatDate(f.submitted_at) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-700 p-5 text-white">
                            <p class="text-xs text-purple-200 mb-1">Welcome back,</p>
                            <h3 class="text-lg font-bold">{{ firstName }}</h3>
                            <p class="text-xs text-purple-200 mt-1">{{ profile.department ?? 'Lecturer' }}</p>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-5">
                            <h3 class="text-sm font-bold text-gray-800 mb-3">Quick Actions</h3>
                            <div class="space-y-2">
                                <button @click="router.visit(route('lecture.feedback'))" class="w-full rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 text-left text-xs font-semibold text-purple-700 hover:bg-purple-100 flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    Submit New Feedback
                                </button>
                                <button @click="router.visit(route('lecture.track'))" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-left text-xs font-semibold text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 11.25l-3-3m0 0l-3 3m3-3v7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Track My Feedback
                                </button>
                                <button @click="router.visit(route('lecture.evaluations'))" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-left text-xs font-semibold text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                    Evaluation Results
                                </button>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-5">
                            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                                </svg>
                                Impact Summary
                            </h3>
                            <p class="text-3xl font-black text-green-600 mt-2">{{ resolvedCount }}</p>
                            <p class="text-xs text-gray-500 mt-1">Resolved feedbacks in your department list.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LectureLayout>
</template>
