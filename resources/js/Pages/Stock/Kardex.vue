<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { invoke } from '@tauri-apps/api/core';


const props = defineProps({
    movements: Array,
    products: Array,
    filters: Object,
});

const productId = ref(props.filters?.product_id ?? '');

const reportType = ref(props.filters?.report_type ?? 'kardex');
const from = ref(props.filters?.from ?? '');
const to = ref(props.filters?.to ?? '');
const exporting = ref(false);

const reportOptions = [
    { value: 'kardex', label: 'Kardex completo' },
    { value: 'ventas', label: 'Ventas' },
    {
        value: 'eliminados_cocina',
        label: 'Pedidos eliminados - Cocina',
    },
    {
        value: 'eliminados_bar',
        label: 'Pedidos eliminados - Bar',
    },
    {
        value: 'eliminados_todos',
        label: 'Pedidos eliminados - Cocina y Bar',
    },
    { value: 'merma', label: 'Merma' },
    { value: 'ingresos', label: 'Ingreso de productos' },
    { value: 'promociones', label: 'Promociones' },
];

const adjustForm = useForm({
    product_id: '',
    type: 'entrada',
    quantity: 1,
    reason: '',
});

const submitAdjust = () => {
    adjustForm.post(route('kardex.adjust'), {
        preserveScroll: true,
        onSuccess: () => {
            adjustForm.reset();
            adjustForm.type = 'entrada';
            adjustForm.quantity = 1;
        },
    });
};

const money = (value) => Number(value || 0).toFixed(2);

const applyFilter = () => {
    router.get(
        route('kardex'),
        {
            product_id: productId.value,
            report_type: reportType.value,
            from: from.value,
            to: to.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
};

const clearFilter = () => {
    productId.value = '';
    reportType.value = 'kardex';
    from.value = '';
    to.value = '';

    router.get(route('kardex'));
};

const exportCsv = async () => {
    if (exporting.value) {
        return;
    }

    if (from.value && to.value && from.value > to.value) {
        alert('La fecha inicial no puede ser mayor que la fecha final.');
        return;
    }

    exporting.value = true;

    try {
        const response = await axios.get(
            route('kardex.export'),
            {
                params: {
                    report_type: reportType.value,
                    product_id: productId.value || undefined,
                    from: from.value || undefined,
                    to: to.value || undefined,
                },
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
            : `${reportType.value}_${Date.now()}.csv`;

        const arrayBuffer = await response.data.arrayBuffer();
        const bytes = Array.from(new Uint8Array(arrayBuffer));

        const savedPath = await invoke('save_csv_file', {
            fileName,
            bytes,
        });

        alert(
            `Reporte exportado correctamente.\n\nGuardado en:\n${savedPath}`
        );
    } catch (error) {
        console.error(error);

        alert(
            typeof error === 'string'
                ? error
                : 'No se pudo exportar el reporte.'
        );
    } finally {
        exporting.value = false;
    }
};

const typeClass = (type) => {
    if (type === 'venta') return 'bg-red-100 text-red-700';
    if (type === 'cancelacion') return 'bg-green-100 text-green-700';
    if (type === 'devolucion') return 'bg-blue-100 text-blue-700';
    if (type === 'entrada') return 'bg-green-100 text-green-700';
    if (type === 'ajuste') return 'bg-yellow-100 text-yellow-700';
    if (type === 'merma') return 'bg-orange-100 text-orange-700';

    return 'bg-zinc-100 text-zinc-700';
};
</script>

<template>
    <Head title="Kardex de Inventario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                📦 Kardex de Inventario
            </h2>
        </template>

        <div class="bg-white rounded-2xl shadow p-6 mb-6">
    <h3 class="text-xl font-black mb-5">
        📊 Reportes de movimientos
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
        <div>
            <label class="block text-xs font-black uppercase text-zinc-500 mb-2">
                Tipo de reporte
            </label>

            <select
                v-model="reportType"
                class="w-full rounded-xl border-zinc-300"
            >
                <option
                    v-for="option in reportOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-black uppercase text-zinc-500 mb-2">
                Producto
            </label>

            <select
                v-model="productId"
                class="w-full rounded-xl border-zinc-300"
            >
                <option value="">
                    Todos los productos
                </option>

                <option
                    v-for="product in products"
                    :key="product.id"
                    :value="product.id"
                >
                    {{ product.name }} - Stock: {{ product.stock }}
                </option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-black uppercase text-zinc-500 mb-2">
                Desde
            </label>

            <input
                v-model="from"
                type="datetime-local"
                class="w-full rounded-xl border-zinc-300"
            />
        </div>

        <div>
            <label class="block text-xs font-black uppercase text-zinc-500 mb-2">
                Hasta
            </label>

            <input
                v-model="to"
                type="datetime-local"
                class="w-full rounded-xl border-zinc-300"
            />
        </div>

        <div class="flex items-end">
            <button
                type="button"
                @click="applyFilter"
                class="w-full bg-black text-white rounded-xl font-black px-5 py-3 hover:bg-zinc-800"
            >
                🔍 Buscar
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-col md:flex-row gap-3">
        <button
            type="button"
            @click="clearFilter"
            class="flex-1 bg-zinc-200 text-zinc-800 rounded-xl font-black px-5 py-3 hover:bg-zinc-300"
        >
            Limpiar filtros
        </button>

        <button
            type="button"
            @click="exportCsv"
            :disabled="exporting"
            class="flex-1 bg-green-700 text-white rounded-xl font-black px-5 py-3 hover:bg-green-800 disabled:opacity-50"
        >
            {{
                exporting
                    ? 'Exportando...'
                    : '📗 Exportar CSV / Excel'
            }}
        </button>

        <button
            type="button"
            disabled
            title="Se implementará en el siguiente paso"
            class="flex-1 bg-blue-700 text-white rounded-xl font-black px-5 py-3 opacity-50 cursor-not-allowed"
        >
            🖨 Imprimir reporte
        </button>
    </div>
</div>


        <div class="bg-white rounded-2xl shadow p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-zinc-500">
                            <th class="py-3">Fecha</th>
                            <th>Producto</th>
                            <th>Usuario</th>
                            <th>Tipo</th>
                            <th class="text-right">Cantidad</th>
                            <th class="text-right">Antes</th>
                            <th class="text-right">Después</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="movement in movements"
                            :key="movement.id"
                            class="border-b"
                        >
                            <td class="py-3">
                                {{ movement.created_at_formatted }}
                            </td>

                            <td class="font-bold">
                                {{ movement.product?.name ?? 'Producto eliminado' }}
                            </td>

                            <td>
                                {{ movement.user?.name ?? 'Sistema' }}
                            </td>

                            <td>
                                <span
                                    class="px-2 py-1 rounded-lg text-xs font-bold uppercase"
                                    :class="typeClass(movement.type)"
                                >
                                    {{ movement.type }}
                                </span>
                            </td>

                            <td
                                class="text-right font-black"
                                :class="movement.quantity < 0 ? 'text-red-600' : 'text-green-600'"
                            >
                                {{ movement.quantity }}
                            </td>

                            <td class="text-right">
                                {{ movement.stock_before }}
                            </td>

                            <td class="text-right font-bold">
                                {{ movement.stock_after }}
                            </td>

                            <td>
                                {{ movement.reason ?? '-' }}
                            </td>
                        </tr>

                        <tr v-if="movements.length === 0">
                            <td colspan="8" class="py-8 text-center text-zinc-500">
                                No hay movimientos de stock registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
