<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    backups: Array,
    backup_path: String,
});

const createBackup = () => {
    if (confirm('¿Crear un respaldo de la base de datos?')) {
        router.post(route('backup'), {}, {
            preserveScroll: true,
        });
    }
};
const restoreBackup = (backup) => {
    if (confirm(`¿Restaurar este respaldo?\n\n${backup.name}\n\nSe reemplazará la base de datos actual.`)) {
        router.post(route('respaldos.restore', backup.name), {}, {
            preserveScroll: true,
        });
    }
};
const deleteBackup = (backup) => {
    if (confirm(`¿Eliminar este respaldo?\n\n${backup.name}`)) {
        router.delete(route('respaldos.destroy', backup.name), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Respaldos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                💾 Respaldos
            </h2>
        </template>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-black mb-4">
                    Base de datos
                </h3>

                <p class="text-sm text-zinc-500 mb-4">
                    Ruta de respaldo:
                </p>

                <div class="bg-zinc-100 rounded-xl p-3 text-xs break-all font-mono">
                    {{ backup_path }}
                </div>

                <button
                    @click="createBackup"
                    class="mt-6 w-full bg-black text-white rounded-xl py-4 font-black hover:bg-zinc-800"
                >
                    💾 Crear respaldo
                </button>
            </div>

            <div class="xl:col-span-2 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-black mb-4">
                    Respaldos creados
                </h3>

                <div class="space-y-3">
                    <div
    v-for="backup in backups"
    :key="backup.name"
    class="border rounded-2xl p-4 flex justify-between gap-4"
>
    <div>
        <p class="font-black">
            {{ backup.name }}
        </p>

        <p class="text-sm text-zinc-500">
            {{ backup.date }} | {{ backup.size }} MB
        </p>
    </div>

    <div class="flex items-center gap-2">
        <button
            @click="restoreBackup(backup)"
            class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-xl text-sm font-bold"
        >
            ♻ Restaurar
        </button>

        <button
            @click="deleteBackup(backup)"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-sm font-bold"
        >
            🗑 Eliminar
        </button>
    </div>
</div>

                    <div
                        v-if="backups.length === 0"
                        class="text-center text-zinc-500 py-10"
                    >
                        Aún no hay respaldos.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
