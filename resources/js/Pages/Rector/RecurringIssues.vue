<script setup>
import { Head } from '@inertiajs/vue3';
import RectorLayout from '@/Layouts/RectorLayout.vue';
import RecurringIssuesWorkspace from '@/Components/RecurringIssuesWorkspace.vue';
defineProps({ groups: Array, summary: Object, categories: Array, departments: Array, filters: Object, role: String });
</script>
<template><RectorLayout><Head title="Recurring Issues" /><RecurringIssuesWorkspace v-bind="$props" /></RectorLayout></template>
