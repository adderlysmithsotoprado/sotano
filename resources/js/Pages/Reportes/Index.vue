<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    from: String,
    to: String,
    sales: Number,
    cost: Number,
    profit: Number,
    margin: Number,
    paidOrdersCount: Number,
    averageTicket: Number,
    topProducts: Array,
    topProfitableProducts: Array,
    recentSales: Array,
});

const from = ref(props.from);
const to = ref(props.to);

const money = (value) => Number(value || 0).toFixed(2);

const search = () => {
    router.get(route('reportes'), {
        from: from.value,
        to: to.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Reportes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                📊 Reporte de Rentabilidad
            </h2>
        </template>

        <div class="mb-6 bg-white rounded-2xl shadow p-6 flex flex-col md:flex-row md:items-end gap-4">
            <div>
                <label class="block text-sm font-bold mb-2">Desde</label>
                <input v-model="from" type="date" class="rounded-lg border-zinc-300" />
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">Hasta</label>
                <input v-model="to" type="date" class="rounded-lg border-zinc-300" />
            </div>

            <button
                @click="search"
                class="bg-black text-white px-6 py-3 rounded-xl font-bold hover:bg-zinc-800"
            >
                Buscar
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-sm uppercase text-zinc-500">Ventas</h3>
                <p class="text-3xl font-black text-green-600 mt-2">
                    S/ {{ money(sales) }}
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-sm uppercase text-zinc-500">Costo</h3>
                <p class="text-3xl font-black text-red-600 mt-2">
                    S/ {{ money(cost) }}
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-sm uppercase text-zinc-500">Utilidad</h3>
                <p class="text-3xl font-black text-blue-600 mt-2">
                    S/ {{ money(profit) }}
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-sm uppercase text-zinc-500">Margen</h3>
                <p class="text-3xl font-black text-purple-600 mt-2">
                    {{ money(margin) }}%
                </p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">

    <!-- MÁS VENDIDOS -->
    <div class="bg-white rounded-2xl shadow p-6">

        <h3 class="text-xl font-bold mb-4">
            🔥 Productos más vendidos
        </h3>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-zinc-500">
                    <th class="py-3 text-left">Producto</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">Ventas</th>
                </tr>
            </thead>

            <tbody>

                <tr
                    v-for="product in topProducts"
                    :key="product.product_name"
                    class="border-b"
                >
                    <td class="py-3 font-bold">
                        {{ product.product_name }}
                    </td>

                    <td class="text-right">
                        {{ product.qty }}
                    </td>

                    <td class="text-right">
                        S/ {{ money(product.sales) }}
                    </td>
                </tr>

            </tbody>
        </table>

    </div>

    <!-- MÁS RENTABLES -->

    <div class="bg-white rounded-2xl shadow p-6">

        <h3 class="text-xl font-bold mb-4">
            💰 Productos más rentables
        </h3>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-zinc-500">
                    <th class="py-3 text-left">Producto</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">Utilidad</th>
                </tr>
            </thead>

            <tbody>

                <tr
                    v-for="product in topProfitableProducts"
                    :key="product.product_name"
                    class="border-b"
                >
                    <td class="py-3 font-bold">
                        {{ product.product_name }}
                    </td>

                    <td class="text-right">
                        {{ product.qty }}
                    </td>

                    <td class="text-right font-bold text-green-600">
                        S/ {{ money(product.profit) }}
                    </td>
                </tr>

            </tbody>
        </table>

    </div>

</div>
    </AuthenticatedLayout>
</template>
