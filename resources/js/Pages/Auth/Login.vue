<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    tipo_usuario: 'email',
    email: '',
    registro: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar Sesión" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <!-- Selector de tipo de usuario -->
            <div>
                <InputLabel for="tipo_usuario" value="Iniciar sesión como" />
                <select
                    id="tipo_usuario"
                    v-model="form.tipo_usuario"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="email">Correo Electrónico</option>
                    <option value="decano">Decano (Registro)</option>
                </select>
            </div>

            <!-- Campo Email (para usuarios normales) -->
            <div class="mt-4" v-if="form.tipo_usuario === 'email'">
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    :required="form.tipo_usuario === 'email'"
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Campo Registro (para Decano) -->
            <div class="mt-4" v-if="form.tipo_usuario === 'decano'">
                <InputLabel for="registro" value="Registro de Decano" />
                <TextInput
                    id="registro"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.registro"
                    :required="form.tipo_usuario === 'decano'"
                    autofocus
                    autocomplete="username"
                    placeholder="Ej: DEC001"
                />
                <InputError class="mt-2" :message="form.errors.registro" />
            </div>

            <!-- Contraseña -->
            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Recordarme</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword && form.tipo_usuario === 'email'"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    ¿Olvidaste tu contraseña?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Ingresar
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
