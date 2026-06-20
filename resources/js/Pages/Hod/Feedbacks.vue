<script setup>
import HodLayout from '@/Layouts/HodLayout.vue';
import RoleFeedbackInbox from '@/Components/RoleFeedbackInbox.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    feedbacks: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});
</script>

<template>
    <HodLayout>
        <Head title="Department Feedback Inbox" />
        <RoleFeedbackInbox :feedbacks="feedbacks" :stats="stats" role="hod" />
    </HodLayout>
</template>
