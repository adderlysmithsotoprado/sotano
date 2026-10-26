<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
});

const itemsByArea = (order, area) => {
    return order.items
        ? order.items.filter(item => item.printer_area === area && item.status !== 'entregado')
        : [];
};

const tableName = (order) => {
    return order.table ? order.table.name : 'Sin mesa';
};

const statusLabel = (status) => {
    return status === 'entregado'
        ? 'Entregado'
        : 'Pendiente';
};

const statusClass = (status) => {
    return status === 'entregado'
        ? 'bg-green-100 text-green-700'
        : 'bg-yellow-100 text-yellow-700';
};



</script>

<template>
    <Head title="Comandas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                👨‍🍳 Comandas Cocina / Bar
            </h2>
        </template>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-2xl font-black mb-4">
                    👨‍🍳 Cocina
                </h3>

                <div
                    v-for="order in orders"
                    :key="'cocina-' + order.id"
                    class="mb-5 border rounded-2xl p-4"
                >
                    <div>
                        <h4 class="font-black">
                            Pedido #{{ order.id }} - {{ tableName(order) }}
                        </h4>


                    </div>

                    <div v-if="itemsByArea(order, 'cocina').length > 0" class="mt-4 space-y-3">
                        <div
                            v-for="item in itemsByArea(order, 'cocina')"
                            :key="item.id"
                            class="bg-orange-50 border border-orange-200 rounded-xl p-4"
                        >
                            <p class="font-black text-lg">
                                {{ item.quantity }} x {{ item.product_name }}
                            </p>

                            <span
                                class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-black"
                                :class="statusClass(item.status)"
                            >
                                {{ statusLabel(item.status) }}
                            </span>

                            <div
                                v-if="item.modifiers && item.modifiers.length > 0"
                                class="mt-3 text-sm text-zinc-700"
                            >
                                <p
                                    v-for="modifier in item.modifiers"
                                    :key="modifier.id"
                                    class="font-semibold"
                                >
                                    + {{ modifier.modifier_name }}
                                    <span v-if="Number(modifier.extra_price) > 0">
                                        (+S/ {{ modifier.extra_price }})
                                    </span>
                                </p>
                            </div>

                            <p
                                v-if="item.notes"
                                class="mt-2 text-sm bg-yellow-100 text-yellow-800 rounded-lg px-3 py-2 font-semibold"
                            >
                                Nota: {{ item.notes }}
                            </p>


                        </div>
                    </div>

                    <div v-else class="mt-4 text-sm text-zinc-400">
                        Sin productos pendientes para cocina.
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-2xl font-black mb-4">
                    🍸 Bar
                </h3>

                <div
                    v-for="order in orders"
                    :key="'bar-' + order.id"
                    class="mb-5 border rounded-2xl p-4"
                >
                    <div>
                        <h4 class="font-black">
                            Pedido #{{ order.id }} - {{ tableName(order) }}
                        </h4>


                    </div>

                    <div v-if="itemsByArea(order, 'bar').length > 0" class="mt-4 space-y-3">
                        <div
                            v-for="item in itemsByArea(order, 'bar')"
                            :key="item.id"
                            class="bg-blue-50 border border-blue-200 rounded-xl p-4"
                        >
                            <p class="font-black text-lg">
                                {{ item.quantity }} x {{ item.product_name }}
                            </p>

                            <span
                                class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-black"
                                :class="statusClass(item.status)"
                            >
                                {{ statusLabel(item.status) }}
                            </span>

                            <div
                                v-if="item.modifiers && item.modifiers.length > 0"
                                class="mt-3 text-sm text-zinc-700"
                            >
                                <p
                                    v-for="modifier in item.modifiers"
                                    :key="modifier.id"
                                    class="font-semibold"
                                >
                                    + {{ modifier.modifier_name }}
                                    <span v-if="Number(modifier.extra_price) > 0">
                                        (+S/ {{ modifier.extra_price }})
                                    </span>
                                </p>
                            </div>

                            <p
                                v-if="item.notes"
                                class="mt-2 text-sm bg-yellow-100 text-yellow-800 rounded-lg px-3 py-2 font-semibold"
                            >
                                Nota: {{ item.notes }}
                            </p>

                            <div class="mt-4">
    <button
        @click="markDelivered(item)"
        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl py-3 text-base font-black tracking-wide"
    >
        ✅ ENTREGADO
    </button>
</div>
                        </div>
                    </div>

                    <div v-else class="mt-4 text-sm text-zinc-400">
                        Sin productos pendientes para bar.
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="orders.length === 0"
            class="mt-6 bg-white rounded-2xl shadow p-10 text-center text-zinc-500"
        >
            No hay comandas pendientes.
        </div>
    </AuthenticatedLayout>
</template>
