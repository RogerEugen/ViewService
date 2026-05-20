<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    user:            { type: Object, default: () => ({}) },
    categories:      { type: Array,  default: () => [] },
    recentFeedbacks: { type: Array,  default: () => [] },
    activeWindow:    { type: Object, default: null },
    departmentId:    { type: Number, default: null },
    facultyId:       { type: Number, default: null },
    profile:         { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

// Category icons mapping
const categoryIcon = (slug) => ({
    'academic-issues':        { icon: 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25', bg: 'bg-blue-100', color: 'text-blue-600' },
    'examination-concerns':   { icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z', bg: 'bg-orange-100', color: 'text-orange-600' },
    'student-affairs':        { icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', bg: 'bg-green-100', color: 'text-green-600' },
    'harassment-misconduct':  { icon: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z', bg: 'bg-red-100', color: 'text-red-600' },
    'infrastructure':         { icon: 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z', bg: 'bg-gray-100', color: 'text-gray-600' },
    'general-suggestion':     { icon: 'M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18', bg: 'bg-yellow-100', color: 'text-yellow-600' },
}[slug] ?? { icon: 'M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z', bg: 'bg-indigo-100', color: 'text-indigo-600' });

const statusColor = (s) => ({
    submitted:    'bg-blue-100 text-blue-700',
    under_review: 'bg-yellow-100 text-yellow-700',
    escalated:    'bg-orange-100 text-orange-700',
    resolved:     'bg-green-100 text-green-700',
    closed:       'bg-gray-100 text-gray-600',
}[s] ?? 'bg-gray-100 text-gray-600');

const statusLabel = (s) => ({
    submitted:    'Submitted',
    under_review: 'In Review',
    escalated:    'Escalated',
    resolved:     'Resolved',
    closed:       'Closed',
}[s] ?? s);

const priorityDot = (p) => ({
    low:    'bg-gray-400',
    medium: 'bg-blue-500',
    high:   'bg-orange-500',
    urgent: 'bg-red-600',
}[p] ?? 'bg-gray-400');

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
};

const resolvedCount = computed(() =>
    props.recentFeedbacks.filter(f => f.status === 'resolved').length
);

const goToFeedback = (categoryId) => {
    router.visit(route('student.feedback', { category_id: categoryId }));
};

const firstName = computed(() => props.user?.first_name ?? props.user?.name?.split(' ')[0] ?? 'Student');
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Dashboard" />

        <div class="min-h-screen bg-gray-50">
            <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

                <!-- Flash success -->
                <div v-if="flash.success"
                    class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 flex items-center gap-3">
                    <svg class="h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-semibold text-green-800">{{ flash.success }}</p>
                    <span v-if="flash.tracking_code" class="ml-auto font-mono text-xs font-bold text-green-700 bg-green-100 px-2 py-1 rounded">
                        Code: {{ flash.tracking_code }}
                    </span>
                </div>

                <!-- Main two-column layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- LEFT COLUMN — main content -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Header -->
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Feedback Center</h1>
                            <p class="text-sm text-gray-500 mt-1">
                                Help us improve your campus experience by sharing your thoughts.
                            </p>
                        </div>

                        <!-- Submit New Feedback section -->
                        <div class="rounded-2xl border border-gray-200 bg-white p-6">
                            <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Submit New Feedback
                            </h2>

                            <!-- Category cards grid -->
                            <div v-if="categories.length > 0"
                                class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    @click="goToFeedback(cat.id)"
                                    class="group flex items-start gap-4 rounded-xl border border-gray-200 p-4 text-left hover:border-indigo-300 hover:bg-indigo-50 hover:shadow-sm transition-all duration-200"
                                >
                                    <!-- Icon -->
                                    <div class="flex-shrink-0 flex h-11 w-11 items-center justify-center rounded-xl transition-colors"
                                        :class="[categoryIcon(cat.slug).bg, 'group-hover:scale-110 transition-transform']">
                                        <svg class="h-5 w-5" :class="categoryIcon(cat.slug).color" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="categoryIcon(cat.slug).icon"/>
                                        </svg>
                                    </div>
                                    <!-- Text -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-700">{{ cat.name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed line-clamp-2">{{ cat.description }}</p>
                                        <p class="text-xs text-indigo-500 mt-1.5 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                            Submit feedback →
                                        </p>
                                    </div>
                                </button>
                            </div>

                            <!-- No categories fallback -->
                            <div v-else class="text-center py-8">
                                <div class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-400">No feedback categories available.</p>
                                <button @click="router.visit(route('student.feedback'))"
                                    class="mt-2 text-sm text-indigo-600 font-medium hover:underline">
                                    Go to feedback form →
                                </button>
                            </div>
                        </div>

                        <!-- Evaluation window banner -->
                        <div v-if="activeWindow"
                            class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-4 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-sm">Course Evaluation Open!</p>
                                        <p class="text-xs text-indigo-200">{{ activeWindow.title }} — {{ activeWindow.academic_year }}</p>
                                    </div>
                                </div>
                                <button @click="router.visit(route('student.evaluations'))"
                                    class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-50 transition flex-shrink-0">
                                    Evaluate Now →
                                </button>
                            </div>
                        </div>

                        <!-- Recent Submissions -->
                        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                                    </svg>
                                    Recent Submissions
                                    <span class="text-xs text-gray-400 font-normal">from your department</span>
                                </h2>
                                <button @click="router.visit(route('student.track'))"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                                    Track Feedback →
                                </button>
                            </div>

                            <!-- Empty state -->
                            <div v-if="recentFeedbacks.length === 0"
                                class="px-5 py-10 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                                </svg>
                                <p class="text-sm text-gray-400">No feedback submissions yet from your department.</p>
                                <p class="text-xs text-gray-300 mt-1">Be the first to submit feedback!</p>
                            </div>

                            <!-- Table -->
                            <div v-else class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Priority</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="f in recentFeedbacks" :key="f.id"
                                            class="hover:bg-gray-50 transition">
                                            <td class="px-5 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="h-2 w-2 rounded-full flex-shrink-0"
                                                        :class="priorityDot(f.priority)"></div>
                                                    <span class="text-sm font-medium text-gray-800">{{ f.category ?? 'Unknown' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3">
                                                <span class="capitalize text-xs text-gray-500">{{ f.priority }}</span>
                                            </td>
                                            <td class="px-5 py-3">
                                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                                    :class="statusColor(f.status)">
                                                    {{ statusLabel(f.status) }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 text-xs text-gray-400">
                                                {{ formatDate(f.submitted_at) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bottom cards row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <!-- Impact summary -->
                            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100">
                                        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800">Impact Summary</h3>
                                </div>
                                <p class="text-3xl font-black text-green-600">{{ resolvedCount }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Feedbacks resolved from your department this period.
                                </p>
                            </div>

                            <!-- Security score -->
                            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100">
                                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800">Security Score</h3>
                                </div>
                                <p class="text-3xl font-black text-indigo-600">100%</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Anonymity verified across all submissions.
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN — sidebar -->
                    <div class="space-y-4">

                        <!-- Welcome card -->
                        <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 p-5 text-white">
                            <p class="text-xs text-indigo-200 mb-1">Welcome back,</p>
                            <h3 class="text-lg font-bold">{{ firstName }}</h3>
                            <p class="text-xs text-indigo-200 mt-1">
                                {{ profile.program ?? 'Student' }}
                            </p>
                            <div v-if="profile.department" class="mt-3 rounded-lg bg-white/10 px-3 py-2">
                                <p class="text-xs text-indigo-200">Department</p>
                                <p class="text-sm font-semibold">{{ profile.department }}</p>
                            </div>
                            <div v-if="profile.year_of_study" class="mt-2 flex gap-3">
                                <div class="rounded-lg bg-white/10 px-3 py-2 flex-1">
                                    <p class="text-xs text-indigo-200">Year</p>
                                    <p class="text-sm font-bold">{{ profile.year_of_study }}</p>
                                </div>
                                <div class="rounded-lg bg-white/10 px-3 py-2 flex-1">
                                    <p class="text-xs text-indigo-200">Semester</p>
                                    <p class="text-sm font-bold">{{ profile.semester }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Anonymous Privacy -->
                        <div class="rounded-2xl bg-indigo-600 px-5 py-5 text-white">
                            <h3 class="text-sm font-bold mb-2 flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                Anonymous Privacy
                            </h3>
                            <p class="text-xs text-indigo-200 leading-relaxed">
                                Your feedback is always encrypted and identity-protected by default. No personal information is ever stored with your submission.
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></div>
                                <p class="text-xs text-indigo-200 font-medium">Encryption active</p>
                            </div>
                        </div>

                        <!-- Quick actions -->
                        <div class="rounded-2xl border border-gray-200 bg-white p-5">
                            <h3 class="text-sm font-bold text-gray-800 mb-3">Quick Actions</h3>
                            <div class="space-y-2">
                                <button @click="router.visit(route('student.feedback'))"
                                    class="w-full flex items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-left hover:bg-indigo-100 transition">
                                    <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-600">
                                        <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    </div>
                                    <span class="text-xs font-semibold text-indigo-700">Submit New Feedback</span>
                                </button>
                                <button @click="router.visit(route('student.track'))"
                                    class="w-full flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 text-left hover:bg-gray-50 transition">
                                    <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-gray-200">
                                        <svg class="h-3.5 w-3.5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 11.25l-3-3m0 0l-3 3m3-3v7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-700">Track My Feedback</span>
                                </button>
                                <button v-if="activeWindow" @click="router.visit(route('student.evaluations'))"
                                    class="w-full flex items-center gap-3 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-left hover:bg-yellow-100 transition">
                                    <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-yellow-500">
                                        <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                                    </div>
                                    <span class="text-xs font-semibold text-yellow-700">Evaluate Courses</span>
                                </button>
                                <button @click="router.visit(route('student.myinfo'))"
                                    class="w-full flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 text-left hover:bg-gray-50 transition">
                                    <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-gray-200">
                                        <svg class="h-3.5 w-3.5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-700">My Profile</span>
                                </button>
                            </div>
                        </div>

                        <!-- Quick tip -->
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            <p class="text-xs font-bold text-blue-800 mb-1.5 flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                </svg>
                                Quick Tip
                            </p>
                            <p class="text-xs text-blue-700 leading-relaxed">
                                Detailed feedback helps administrators understand the root cause of issues faster and prioritize resolutions.
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>