<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Ventas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                Ventas
            </h2>
        </template>

        <div class="bg-white rounded-2xl shadow p-6">
            <h1 class="text-xl font-semibold">
                Módulo de Ventas
            </h1>
            <p class="text-zinc-500 mt-2">
                Aquí construiremos el POS principal.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
