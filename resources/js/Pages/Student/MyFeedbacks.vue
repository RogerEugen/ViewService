<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
</script>

<template>
    <AuthenticatedLayout>
        <Head title="My Anonymous Feedback" />
        <template #header>
            <h1 class="text-xl font-bold text-slate-900">My Anonymous Feedback</h1>
        </template>

        <div class="mx-auto max-w-3xl px-4 py-10">
            <div class="rounded-3xl border border-indigo-100 bg-white p-8 text-center shadow-xl shadow-indigo-100/60">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-indigo-100 text-3xl">🔐</div>
                <h2 class="mt-5 text-xl font-black text-slate-900">Track feedback privately</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                    Anonymous submissions are not linked to a visible user history. Use the private tracking code provided after submission to view updates and continue the conversation.
                </p>
                <button
                    type="button"
                    class="mt-6 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-700"
                    @click="router.visit(route('student.track'))"
                >
                    Open Feedback Tracker
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
