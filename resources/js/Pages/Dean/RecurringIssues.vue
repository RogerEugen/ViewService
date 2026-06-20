<script setup>
import { Head } from '@inertiajs/vue3';
import DeanLayout from '@/Layouts/DeanLayout.vue';
import RecurringIssuesWorkspace from '@/Components/RecurringIssuesWorkspace.vue';
defineProps({ groups: Array, summary: Object, categories: Array, departments: Array, filters: Object, role: String });
</script>
<template><DeanLayout><Head title="Recurring Issues" /><RecurringIssuesWorkspace v-bind="$props" /></DeanLayout></template>
