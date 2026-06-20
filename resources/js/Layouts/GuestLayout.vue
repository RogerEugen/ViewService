<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div class="flex flex-col items-center">
            <Link href="/" class="rounded-full focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-4">
                <img
                    src="/images/nit-logo.jpg"
                    alt="National Institute of Transport logo"
                    class="h-28 w-28 rounded-full object-contain sm:h-32 sm:w-32"
                />
            </Link>
            <p class="mt-3 text-center text-sm font-bold text-slate-900">
                National Institute of Transport
            </p>
            <p class="mt-0.5 text-center text-xs text-slate-500">
                Anonymous Feedback System
            </p>
        </div>

        <div
            class="mt-5 w-full overflow-hidden bg-white px-6 py-5 shadow-md sm:max-w-md sm:rounded-xl"
        >
            <slot />
        </div>
    </div>
</template>
