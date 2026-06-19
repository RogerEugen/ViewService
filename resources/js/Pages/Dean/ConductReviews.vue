<script setup>
import DeanLayout from '@/Layouts/DeanLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ShieldExclamationIcon } from '@heroicons/vue/24/outline';

defineProps({
    reviews: { type: Array, default: () => [] },
    user: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const markReviewed = (id) => router.post(route('dean.conduct-reviews.mark', id), {}, {
    preserveScroll: true,
});

const formatDate = (value) => value
    ? new Date(value).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })
    : 'Not reviewed';
</script>

<template>
    <DeanLayout>
        <Head title="Restricted Conduct Reviews" />
        <template #header>
            <div>
                <h1 class="text-xl font-black text-slate-950">Restricted Conduct Reviews</h1>
                <p class="mt-1 text-xs text-slate-500">Identity review for users who repeatedly attempted to submit abusive language.</p>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex gap-3">
                    <ShieldExclamationIcon class="h-6 w-6 flex-none text-amber-600" />
                    <div>
                        <p class="text-sm font-black text-amber-900">Confidential identity access</p>
                        <p class="mt-1 text-xs leading-5 text-amber-700">This page is visible only to the Dean of the affected faculty. Identity data is shown only after a second blocked language violation and is never attached to legitimate anonymous feedback.</p>
                    </div>
                </div>
            </div>

            <div v-if="flash.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ flash.success }}</div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-black text-slate-900">Repeat Language Violations</h2>
                    <p class="mt-1 text-xs text-slate-400">{{ reviews.length }} restricted review record(s)</p>
                </div>
                <div v-if="reviews.length === 0" class="px-5 py-14 text-center text-sm text-slate-400">No repeat language violations require review.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400"><tr><th class="px-5 py-3">Identity</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Department</th><th class="px-5 py-3">Violations</th><th class="px-5 py-3">Last Attempt</th><th class="px-5 py-3 text-right">Review</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-for="review in reviews" :key="review.id">
                                <td class="px-5 py-4"><p class="font-black text-slate-900">{{ review.name }}</p><p class="mt-1 text-slate-500">{{ review.registration_number || review.email }}</p></td>
                                <td class="px-5 py-4 font-bold capitalize text-slate-600">{{ review.role }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ review.department || '—' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-rose-50 px-2.5 py-1 font-black text-rose-600">{{ review.violation_count }}</span></td>
                                <td class="px-5 py-4 text-slate-500">{{ formatDate(review.last_violation_at) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <span v-if="review.reviewed_at" class="font-bold text-emerald-600">Reviewed</span>
                                    <button v-else class="rounded-lg bg-blue-600 px-3 py-2 font-bold text-white hover:bg-blue-700" @click="markReviewed(review.id)">Mark reviewed</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DeanLayout>
</template>
