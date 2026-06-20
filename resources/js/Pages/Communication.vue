<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { listenToCommunication } from '@/lib/realtime';
import HodLayout from '@/Layouts/HodLayout.vue';
import DeanLayout from '@/Layouts/DeanLayout.vue';
import RectorLayout from '@/Layouts/RectorLayout.vue';
import LectureLayout from '@/Layouts/LectureLayout.vue';

const props = defineProps({
    rooms: { type: Array, default: () => [] },
    selectedRoom: { type: String, default: '' },
    messages: { type: Array, default: () => [] },
    realtimeChannel: { type: String, default: null },
    currentRole: { type: String, default: '' },
});

const layouts = {
    hod: HodLayout,
    dean: DeanLayout,
    rector: RectorLayout,
    lecturer: LectureLayout,
};
const Layout = computed(() => layouts[props.currentRole] ?? RectorLayout);
const form = useForm({ room: props.selectedRoom, message: '' });
const liveMessages = ref([...props.messages]);
const connectionState = ref('connecting');
const messagesPanel = ref(null);
const roomSearch = ref('');
const leadershipFilter = ref('all');
const roomUnread = ref(Object.fromEntries(
    props.rooms.map((room) => [room.key, Number(room.unread_count ?? 0)])
));
const rectorFilters = [
    { key: 'all', label: 'All' },
    { key: 'hod', label: 'HODs' },
    { key: 'dean', label: 'Deans' },
];
const filteredRooms = computed(() => {
    const query = roomSearch.value.trim().toLowerCase();

    return props.rooms.filter((room) => {
        const matchesRole = props.currentRole !== 'rector'
            || leadershipFilter.value === 'all'
            || room.participant_role === leadershipFilter.value;
        const matchesSearch = query === ''
            || room.label.toLowerCase().includes(query);

        return matchesRole && matchesSearch;
    });
});
const totalUnread = computed(() => Object.values(roomUnread.value)
    .reduce((sum, count) => sum + Number(count || 0), 0));
const roleUnreadCount = (role) => props.rooms
    .filter((room) => role === 'all' || room.participant_role === role)
    .reduce((sum, room) => sum + Number(roomUnread.value[room.key] || 0), 0);
const notificationSound = typeof Audio !== 'undefined'
    ? new Audio(
        import.meta.env.VITE_NOTIFICATION_SOUND_URL
            || 'http://127.0.0.1:8002/sounds/notification.mp3',
    )
    : null;
if (notificationSound) notificationSound.preload = 'auto';

watch(() => props.messages, (messages) => {
    liveMessages.value = [...messages];
    scrollToLatest();
});
watch(() => props.rooms, (rooms) => {
    roomUnread.value = Object.fromEntries(
        rooms.map((room) => [room.key, Number(room.unread_count ?? 0)])
    );
}, { deep: true });

const selectRoom = (room) => {
    router.get(route('communications.index'), { room }, {
        preserveState: false,
        preserveScroll: true,
    });
};

const send = () => {
    form.room = props.selectedRoom;
    form.post(route('communications.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('message'),
    });
};

const roleLabel = (role) => ({
    hod: 'Head of Department',
    dean: 'Dean of Faculty',
    rector: 'Rector',
    lecturer: 'Lecturer',
}[role] ?? role);

const isRightSide = (role) => role === 'rector';

const messageBubbleClass = (role) => ({
    rector: 'rounded-br-md bg-blue-600 text-white',
    dean: 'rounded-bl-md border border-violet-200 bg-violet-50 text-violet-950',
    hod: 'rounded-bl-md border border-emerald-200 bg-emerald-50 text-emerald-950',
    lecturer: 'rounded-bl-md border border-amber-200 bg-amber-50 text-amber-950',
}[role] ?? 'rounded-bl-md border border-slate-200 bg-white text-slate-800');

const avatarClass = (role) => ({
    rector: 'bg-blue-100 text-blue-700',
    dean: 'bg-violet-100 text-violet-700',
    hod: 'bg-emerald-100 text-emerald-700',
    lecturer: 'bg-amber-100 text-amber-700',
}[role] ?? 'bg-slate-200 text-slate-600');

const formatTime = (value) => value
    ? new Date(value).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })
    : '';

const scrollToLatest = () => nextTick(() => {
    if (messagesPanel.value) {
        messagesPanel.value.scrollTop = messagesPanel.value.scrollHeight;
    }
});

