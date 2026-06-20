<script setup>
import DeanLayout from '@/Layouts/DeanLayout.vue';
import RoleFeedbackInbox from '@/Components/RoleFeedbackInbox.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    feedbacks: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});
</script>

<template>
    <DeanLayout>
        <Head title="Faculty Feedback Inbox" />
        <RoleFeedbackInbox :feedbacks="feedbacks" :stats="stats" role="dean" />
    </DeanLayout>
</template>
