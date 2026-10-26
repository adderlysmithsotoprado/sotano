<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <div
        class="min-h-screen bg-cover bg-center relative flex items-center justify-center px-6"
        style="background-image: url('/images/sotano-login-bg.png');"
    >
        <div class="absolute inset-0 bg-black/70 backdrop-blur-[2px]"></div>

        <div class="relative z-10 w-full max-w-md">
            <div class="bg-white/10 border border-white/20 shadow-2xl rounded-3xl p-8 backdrop-blur-xl text-white animate-[fadeIn_.4s_ease-out]">
                <div class="text-center mb-8">
                    <img
                        src="/images/logo.png"
                        alt="SOTANO POS"
                        class="w-36 mx-auto mb-4 object-contain"
                    />

                    <h1 class="text-3xl font-black tracking-wide">
                        SOTANO
                    </h1>

                    <p class="text-sm text-zinc-300 mt-2">
                        Sistema Integral de Gestión para Restobar
                    </p>
                </div>

                <div v-if="status" class="mb-4 text-sm text-green-300 font-bold text-center">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold mb-2">
                            Correo electrónico
                        </label>

                        <input
                            v-model="form.email"
                            type="email"
                            autofocus
                            autocomplete="username"
                            placeholder="ingrese su correo"
                            class="w-full rounded-xl border border-white/20 bg-white/90 text-black px-4 py-3 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                        />

                        <p v-if="form.errors.email" class="text-red-300 text-sm mt-2">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold mb-2">
                            Contraseña
                        </label>

                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Ingrese su contraseña"
                            class="w-full rounded-xl border border-white/20 bg-white/90 text-black px-4 py-3 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                        />

                        <p v-if="form.errors.password" class="text-red-300 text-sm mt-2">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-zinc-200">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded border-zinc-300 text-yellow-600 focus:ring-yellow-500"
                        />
                        Recordar sesión
                    </label>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-black hover:bg-yellow-700 text-white rounded-xl py-4 font-black tracking-wide transition disabled:opacity-50"
                    >
                        INICIAR SESIÓN
                    </button>
                </form>

                <div class="mt-8 text-center text-xs text-zinc-300">
                    <p>© 2026 SOTANO POS</p>
                    <p>Desarrollado por Ing. Adderly Soto Prado</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(.96);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
