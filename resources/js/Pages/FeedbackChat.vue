<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import {
    ChatBubbleLeftRightIcon,
    KeyIcon,
    LockClosedIcon,
    MagnifyingGlassIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import LectureLayout from '@/Layouts/LectureLayout.vue';
import RectorLayout from '@/Layouts/RectorLayout.vue';
import { listenToFeedback } from '@/lib/realtime';

const props = defineProps({
    currentRole: { type: String, required: true },
    selectedCode: { type: String, default: '' },
    threads: { type: Array, default: () => [] },
    thread: { type: Object, default: null },
    error: { type: String, default: null },
});

const Layout = computed(() => props.currentRole === 'lecturer' ? LectureLayout : RectorLayout);
const isLecturer = computed(() => props.currentRole === 'lecturer');
const trackingCode = ref(props.selectedCode);
const search = ref('');
const connectionState = ref('connecting');
const messagesPanel = ref(null);
const form = useForm({
    tracking_code: props.selectedCode,
    message: '',
});

const filteredThreads = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.threads;

    return props.threads.filter((item) => [
        item.tracking_code,
        item.category,
        item.status,
    ].some((value) => String(value ?? '').toLowerCase().includes(term)));
});

const notificationSound = typeof Audio !== 'undefined'
    ? new Audio(
        import.meta.env.VITE_NOTIFICATION_SOUND_URL
            || 'http://127.0.0.1:8002/sounds/notification.mp3',
    )
    : null;
if (notificationSound) notificationSound.preload = 'auto';

const openTrackingCode = () => {
    const code = trackingCode.value.trim().toUpperCase();
    if (!code) return;

    router.get(route('lecture.rector-chat'), { code }, {
        preserveScroll: true,
        preserveState: false,
    });
};

const selectThread = (code) => {
    router.get(route('rector.lecturer-chats'), { code }, {
        preserveScroll: true,
        preserveState: false,
    });
};

const send = () => {
    if (!props.thread || !form.message.trim()) return;
    form.tracking_code = props.thread.tracking_code;
    form.post(route(isLecturer.value ? 'lecture.rector-chat.send' : 'rector.lecturer-chats.send'), {
        preserveScroll: true,
        onSuccess: () => form.reset('message'),
    });
};

const formatTime = (value) => value
    ? new Date(value).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })
    : '';

const formatStatus = (value) => String(value ?? '')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());

const statusClass = (status) => ({
    submitted: 'bg-blue-50 text-blue-700',
    under_review: 'bg-amber-50 text-amber-700',
    escalated: 'bg-orange-50 text-orange-700',
    resolved: 'bg-emerald-50 text-emerald-700',
}[status] ?? 'bg-slate-100 text-slate-600');

const scrollToLatest = () => nextTick(() => {
    if (messagesPanel.value) {
        messagesPanel.value.scrollTop = messagesPanel.value.scrollHeight;
    }
});

const playNotification = () => {
    if (!notificationSound) return;
    notificationSound.currentTime = 0;
    notificationSound.play().catch(() => {});
};

const unlockNotificationSound = () => {
    if (!notificationSound) return;
    const volume = notificationSound.volume;
    notificationSound.volume = 0;
    notificationSound.play()
        .then(() => {
            notificationSound.pause();
            notificationSound.currentTime = 0;
            notificationSound.volume = volume;
        })
        .catch(() => { notificationSound.volume = volume; });
};

let stopRealtime = () => {};
const connectRealtime = () => {
    stopRealtime();
    connectionState.value = 'connecting';
    stopRealtime = listenToFeedback(
        props.thread?.realtime_channel,
        (event) => {
            if (event?.message?.sender_role !== props.currentRole) playNotification();
            router.reload({
                only: ['thread', 'threads'],
                preserveScroll: true,
                onSuccess: scrollToLatest,
            });
        },
        (state) => { connectionState.value = state; },
    );
};

watch(() => props.selectedCode, (code) => {
    trackingCode.value = code;
    form.tracking_code = code;
});
watch(() => props.thread?.realtime_channel, connectRealtime);
watch(() => props.thread?.messages, scrollToLatest, { deep: true });

onMounted(() => {
    connectRealtime();
    scrollToLatest();
});
onUnmounted(() => stopRealtime());
</script>

