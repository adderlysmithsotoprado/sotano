<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    logs: Array,
    filters: Object,
    modules: Array,
});

const search = ref(props.filters?.search ?? '');
const module = ref(props.filters?.module ?? '');
const date = ref(props.filters?.date ?? '');

const applyFilters = () => {
    router.get(route('auditoria'), {
        search: search.value,
        module: module.value,
        date: date.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    module.value = '';
    date.value = '';

    router.get(route('auditoria'));
};
</script>

<template>
    <Head title="Auditoría" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🛡️ Auditoría del Sistema
            </h2>
        </template>

        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar usuario, acción o detalle"
                    class="rounded-lg border-zinc-300"
                />

                <select
                    v-model="module"
                    class="rounded-lg border-zinc-300"
                >
                    <option value="">Todos los módulos</option>

                    <option
                        v-for="item in modules"
                        :key="item"
                        :value="item"
                    >
                        {{ item }}
                    </option>
                </select>

                <input
                    v-model="date"
                    type="date"
                    class="rounded-lg border-zinc-300"
                />

                <button
                    type="button"
                    @click="applyFilters"
                    class="bg-black text-white rounded-xl font-bold px-5 py-3 hover:bg-zinc-800"
                >
                    Filtrar
                </button>

                <button
                    type="button"
                    @click="clearFilters"
                    class="bg-zinc-200 text-zinc-800 rounded-xl font-bold px-5 py-3 hover:bg-zinc-300"
                >
                    Limpiar
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold">
                    Últimas acciones registradas
                </h3>

                <span class="text-sm text-zinc-500">
                    {{ logs.length }} registro(s)
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-zinc-500">
                            <th class="py-3">Fecha</th>
                            <th>Usuario</th>
                            <th>Módulo</th>
                            <th>Acción</th>
                            <th>Detalle</th>
                            <th>IP</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="log in logs"
                            :key="log.id"
                            class="border-b"
                        >
                            <td class="py-3 whitespace-nowrap">
                                {{ log.created_at_formatted }}
                            </td>

                            <td>
                                {{ log.user?.name ?? 'Sistema' }}
                            </td>

                            <td>
                                <span class="px-2 py-1 rounded-lg text-xs font-bold bg-zinc-100 text-zinc-700">
                                    {{ log.module }}
                                </span>
                            </td>

                            <td class="font-bold">
                                {{ log.action }}
                            </td>

                            <td>
                                {{ log.description ?? '-' }}
                            </td>

                            <td class="text-zinc-500">
                                {{ log.ip_address ?? '-' }}
                            </td>
                        </tr>

                        <tr v-if="logs.length === 0">
                            <td colspan="6" class="py-8 text-center text-zinc-500">
                                No se encontraron registros.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
