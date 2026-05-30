<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    windows: { type: Array, default: () => [] },
    user:    { type: Object, default: () => ({}) },
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const form = useForm({
    title:         '',
    academic_year: '',
    semester:      1,
    opens_at:      '',
    closes_at:     '',
});

const showForm = ref(false);

const submit = () => {
    form.post(route('admin.evaluation-windows.store'), {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const toggle = (id) => {
    router.post(route('admin.evaluation-windows.toggle', id));
};

const deleteWindow = (id) => {
    if (confirm('Delete this evaluation window? This cannot be undone.')) {
        router.delete(route('admin.evaluation-windows.delete', id));
    }
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
}) : '—';

const windowStatus = (w) => {
    const now = new Date();
    if (!w.is_active) return { label: 'Inactive', color: 'bg-gray-100 text-gray-600' };
    if (new Date(w.opens_at) > now) return { label: 'Scheduled', color: 'bg-blue-100 text-blue-700' };
    if (new Date(w.closes_at) < now) return { label: 'Closed', color: 'bg-red-100 text-red-700' };
    return { label: 'Open', color: 'bg-green-100 text-green-700' };
};
</script>

<template>
    <AdminLayout>
        <Head title="Evaluation Windows" />
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Evaluation Windows</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Manage course evaluation periods</p>
                </div>
                <button @click="showForm = !showForm"
                    class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                    + New Window
                </button>
            </div>
        </template>

        <div class="py-8 px-4 max-w-6xl mx-auto space-y-6">

            <!-- Flash -->
            <div v-if="flash.success" class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 font-medium flex gap-2">
                <svg class="h-4 w-4 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 font-medium">{{ flash.error }}</div>

            <!-- Create form -->
            <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="showForm" class="rounded-xl border border-indigo-200 bg-indigo-50 p-5">
                    <h3 class="text-sm font-semibold text-indigo-800 mb-4">Create New Evaluation Window</h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Window Title *</label>
                                <input v-model="form.title" type="text" placeholder="e.g. Semester 1 Course Evaluation 2024/2025"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required/>
                                <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Academic Year * (e.g. 2024/2025)</label>
                                <input v-model="form.academic_year" type="text" placeholder="2024/2025"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required/>
                                <p v-if="form.errors.academic_year" class="mt-1 text-xs text-red-500">{{ form.errors.academic_year }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Semester *</label>
                                <select v-model="form.semester"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white">
                                    <option :value="1">Semester 1</option>
                                    <option :value="2">Semester 2</option>
                                </select>
                            </div>
                            <!-- In the opens_at input — remove :min attribute -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Opens At *</label>
                                <input v-model="form.opens_at" type="datetime-local"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                    required />
                                <p class="mt-1 text-xs text-gray-400">Admin can set any open time including the past</p>
                                <p v-if="form.errors.opens_at" class="mt-1 text-xs text-red-500">{{ form.errors.opens_at
                                    }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Closes At *</label>
                                <input v-model="form.closes_at" type="datetime-local"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" required/>
                                <p v-if="form.errors.closes_at" class="mt-1 text-xs text-red-500">{{ form.errors.closes_at }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                {{ form.processing ? 'Creating...' : 'Create Window' }}
                            </button>
                            <button type="button" @click="showForm = false"
                                class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </transition>

            <!-- Info box -->
            <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 flex gap-2">
                <svg class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-blue-700">
                    Only one window can be active at a time. Results are hidden until a course receives at least
                    <strong>5 evaluations</strong> to protect student anonymity.
                </p>
            </div>

            <!-- Windows list -->
            <div v-if="windows.length === 0" class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-12 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v11.25A2.25 2.25 0 009 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25"/>
                </svg>
                <p class="text-sm text-gray-400">No evaluation windows yet. Create one to get started.</p>
            </div>

            <div v-else class="space-y-3">
                <div v-for="w in windows" :key="w.id"
                    class="rounded-xl border bg-white p-5 flex items-start justify-between gap-4"
                    :class="w.is_open ? 'border-green-200 bg-green-50/30' : 'border-gray-200'"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-sm font-semibold text-gray-900">{{ w.title }}</h3>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="windowStatus(w).color">
                                {{ windowStatus(w).label }}
                            </span>
                            <span v-if="w.is_open" class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute h-2 w-2 rounded-full bg-green-400 opacity-75"></span>
                                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-gray-500">
                            <span>📅 {{ w.academic_year }} — Semester {{ w.semester }}</span>
                            <span>🔓 Opens: {{ formatDate(w.opens_at) }}</span>
                            <span>🔒 Closes: {{ formatDate(w.closes_at) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click="toggle(w.id)"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold border transition"
                            :class="w.is_active
                                ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                                : 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100'"
                        >
                            {{ w.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button @click="deleteWindow(w.id)"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-500 hover:bg-gray-100">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