const playNotification = () => {
    if (!notificationSound) return;
    notificationSound.currentTime = 0;
    notificationSound.play().catch(() => {
        // Browsers may block audio until the user has interacted with the page.
    });
};

const unlockNotificationSound = () => {
    if (!notificationSound) return;

    const originalVolume = notificationSound.volume;
    notificationSound.volume = 0;
    notificationSound.play()
        .then(() => {
            notificationSound.pause();
            notificationSound.currentTime = 0;
            notificationSound.volume = originalVolume;
        })
        .catch(() => {
            notificationSound.volume = originalVolume;
        });
};

const addRealtimeMessage = (event) => {
    const message = event?.message;
    if (!message || liveMessages.value.some((item) => item.id === message.id)) return;

    liveMessages.value.push(message);
    scrollToLatest();

    if (message.sender_role !== props.currentRole) {
        playNotification();
    }
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const markRoomRead = (room) => {
    if (!room) return;

    roomUnread.value[room] = 0;

    fetch(route('communications.read'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ room }),
    }).catch(() => {
        // The next page refresh will reconcile the persisted unread count.
    });
};

const handleRoomRealtime = (room, event) => {
    const message = event?.message;
    if (!message || message.sender_role === props.currentRole) return;

    if (room === props.selectedRoom) {
        addRealtimeMessage(event);
        markRoomRead(room);
        return;
    }

    roomUnread.value[room] = Number(roomUnread.value[room] || 0) + 1;
    playNotification();
};

let stopRealtime = () => {};
onMounted(() => {
    const listeners = props.rooms
        .filter((room) => room.realtime_channel)
        .map((room) => listenToCommunication(
            room.realtime_channel,
            (event) => handleRoomRealtime(room.key, event),
            room.key === props.selectedRoom
                ? (state) => { connectionState.value = state; }
                : () => {},
        ));
    stopRealtime = () => listeners.forEach((stop) => stop());
    markRoomRead(props.selectedRoom);
    scrollToLatest();
});
onUnmounted(() => stopRealtime());
</script>

