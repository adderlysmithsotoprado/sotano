<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    purchase: Object,
});

const money = (value) => Number(value || 0).toFixed(2);
</script>

<template>
    <Head title="Detalle de Compra" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold">
                🧾 Compra #{{ purchase.id }}
            </h2>
        </template>

        <div class="bg-white rounded-2xl shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="rounded-xl bg-zinc-100 p-4">
                    <p class="text-sm text-zinc-500">Proveedor</p>
                    <p class="font-black">
                        {{ purchase.supplier?.business_name ?? 'Sin proveedor' }}
                    </p>
                </div>

                <div class="rounded-xl bg-zinc-100 p-4">
                    <p class="text-sm text-zinc-500">Documento</p>
                    <p class="font-black">
                        {{ purchase.document_type ?? '-' }} {{ purchase.document_number ?? '' }}
                    </p>
                </div>

                <div class="rounded-xl bg-zinc-100 p-4">
                    <p class="text-sm text-zinc-500">Fecha</p>
                    <p class="font-black">
                        {{ purchase.purchase_date }}
                    </p>
                </div>

                <div class="rounded-xl bg-green-100 p-4 text-green-700">
                    <p class="text-sm">Total</p>
                    <p class="text-2xl font-black">
                        S/ {{ money(purchase.total) }}
                    </p>
                </div>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-zinc-500">
                        <th class="py-3">Producto</th>
                        <th>Cantidad</th>
                        <th>Costo unit.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="item in purchase.items"
                        :key="item.id"
                        class="border-b"
                    >
                        <td class="py-3 font-bold">
                            {{ item.product?.name ?? 'Producto eliminado' }}
                        </td>

                        <td>
                            {{ item.quantity }}
                        </td>

                        <td>
                            S/ {{ money(item.unit_cost) }}
                        </td>

                        <td class="text-right font-bold">
                            S/ {{ money(item.subtotal) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-6 flex gap-3">
                <Link
                    :href="route('compras')"
                    class="bg-zinc-200 px-5 py-3 rounded-xl font-bold"
                >
                    Volver
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
