<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    products: Array,
});
</script>

<template>
    <Head title="Stock Bajo" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                📉 Stock Bajo
            </h2>
        </template>

        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold">
                        Productos para reposición
                    </h3>
                    <p class="text-zinc-500">
                        Productos con stock menor o igual a 5.
                    </p>
                </div>

                <Link
                    :href="route('productos')"
                    class="bg-black text-white px-5 py-3 rounded-xl font-bold hover:bg-zinc-800"
                >
                    Ir a Productos
                </Link>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-zinc-500">
                        <th class="py-3">Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="product in products"
                        :key="product.id"
                        class="border-b"
                    >
                        <td class="py-3 font-bold">
                            {{ product.name }}
                        </td>

                        <td>
                            {{ product.category?.name ?? 'Sin categoría' }}
                        </td>

                        <td>
                            S/ {{ product.price }}
                        </td>

                        <td class="font-black text-red-600">
                            {{ product.stock }}
                        </td>
                    </tr>

                    <tr v-if="products.length === 0">
                        <td colspan="4" class="py-8 text-center text-zinc-500">
                            No hay productos con stock bajo.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