<template>
    <component :is="Layout">
        <Head title="Secure Communication Hub" />

        <template #header>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Secure Communication Hub</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Encrypted, role-based real-time communication. Personal identities are not displayed.
                </p>
            </div>
        </template>

        <div
            class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
            @pointerdown.once="unlockNotificationSound"
        >
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg shadow-slate-200/60 lg:grid lg:grid-cols-[310px_1fr]">
                <aside class="border-b border-slate-200 bg-slate-50 lg:min-h-[680px] lg:border-b-0 lg:border-r">
                    <div class="border-b border-slate-200 bg-white p-4">
                        <div class="relative">
                            <input v-model="roomSearch" type="search" placeholder="Search conversations" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-4 pr-3 text-xs focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                    </div>
                    <div v-if="currentRole === 'rector'" class="border-b border-slate-200 bg-white px-3 py-3">
                        <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1">
                            <button
                                v-for="filter in rectorFilters"
                                :key="filter.key"
                                type="button"
                                class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-[10px] font-black transition"
                                :class="leadershipFilter === filter.key
                                    ? 'bg-white text-blue-700 shadow-sm'
                                    : 'text-slate-500 hover:text-slate-800'"
                                @click="leadershipFilter = filter.key"
                            >
                                {{ filter.label }}
                                <span
                                    class="rounded-full px-1.5 py-0.5 text-[9px]"
                                    :class="leadershipFilter === filter.key ? 'bg-blue-50 text-blue-700' : 'bg-slate-200 text-slate-500'"
                                >
                                    {{ roleUnreadCount(filter.key) }}
                                </span>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 border-b border-slate-200 px-4 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <span class="text-blue-600">Inbox</span>
                        <span>Encrypted</span>
                        <span
                            class="ml-auto rounded-full px-2 py-0.5 text-white"
                            :class="totalUnread > 0 ? 'bg-red-500' : 'bg-blue-600'"
                        >
                            {{ totalUnread }}
                        </span>
                    </div>
                    <div class="space-y-1 p-2">
                        <button
                            v-for="room in filteredRooms"
                            :key="room.key"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition"
                            :class="room.key === selectedRoom ? 'bg-blue-50 ring-1 ring-blue-100' : 'hover:bg-white'"
                            @click="selectRoom(room.key)"
                        >
                            <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-black text-white">
                                {{ room.label.slice(0, 1) }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-xs font-black text-slate-900">{{ room.label }}</span>
                                <span class="mt-1 block truncate text-[10px] text-slate-400">Secure real-time conversation</span>
                            </span>
                            <span
                                v-if="roomUnread[room.key] > 0"
                                class="grid min-w-5 place-items-center rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-black text-white"
                            >
                                {{ roomUnread[room.key] > 99 ? '99+' : roomUnread[room.key] }}
                            </span>
                            <span v-else-if="room.key === selectedRoom" class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        </button>
                        <div v-if="filteredRooms.length === 0" class="px-4 py-10 text-center">
                            <p class="text-xs font-bold text-slate-600">No conversations found</p>
                            <p class="mt-1 text-[10px] text-slate-400">Try another leader filter or search term.</p>
                        </div>
                    </div>
                </aside>

                <section class="flex min-h-[680px] flex-col bg-white">
                    <div class="border-b border-slate-200 bg-white px-5 py-4 sm:px-7">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-full bg-blue-100 text-sm font-black text-blue-700">{{ rooms.find((room) => room.key === selectedRoom)?.label?.slice(0, 1) ?? 'C' }}</span>
                                <div>
                                    <p class="font-black text-slate-900">{{ rooms.find((room) => room.key === selectedRoom)?.label ?? 'Communication' }}</p>
                                    <p class="text-xs font-medium" :class="connectionState === 'connected' ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ connectionState === 'connected' ? 'Online · messages delivered instantly' : 'Reconnecting to live messages...' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-bold text-emerald-700">End-to-end protected</span>
                                <button type="button" class="grid h-9 w-9 place-items-center rounded-full border border-slate-200 text-slate-500">•••</button>
                            </div>
                        </div>
                    </div>

                    <div ref="messagesPanel" class="flex-1 space-y-5 overflow-y-auto bg-slate-50/60 p-5 sm:p-7">
                        <div v-if="liveMessages.length === 0" class="grid h-full min-h-72 place-items-center text-center">
                            <div>
                                <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-indigo-100 text-2xl">💬</div>
                                <p class="mt-4 font-semibold text-slate-700">No messages in this room yet</p>
                                <p class="mt-1 text-sm text-slate-500">Start a respectful, action-focused conversation.</p>
                            </div>
                        </div>

                        <div
                            v-for="message in liveMessages"
                            :key="message.id"
                            class="flex items-end gap-2"
                            :class="isRightSide(message.sender_role) ? 'justify-end' : 'justify-start'"
                        >
                            <span
                                v-if="!isRightSide(message.sender_role)"
                                class="grid h-8 w-8 flex-none place-items-center rounded-full text-[10px] font-black uppercase"
                                :class="avatarClass(message.sender_role)"
                            >
                                {{ message.sender_role.slice(0, 1) }}
                            </span>
                            <div
                                class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-[68%]"
                                :class="messageBubbleClass(message.sender_role)"
                            >
                                <p class="mb-1 text-[11px] font-bold uppercase tracking-wide opacity-70">{{ roleLabel(message.sender_role) }}</p>
                                <p class="whitespace-pre-wrap text-sm leading-6">{{ message.content }}</p>
                                <p class="mt-2 text-[10px] opacity-60">{{ formatTime(message.sent_at) }}</p>
                            </div>
                            <span
                                v-if="isRightSide(message.sender_role)"
                                class="grid h-8 w-8 flex-none place-items-center rounded-full text-[10px] font-black uppercase"
                                :class="avatarClass(message.sender_role)"
                            >
                                R
                            </span>
                        </div>
                    </div>

                    <form class="border-t border-slate-200 bg-white p-4" @submit.prevent="send">
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                            <span class="grid h-9 w-9 place-items-center rounded-full text-lg text-slate-400">＋</span>
                            <textarea
                                v-model="form.message"
                                rows="1"
                                maxlength="3000"
                                placeholder="Type a message..."
                                class="min-h-10 flex-1 resize-none border-0 bg-transparent py-2 text-sm focus:ring-0"
                                @keydown.enter.exact.prevent="send"
                            />
                            <button
                                type="submit"
                                :disabled="form.processing || form.message.trim().length < 2"
                                class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ form.processing ? 'Sending...' : 'Send' }}
                            </button>
                        </div>
                        <p v-if="form.errors.message" class="mt-2 text-xs font-medium text-red-600">{{ form.errors.message }}</p>
                    </form>
                </section>
            </div>
        </div>
    </component>
</template>
