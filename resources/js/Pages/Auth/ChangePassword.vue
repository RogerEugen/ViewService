<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    current_password:          '',
    new_password:              '',
    new_password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset(
            'current_password',
            'new_password',
            'new_password_confirmation'
        ),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Change Password" />

        <!-- Info banner -->
        <div class="mb-4 rounded-md bg-yellow-50 border border-yellow-200 p-4">
            <div class="flex items-start gap-3">
                <svg
                    class="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>
                <div>
                    <p class="text-sm font-medium text-yellow-800">
                        Password change required
                    </p>
                    <p class="mt-1 text-sm text-yellow-700">
                        This is your first login. You must set a new password
                        before accessing the system.
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit">

            <!-- Current password (default = lastname) -->
            <div>
                <InputLabel
                    for="current_password"
                    value="Current Password"
                />
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
                <InputError
                    class="mt-2"
                    :message="form.errors.current_password"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Your default password is your last name (e.g.
                    <span class="font-medium">Mwangi</span>).
                </p>
            </div>

            <!-- New password -->
            <div class="mt-4">
                <InputLabel
                    for="new_password"
                    value="New Password"
                />
                <TextInput
                    id="new_password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.new_password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimum 8 characters"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.new_password"
                />

                <!-- Password strength hints -->
                <ul class="mt-2 space-y-1">
                    <li
                        class="flex items-center gap-1.5 text-xs"
                        :class="form.new_password.length >= 8
                            ? 'text-green-600'
                            : 'text-gray-400'"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        At least 8 characters
                    </li>
                    <li
                        class="flex items-center gap-1.5 text-xs"
                        :class="/[A-Z]/.test(form.new_password)
                            ? 'text-green-600'
                            : 'text-gray-400'"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        At least one uppercase letter
                    </li>
                    <li
                        class="flex items-center gap-1.5 text-xs"
                        :class="/[0-9]/.test(form.new_password)
                            ? 'text-green-600'
                            : 'text-gray-400'"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        At least one number
                    </li>
                </ul>
            </div>

            <!-- Confirm new password -->
            <div class="mt-4">
                <InputLabel
                    for="new_password_confirmation"
                    value="Confirm New Password"
                />
                <TextInput
                    id="new_password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.new_password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repeat your new password"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.new_password_confirmation"
                />

                <!-- Live match indicator -->
                <p
                    v-if="form.new_password_confirmation.length > 0"
                    class="mt-1 text-xs"
                    :class="form.new_password === form.new_password_confirmation
                        ? 'text-green-600'
                        : 'text-red-500'"
                >
                    {{
                        form.new_password === form.new_password_confirmation
                            ? 'Passwords match'
                            : 'Passwords do not match'
                    }}
                </p>
            </div>

            <!-- Submit -->
            <div class="mt-6 flex items-center justify-end">
                <PrimaryButton
                    class="ms-4 w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <svg
                        v-if="form.processing"
                        class="me-2 h-4 w-4 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12" cy="12" r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v8z"
                        />
                    </svg>
                    {{ form.processing ? 'Saving...' : 'Set New Password' }}
                </PrimaryButton>
            </div>

        </form>
    </GuestLayout>
</template>