<template>
    <component :is="Layout">
        <Head :title="isLecturer ? 'Private Rector Chat' : 'Lecturer Feedback Chats'" />

        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-950">
                        {{ isLecturer ? 'Private Rector Chat' : 'Lecturer Feedback Chats' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Each tracking code opens one isolated, encrypted feedback conversation.
                    </p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700">
                    <ShieldCheckIcon class="h-4 w-4" />
                    Identity protected
                </span>
            </div>
        </template>

        <div
            class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
            @pointerdown.once="unlockNotificationSound"
        >
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60 lg:grid lg:grid-cols-[330px_1fr]">
                <aside class="border-b border-slate-200 bg-slate-50 lg:min-h-[700px] lg:border-b-0 lg:border-r">
                    <div class="border-b border-slate-200 bg-white p-4">
                        <form v-if="isLecturer" class="space-y-3" @submit.prevent="openTrackingCode">
                            <div class="flex items-center gap-2">
                                <KeyIcon class="h-5 w-5 text-blue-600" />
                                <p class="text-sm font-black text-slate-900">Unlock your conversation</p>
                            </div>
                            <p class="text-xs leading-5 text-slate-500">
                                Enter the tracking code received after submitting your lecturer feedback.
                            </p>
                            <div class="flex gap-2">
                                <input
                                    v-model="trackingCode"
                                    type="text"
                                    maxlength="30"
                                    placeholder="FB-2026-XXXXXXXX"
                                    class="min-w-0 flex-1 rounded-xl border-slate-200 bg-slate-50 text-xs font-bold uppercase focus:border-blue-500 focus:ring-blue-500"
                                />
                                <button type="submit" class="rounded-xl bg-blue-600 px-4 text-xs font-bold text-white hover:bg-blue-700">
                                    Open
                                </button>
                            </div>
                        </form>

                        <div v-else class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search code or category"
                                class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-xs focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <span class="text-blue-600">{{ isLecturer ? 'Private thread' : 'Lecturer inbox' }}</span>
                        <span class="ml-auto rounded-full bg-blue-600 px-2 py-0.5 text-white">
                            {{ isLecturer ? (thread ? 1 : 0) : filteredThreads.length }}
                        </span>
                    </div>

                    <div class="max-h-[570px] space-y-2 overflow-y-auto p-3">
                        <button
                            v-if="isLecturer && thread"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-2xl bg-blue-50 p-3 text-left ring-1 ring-blue-100"
                        >
                            <span class="grid h-11 w-11 place-items-center rounded-full bg-blue-600 text-sm font-black text-white">R</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-xs font-black text-slate-900">{{ thread.tracking_code }}</span>
                                <span class="mt-1 block truncate text-[11px] text-slate-500">{{ thread.category }}</span>
                            </span>
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        </button>

                        <template v-if="!isLecturer">
                            <button
                                v-for="item in filteredThreads"
                                :key="item.tracking_code"
                                type="button"
                                class="w-full rounded-2xl p-3 text-left transition"
                                :class="item.tracking_code === selectedCode ? 'bg-blue-50 ring-1 ring-blue-100' : 'hover:bg-white'"
                                @click="selectThread(item.tracking_code)"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="grid h-11 w-11 flex-none place-items-center rounded-full bg-amber-100 text-xs font-black text-amber-700">L</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-black text-slate-900">{{ item.tracking_code }}</span>
                                        <span class="mt-1 block truncate text-[11px] text-slate-500">{{ item.category }}</span>
                                    </span>
                                    <span class="rounded-full px-2 py-1 text-[9px] font-black" :class="statusClass(item.status)">
                                        {{ formatStatus(item.status) }}
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center justify-between pl-14 text-[10px] text-slate-400">
                                    <span>{{ item.messages_count }} messages</span>
                                    <span>{{ formatTime(item.last_activity_at) }}</span>
                                </div>
                            </button>
                        </template>

                        <div v-if="(isLecturer && !thread) || (!isLecturer && filteredThreads.length === 0)" class="px-5 py-12 text-center">
                            <LockClosedIcon class="mx-auto h-9 w-9 text-slate-300" />
                            <p class="mt-3 text-xs font-bold text-slate-600">
                                {{ isLecturer ? 'No conversation is open' : 'No lecturer threads found' }}
                            </p>
                            <p v-if="isLecturer" class="mt-1 text-[11px] leading-5 text-slate-400">
                                Other lecturers cannot browse or open your Rector replies.
                            </p>
                        </div>
                    </div>
                </aside>

                <section class="flex min-h-[700px] flex-col bg-white">
                    <template v-if="thread">
                        <header class="border-b border-slate-200 px-5 py-4 sm:px-7">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-11 w-11 place-items-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-700 font-black text-white">
                                        {{ isLecturer ? 'R' : 'L' }}
                                    </span>
                                    <div>
                                        <p class="font-black text-slate-950">{{ thread.tracking_code }}</p>
                                        <p class="text-xs font-medium" :class="connectionState === 'connected' ? 'text-emerald-600' : 'text-amber-600'">
                                            {{ connectionState === 'connected' ? 'Online · private real-time thread' : 'Connecting to live messages...' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-[10px] font-black" :class="statusClass(thread.status)">
                                        {{ formatStatus(thread.status) }}
                                    </span>
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-bold text-emerald-700">Encrypted</span>
                                </div>
                            </div>
                        </header>

                        <div ref="messagesPanel" class="flex-1 space-y-5 overflow-y-auto bg-slate-50/70 p-5 sm:p-7">
                            <article class="rounded-2xl border border-indigo-100 bg-indigo-50/80 p-4 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-black uppercase tracking-wider text-indigo-700">Original anonymous feedback</p>
                                    <span class="text-[11px] font-semibold text-slate-500">{{ thread.category }} · {{ formatTime(thread.submitted_at) }}</span>
                                </div>
                                <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ thread.content }}</p>
                            </article>

                            <div v-if="thread.messages.length === 0" class="py-12 text-center">
                                <ChatBubbleLeftRightIcon class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-3 text-sm font-bold text-slate-600">No replies yet</p>
                                <p class="mt-1 text-xs text-slate-400">Start a private conversation about this feedback.</p>
                            </div>

                            <div
                                v-for="message in thread.messages"
                                :key="message.id"
                                class="flex items-end gap-2"
                                :class="message.sender_role === 'rector' ? 'justify-end' : 'justify-start'"
                            >
                                <span
                                    v-if="message.sender_role !== 'rector'"
                                    class="grid h-8 w-8 place-items-center rounded-full bg-amber-100 text-[10px] font-black text-amber-700"
                                >L</span>
                                <div
                                    class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-[68%]"
                                    :class="message.sender_role === 'rector'
                                        ? 'rounded-br-md bg-blue-600 text-white'
                                        : 'rounded-bl-md border border-amber-200 bg-amber-50 text-amber-950'"
                                >
                                    <p class="mb-1 text-[10px] font-black uppercase tracking-wider opacity-70">
                                        {{ message.sender_role === 'rector' ? 'Rector' : 'Lecturer' }}
                                    </p>
                                    <p class="whitespace-pre-wrap text-sm leading-6">{{ message.content }}</p>
                                    <p class="mt-2 text-[10px] opacity-60">{{ formatTime(message.sent_at) }}</p>
                                </div>
                                <span
                                    v-if="message.sender_role === 'rector'"
                                    class="grid h-8 w-8 place-items-center rounded-full bg-blue-100 text-[10px] font-black text-blue-700"
                                >R</span>
                            </div>
                        </div>

                        <form class="border-t border-slate-200 bg-white p-4" @submit.prevent="send">
                            <div class="flex items-end gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                                <ChatBubbleLeftRightIcon class="mb-2 ml-2 h-5 w-5 flex-none text-slate-400" />
                                <textarea
                                    v-model="form.message"
                                    rows="1"
                                    maxlength="3000"
                                    placeholder="Type a respectful message in English..."
                                    class="min-h-11 flex-1 resize-none border-0 bg-transparent py-2.5 text-sm focus:ring-0"
                                    @keydown.enter.exact.prevent="send"
                                />
                                <button
                                    type="submit"
                                    :disabled="form.processing || form.message.trim().length < 2"
                                    class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Sending...' : 'Send' }}
                                </button>
                            </div>
                            <p v-if="form.errors.message" class="mt-2 text-xs font-semibold text-red-600">{{ form.errors.message }}</p>
                        </form>
                    </template>

                    <div v-else class="grid flex-1 place-items-center p-8 text-center">
                        <div class="max-w-sm">
                            <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-blue-100">
                                <KeyIcon class="h-8 w-8 text-blue-700" />
                            </div>
                            <p class="mt-5 text-lg font-black text-slate-900">
                                {{ isLecturer ? 'Enter your tracking code' : 'Select a lecturer thread' }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                {{ error || (isLecturer
                                    ? 'The code is the private key that opens only the conversation linked to that feedback.'
                                    : 'Every tracking code has its own isolated conversation and feedback context.') }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </component>
</template>
