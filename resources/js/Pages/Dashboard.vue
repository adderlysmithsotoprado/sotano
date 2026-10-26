<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    todaySales: Number,
    todayKitchenSales: Number,
    todayBarSales: Number,
    todayProfit: Number,
    occupiedTables: Number,
    freeTables: Number,
    productsCount: Number,
    lowStockProducts: Array,
    cashRegister: Object,
});

const page = usePage();
const role = computed(() => page.props.auth?.user?.role ?? '');

const canSeeSales = computed(() => ['admin', 'cajero'].includes(role.value));
const canSeeStock = computed(() => ['admin', 'cajero'].includes(role.value));
const canSeeCash = computed(() => ['admin', 'cajero'].includes(role.value));

const quickLinks = computed(() => {
    const links = [
        { label: '🍽 Mesas', route: 'mesas', color: 'bg-blue-500 hover:bg-blue-600', roles: ['admin', 'cajero', 'mozo'] },
        { label: '📦 Productos', route: 'productos', color: 'bg-orange-500 hover:bg-orange-600', roles: ['admin', 'cajero'] },
        { label: '👨‍🍳 Comandas', route: 'comandas', color: 'bg-zinc-900 hover:bg-black', roles: ['admin', 'cocina', 'bar'] },
        { label: '💰 Caja', route: 'caja', color: 'bg-red-500 hover:bg-red-600', roles: ['admin', 'cajero'] },
        { label: '📊 Reportes', route: 'reportes', color: 'bg-gray-700 hover:bg-gray-800', roles: ['admin'] },
        { label: '⚙️ Configuración', route: 'configuracion', color: 'bg-purple-500 hover:bg-purple-600', roles: ['admin'] },
    ];

    return links.filter(link => link.roles.includes(role.value));
});
</script>

<template>
    <Head title="SOTANO POS" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🍷 SOTANO POS
            </h2>
        </template>

        <div
            v-if="role === 'cocina' || role === 'bar'"
            class="bg-white rounded-2xl shadow p-8 text-center"
        >
            <h3 class="text-2xl font-black mb-3">
                Panel de Comandas
            </h3>

            <p class="text-zinc-500 mb-6">
                Revisa los pedidos enviados para preparación.
            </p>

            <Link
                :href="route('comandas')"
                class="inline-block bg-black text-white rounded-xl px-8 py-4 font-bold"
            >
                Ir a Comandas
            </Link>
        </div>

        <div
            v-else-if="role === 'mozo'"
            class="bg-white rounded-2xl shadow p-8 text-center"
        >
            <h3 class="text-2xl font-black mb-3">
                Panel de Mesas
            </h3>

            <p class="text-zinc-500 mb-6">
                Selecciona una mesa para tomar o continuar un pedido.
            </p>

            <Link
                :href="route('mesas')"
                class="inline-block bg-blue-600 text-white rounded-xl px-8 py-4 font-bold"
            >
                Ir a Mesas
            </Link>
        </div>

        <div v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6">
               <div
    v-if="canSeeSales"
    class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500"
>
    <h3 class="text-gray-500 text-sm font-black uppercase">
        Ventas del turno
    </h3>

    <div class="mt-4 space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-sm font-bold text-orange-600">
                🍳 Cocina
            </span>

            <span class="font-black text-zinc-800">
                S/ {{ Number(todayKitchenSales || 0).toFixed(2) }}
            </span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-sm font-bold text-blue-600">
                🍸 Bar
            </span>

            <span class="font-black text-zinc-800">
                S/ {{ Number(todayBarSales || 0).toFixed(2) }}
            </span>
        </div>

        <div class="border-t pt-3 flex items-center justify-between">
            <span class="text-sm font-black text-green-700 uppercase">
                Total
            </span>

            <span class="text-2xl font-black text-green-600">
                S/ {{ Number(todaySales || 0).toFixed(2) }}
            </span>
        </div>
    </div>
</div>

                <div
                v-if="canSeeSales"
                class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-emerald-500"
                >
                <h3 class="text-gray-500 text-sm uppercase">
                Utilidad del turno
                </h3>

                <p class="text-3xl font-bold text-emerald-600 mt-2">
                 S/ {{ Number(todayProfit || 0).toFixed(2) }}
                </p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                    <h3 class="text-gray-500 text-sm uppercase">Mesas Ocupadas</h3>
                    <p class="text-3xl font-bold text-red-600 mt-2">
                        {{ occupiedTables }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <h3 class="text-gray-500 text-sm uppercase">Mesas Libres</h3>
                    <p class="text-3xl font-bold text-blue-600 mt-2">
                        {{ freeTables }}
                    </p>
                </div>

                <div
                    v-if="canSeeStock"
                    class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500"
                >
                    <h3 class="text-gray-500 text-sm uppercase">Productos</h3>
                    <p class="text-3xl font-bold text-purple-600 mt-2">
                        {{ productsCount }}
                    </p>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-semibold mb-4">
                        Accesos rápidos
                    </h3>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.route"
                            :href="route(link.route)"
                            class="text-white p-4 rounded-lg text-center font-bold"
                            :class="link.color"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                </div>

                <div
                    v-if="canSeeCash"
                    class="bg-white rounded-xl shadow-lg p-6"
                >
                    <h3 class="text-xl font-semibold mb-4">
                        Estado de Caja
                    </h3>

                    <div v-if="cashRegister" class="p-4 rounded-xl bg-green-100 text-green-700 font-bold">
                        Caja abierta
                        <p class="text-sm font-normal mt-2">
                            Inicial: S/ {{ cashRegister.opening_amount }}
                        </p>
                    </div>

                    <div v-else class="p-4 rounded-xl bg-red-100 text-red-700 font-bold">
                        Caja cerrada
                        <p class="text-sm font-normal mt-2">
                            Abre caja antes de vender.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="canSeeStock"
                class="mt-8 bg-white rounded-xl shadow-lg p-6"
            >
                <h3 class="text-xl font-semibold mb-4">
                    Productos con bajo stock
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-zinc-500">
                                <th class="py-3">Producto</th>
                                <th>Precio</th>
                                <th>Stock</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="product in lowStockProducts"
                                :key="product.id"
                                class="border-b"
                            >
                                <td class="py-3 font-bold">
                                    {{ product.name }}
                                </td>
                                <td>
                                    S/ {{ product.price }}
                                </td>
                                <td class="font-bold text-red-600">
                                    {{ product.stock }}
                                </td>
                            </tr>

                            <tr v-if="lowStockProducts.length === 0">
                                <td colspan="3" class="py-8 text-center text-zinc-500">
                                    No hay productos con bajo stock.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
