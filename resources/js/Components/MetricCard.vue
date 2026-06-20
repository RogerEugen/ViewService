<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: 0 },
    icon: { type: [Object, Function], required: true },
    tone: { type: String, default: 'blue' },
});

const tones = {
    blue: 'bg-blue-50 text-blue-600',
    indigo: 'bg-indigo-50 text-indigo-600',
    amber: 'bg-amber-50 text-amber-600',
    orange: 'bg-orange-50 text-orange-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    rose: 'bg-rose-50 text-rose-600',
    violet: 'bg-violet-50 text-violet-600',
    teal: 'bg-teal-50 text-teal-600',
    slate: 'bg-slate-100 text-slate-600',
};
</script>

<template>
    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl" :class="tones[tone] ?? tones.blue">
                <component :is="icon" class="h-5 w-5" />
            </span>
            <span class="text-2xl font-black text-slate-950">{{ value ?? 0 }}</span>
        </div>
        <p class="mt-4 text-xs font-semibold text-slate-500">{{ label }}</p>
    </article>
</template>
