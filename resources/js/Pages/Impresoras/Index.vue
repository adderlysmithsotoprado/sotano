<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { invoke } from '@tauri-apps/api/core';
import { ref } from 'vue';

const props = defineProps({
    settings: Object,
});

const printers = ref([]);
const loading = ref(false);

const form = useForm({
    kitchen_printer: props.settings?.kitchen_printer ?? '',
    bar_printer: props.settings?.bar_printer ?? '',
    ticket_printer: props.settings?.ticket_printer ?? '',
});

const detectPrinters = async () => {
    loading.value = true;

    try {
        printers.value = await invoke('list_printers');
    } catch (e) {
        console.error(e);
        alert('No se pudieron detectar las impresoras.');
    }

    loading.value = false;
};

const save = () => {
    form.post(route('impresoras.update'), {
        preserveScroll: true,
    });
};

detectPrinters();

const testPrint = async (printer) => {
    if (!printer) {
        alert('Selecciona una impresora primero.');
        return;
    }

    try {
        await invoke('test_print', {
            printerName: printer,
        });

        alert('Prueba enviada a la impresora.');
    } catch (error) {
        console.error(error);
        alert('No se pudo enviar la prueba.');
    }
};
</script>



<template>
    <Head title="Impresoras" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🖨️ Administración de Impresoras
            </h2>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">

            <div class="bg-white rounded-2xl shadow p-8">

                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-black">
                            Impresoras instaladas
                        </h3>

                        <p class="text-zinc-500 text-sm mt-1">
                            Selecciona qué impresora utilizará cada área del sistema.
                        </p>
                    </div>

                    <button
                        @click="detectPrinters"
                        :disabled="loading"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold"
                    >
                        {{ loading ? 'Buscando...' : '🔍 Detectar impresoras' }}
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <div>
                        <label class="block font-bold mb-2">
                            🍳 Cocina
                        </label>

                        <select
                            v-model="form.kitchen_printer"
                            class="w-full rounded-xl border-zinc-300"
                        >
                            <option value="">
                                Seleccionar...
                            </option>

                            <option
                                v-for="printer in printers"
                                :key="printer"
                                :value="printer"
                            >
                                {{ printer }}
                            </option>
                        </select>
                        <button
                        type="button"
                        @click="testPrint(form.kitchen_printer)"
                        class="mt-2 w-full bg-zinc-200 hover:bg-zinc-300 text-zinc-800 rounded-lg py-2 text-sm font-bold"
                        >
                        Probar cocina
                        </button>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">
                            🍺 Bar
                        </label>

                        <select
                            v-model="form.bar_printer"
                            class="w-full rounded-xl border-zinc-300"
                        >
                            <option value="">
                                Seleccionar...
                            </option>

                            <option
                                v-for="printer in printers"
                                :key="printer + '-bar'"
                                :value="printer"
                            >
                                {{ printer }}
                            </option>
                        </select>
                        <button
                        type="button"
                        @click="testPrint(form.bar_printer)"
                        class="mt-2 w-full bg-zinc-200 hover:bg-zinc-300 text-zinc-800 rounded-lg py-2 text-sm font-bold"
                        >
                        Probar bar
                        </button>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">
                            🧾 Ticket de Caja
                        </label>

                        <select
                            v-model="form.ticket_printer"
                            class="w-full rounded-xl border-zinc-300"
                        >
                            <option value="">
                                Seleccionar...
                            </option>

                            <option
                                v-for="printer in printers"
                                :key="printer + '-ticket'"
                                :value="printer"
                            >
                                {{ printer }}
                            </option>
                        </select>
                        <button
                        type="button"
                        @click="testPrint(form.ticket_printer)"
                        class="mt-2 w-full bg-zinc-200 hover:bg-zinc-300 text-zinc-800 rounded-lg py-2 text-sm font-bold"
                        >
                        Probar ticket
                        </button>
                    </div>

                </div>

                <div
                    class="mt-8 bg-zinc-50 rounded-xl p-4 border"
                >
                    <h4 class="font-bold mb-3">
                        Impresoras detectadas
                    </h4>

                    <div
                        v-if="printers.length"
                        class="space-y-2"
                    >
                        <div
                            v-for="printer in printers"
                            :key="printer"
                            class="flex items-center justify-between bg-white rounded-lg border p-3"
                        >
                            <span>
                                🖨️ {{ printer }}
                            </span>

                            <span class="text-green-600 font-bold">
                                Disponible
                            </span>
                        </div>
                    </div>

                    <div
                        v-else
                        class="text-zinc-500"
                    >
                        No se encontraron impresoras.
                    </div>
                </div>

                <div class="mt-8 flex justify-end">

                    <button
                        @click="save"
                        :disabled="form.processing"
                        class="bg-black hover:bg-zinc-800 text-white px-8 py-3 rounded-xl font-bold"
                    >
                        💾 Guardar Configuración
                    </button>

                </div>

            </div>

        </div>

    </AuthenticatedLayout>
</template>
