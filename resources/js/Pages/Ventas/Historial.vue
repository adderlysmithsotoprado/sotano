<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import { invoke } from '@tauri-apps/api/core';

const props = defineProps({
    sales: Array,
    filters: Object,
    total: Number,
});

const search = ref(props.filters?.search ?? '');
const date = ref(props.filters?.date ?? '');

const money = (value) => Number(value || 0).toFixed(2);

const totalSales = computed(() => {
    return props.total ?? (props.sales ?? []).reduce((sum, sale) => {
        return sum + Number(sale.total || 0);
    }, 0);
});

const paymentText = (sale) => {
    if (!sale.payments || sale.payments.length === 0) {
        return 'Sin pago';
    }

    return sale.payments
        .map(payment => `${payment.method.toUpperCase()} S/ ${money(payment.amount)}`)
        .join(' + ');
};

const receivedText = (sale) => {
    return Number(sale.amount_received || 0) > 0
        ? `S/ ${money(sale.amount_received)}`
        : '-';
};

const changeText = (sale) => {
    return Number(sale.change_amount || 0) > 0
        ? `S/ ${money(sale.change_amount)}`
        : '-';
};

const viewSale = (sale) => {
    window.location.href = route('ventas.show', sale.id);
};

const viewTicket = (sale) => {
    window.open(route('ticket.show', sale.id), '_blank');
};

const applyFilters = () => {
    router.get(route('ventas.historial'), {
        search: search.value,
        date: date.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    date.value = '';

    router.get(route('ventas.historial'));
};
const exporting = ref(false);

const downloadExport = async (selectedDate = '') => {
    if (exporting.value) return;

    exporting.value = true;

    try {
        const response = await axios.get(
            route('ventas.historial.export'),
            {
                params: selectedDate
                    ? { date: selectedDate }
                    : {},
                responseType: 'blob',
                headers: {
                    Accept: 'text/csv',
                },
            }
        );

        const disposition =
            response.headers['content-disposition'] ?? '';

        const fileNameMatch = disposition.match(
            /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/
        );

        const fileName = fileNameMatch
            ? fileNameMatch[1].replace(/['"]/g, '')
            : selectedDate
                ? `ventas_${selectedDate}.csv`
                : 'historial_completo_ventas.csv';

        const arrayBuffer = await response.data.arrayBuffer();
        const bytes = Array.from(new Uint8Array(arrayBuffer));

        const savedPath = await invoke('save_csv_file', {
            fileName,
            bytes,
        });

        alert(
            `Archivo exportado correctamente.\n\nGuardado en:\n${savedPath}`
        );
    } catch (error) {
        console.error(error);

        alert(
            typeof error === 'string'
                ? error
                : 'No se pudo exportar el historial.'
        );
    } finally {
        exporting.value = false;
    }
};

const exportSelectedDay = async () => {
    if (!date.value) {
        alert('Selecciona una fecha primero.');
        return;
    }

    await downloadExport(date.value);
};

const exportAllSales = async () => {
    await downloadExport();
};
</script>

<template>
    <Head title="Historial de Ventas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🧾 Historial de Ventas
            </h2>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow p-5">
                <p class="text-sm text-zinc-500">Ventas encontradas</p>
                <p class="text-3xl font-black">{{ sales.length }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow p-5">
                <p class="text-sm text-zinc-500">Total vendido</p>
                <p class="text-3xl font-black text-green-700">
                    S/ {{ money(totalSales) }}
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow p-5">
                <p class="text-sm text-zinc-500">Fecha filtrada</p>
                <p class="text-2xl font-black">
                    {{ date || 'Todas' }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar pedido, mesa, cajero o cliente"
                    class="rounded-lg border-zinc-300"
                />

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
                    Buscar
                </button>

                <button
                    type="button"
                    @click="clearFilters"
                    class="bg-zinc-200 text-zinc-800 rounded-xl font-bold px-5 py-3 hover:bg-zinc-300"
                >
                    Limpiar
                </button>
                        <div class="mt-5 flex flex-col md:flex-row gap-3">
            <button
    type="button"
    @click="exportSelectedDay"
    :disabled="!date || exporting"
    class="flex-1 bg-green-600 hover:bg-green-700 text-white rounded-xl font-black px-5 py-3 disabled:opacity-50 disabled:cursor-not-allowed"
>
    {{
        exporting
            ? 'Exportando...'
            : '📗 Exportar día seleccionado'
    }}
</button>

            <button
    type="button"
    @click="exportAllSales"
    :disabled="exporting"
    class="flex-1 bg-emerald-800 hover:bg-emerald-900 text-white rounded-xl font-black px-5 py-3 disabled:opacity-50 disabled:cursor-not-allowed"
>
    {{
        exporting
            ? 'Exportando...'
            : '📗 Exportar historial completo'
    }}
</button>
        </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-zinc-500">
                            <th class="py-3">Pedido</th>
                            <th>Mesa</th>
                            <th>Cliente</th>
                            <th>Cajero</th>
                            <th>Fecha y hora</th>
                            <th>Pago</th>
                            <th class="text-right">Recibido</th>
                            <th class="text-right">Vuelto</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="sale in sales"
                            :key="sale.id"
                            class="border-b"
                        >
                            <td class="py-3 font-bold">
                                #{{ sale.id }}
                            </td>

                            <td>
                                {{ sale.table?.name ?? 'Sin mesa' }}
                            </td>

                            <td>
                                {{ sale.customer?.name ?? 'Cliente general' }}
                            </td>

                            <td>
                                {{ sale.user?.name ?? 'Sin usuario' }}
                            </td>

                            <td>
                                {{ sale.paid_at_formatted }}
                            </td>

                            <td class="text-xs font-bold max-w-xs">
                                {{ paymentText(sale) }}
                            </td>

                            <td class="text-right font-bold">
                                {{ receivedText(sale) }}
                            </td>

                            <td class="text-right font-bold text-orange-600">
                                {{ changeText(sale) }}
                            </td>

                            <td class="text-right font-bold">
                                S/ {{ money(sale.total) }}
                            </td>

                            <td class="text-right space-x-2 whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="viewSale(sale)"
                                    class="bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-blue-700"
                                >
                                    Ver
                                </button>

                                <button
                                    type="button"
                                    @click="viewTicket(sale)"
                                    class="bg-black text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-zinc-800"
                                >
                                    Ticket
                                </button>
                            </td>
                        </tr>

                        <tr v-if="sales.length === 0">
                            <td colspan="10" class="py-8 text-center text-zinc-500">
                                No hay ventas encontradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
