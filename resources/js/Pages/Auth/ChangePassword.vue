<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    userName: { type: String, default: '' },
    userRole: { type: String, default: '' },
});

const form = useForm({
    current_password:          '',
    new_password:              '',
    new_password_confirmation: '',
});

const cancelForm = useForm({});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset(
            'current_password',
            'new_password',
            'new_password_confirmation'
        ),
    });
};

const cancel = () => {
    cancelForm.post(route('password.update'), {
        data: { cancel: true },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Change Password" />

        <!-- Warning banner -->
        <div class="mb-5 rounded-lg bg-yellow-50 border border-yellow-200 p-4">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-yellow-800">Password change required</p>
                    <p class="mt-0.5 text-sm text-yellow-700">
                        This is your first login
                        <span v-if="userName"> — welcome, <strong>{{ userName }}</strong></span>.
                        You must set a new password before accessing the system.
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">

            <!-- Current password -->
            <div>
                <InputLabel for="current_password" value="Current Password"/>
                <TextInput
                    id="current_password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.current_password"
                    required
                    autofocus
                    autocomplete="current-password"
                    placeholder="Your default password (last name)"
                />
                <InputError class="mt-1" :message="form.errors.current_password"/>
                <p class="mt-1 text-xs text-gray-500">
                    Your default password is your last name, e.g.
                    <span class="font-medium">Mwangi</span>.
                </p>
            </div>

            <!-- New password -->
            <div>
                <InputLabel for="new_password" value="New Password"/>
                <TextInput
                    id="new_password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.new_password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimum 8 characters"
                />
                <InputError class="mt-1" :message="form.errors.new_password"/>

                <!-- Strength hints -->
                <ul class="mt-2 space-y-1">
                    <li class="flex items-center gap-1.5 text-xs" :class="form.new_password.length >= 8 ? 'text-green-600' : 'text-gray-400'">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        At least 8 characters
                    </li>
                    <li class="flex items-center gap-1.5 text-xs" :class="/[A-Z]/.test(form.new_password) ? 'text-green-600' : 'text-gray-400'">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        At least one uppercase letter
                    </li>
                    <li class="flex items-center gap-1.5 text-xs" :class="/[0-9]/.test(form.new_password) ? 'text-green-600' : 'text-gray-400'">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        At least one number
                    </li>
                </ul>
            </div>

            <!-- Confirm password -->
            <div>
                <InputLabel for="new_password_confirmation" value="Confirm New Password"/>
                <TextInput
                    id="new_password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.new_password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repeat your new password"
                />
                <InputError class="mt-1" :message="form.errors.new_password_confirmation"/>
                <p
                    v-if="form.new_password_confirmation.length > 0"
                    class="mt-1 text-xs"
                    :class="form.new_password === form.new_password_confirmation ? 'text-green-600' : 'text-red-500'"
                >
                    {{ form.new_password === form.new_password_confirmation ? 'Passwords match ✓' : 'Passwords do not match' }}
                </p>
            </div>

            <!-- Buttons -->
            <div class="mt-6 flex items-center gap-3">
                <!-- Cancel -->
                <button
                    type="button"
                    @click="cancel"
                    :disabled="cancelForm.processing"
                    class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                    Cancel
                </button>

                <!-- Submit -->
                <PrimaryButton
                    class="flex-1 justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing || !form.new_password || form.new_password !== form.new_password_confirmation"
                >
                    <svg v-if="form.processing" class="me-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    {{ form.processing ? 'Saving...' : 'Set New Password' }}
                </PrimaryButton>
            </div>

        </form>
    </GuestLayout>
</template>