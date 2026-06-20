<script setup>
import RectorLayout from '@/Layouts/RectorLayout.vue';
import RoleFeedbackInbox from '@/Components/RoleFeedbackInbox.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    feedbacks: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});
</script>

<template>
    <RectorLayout>
        <Head title="Campus Feedback Inbox" />
        <RoleFeedbackInbox :feedbacks="feedbacks" :stats="stats" role="rector" />
    </RectorLayout>
</template>
