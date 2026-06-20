<script setup>
import { Head } from '@inertiajs/vue3';
import HodLayout from '@/Layouts/HodLayout.vue';
import RecurringIssuesWorkspace from '@/Components/RecurringIssuesWorkspace.vue';
defineProps({ groups: Array, summary: Object, categories: Array, departments: Array, filters: Object, role: String });
</script>
<template><HodLayout><Head title="Recurring Issues" /><RecurringIssuesWorkspace v-bind="$props" /></HodLayout></template>
