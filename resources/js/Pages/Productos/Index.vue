<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    products: Array,
    categories: Array,
    modifiers: Array,
});

const editingProduct = ref(null);

const form = useForm({
    category_id: '',
    name: '',
    sku: '',
    price: '',
    cost: '',
    stock: 0,
    printer_area: 'none',
    is_active: true,
    is_favorite: false,
    modifier_ids: [],
});

const resetForm = () => {
    editingProduct.value = null;
    form.reset();
    form.category_id = '';
    form.name = '';
    form.sku = '';
    form.price = '';
    form.cost = '';
    form.stock = 0;
    form.printer_area = 'none';
    form.is_active = true;
    form.is_favorite = false;
    form.modifier_ids = [];
};

const submit = () => {
    if (editingProduct.value) {
        form.put(route('productos.update', editingProduct.value.id), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
        return;
    }

    form.post(route('productos.store'), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const editProduct = (product) => {
    editingProduct.value = product;

    form.category_id = product.category_id;
    form.name = product.name;
    form.sku = product.sku ?? '';
    form.price = product.price;
    form.cost = product.cost ?? 0;
    form.stock = product.stock ?? 0;
    form.printer_area = product.printer_area ?? 'none';
    form.is_active = Boolean(product.is_active);
    form.is_favorite = Boolean(product.is_favorite);
    form.modifier_ids = product.modifiers
    ? product.modifiers.map(m => m.id)
    : [];
};

const deleteProduct = (product) => {
    if (confirm('¿Eliminar este producto?')) {
        router.delete(route('productos.destroy', product.id), {
            preserveScroll: true,
        });
    }
};

const money = (value) => {
    return Number(value || 0).toFixed(2);
};

const profit = (product) => {
    return Number(product.price || 0) - Number(product.cost || 0);
};

const margin = (product) => {
    const price = Number(product.price || 0);

    if (price <= 0) {
        return 0;
    }

    return (profit(product) / price) * 100;
};
</script>

<template>
    <Head title="Productos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                📦 Productos
            </h2>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold">
                        {{ editingProduct ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h3>

                    <Link
                        :href="route('categorias')"
                        class="bg-zinc-900 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-black"
                    >
                        🗂️ Categorías
                    </Link>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Nombre del producto"
                        class="w-full rounded-lg border-zinc-300"
                    />

                    <select
                        v-model="form.category_id"
                        class="w-full rounded-lg border-zinc-300"
                    >
                        <option value="">Seleccionar categoría</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <input
                        v-model="form.sku"
                        type="text"
                        placeholder="Código / SKU"
                        class="w-full rounded-lg border-zinc-300"
                    />

                    <input
                        v-model="form.price"
                        type="number"
                        step="0.01"
                        placeholder="Precio venta"
                        class="w-full rounded-lg border-zinc-300"
                    />

                    <input
                        v-model="form.cost"
                        type="number"
                        step="0.01"
                        placeholder="Costo"
                        class="w-full rounded-lg border-zinc-300"
                    />

                    <input
                        v-model="form.stock"
                        type="number"
                        placeholder="Stock"
                        class="w-full rounded-lg border-zinc-300"
                    />

                    <select
                        v-model="form.printer_area"
                        class="w-full rounded-lg border-zinc-300"
                    >
                        <option value="none">Sin impresión</option>
                        <option value="cocina">Cocina</option>
                        <option value="bar">Bar</option>
                    </select>

                    <label class="flex items-center gap-2 text-sm font-bold">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-zinc-300"
                        />
                        Producto activo
                    </label>

                    <label class="flex items-center gap-2 text-sm font-bold">
                    <input
                    v-model="form.is_favorite"
                    type="checkbox"
                    class="rounded border-zinc-300"
                    />
                    ⭐ Producto favorito
                    </label>

                    <div class="border rounded-xl p-4 bg-zinc-50">
                    <p class="font-bold mb-3">
                    🍕 Modificadores disponibles
                    </p>

                    <div class="grid grid-cols-2 gap-2">
                    <label
                    v-for="modifier in props.modifiers"
                    :key="modifier.id"
                    class="flex items-center gap-2 text-sm"
                    >
                    <input
                    type="checkbox"
                    :value="modifier.id"
                    v-model="form.modifier_ids"
                    />

                    {{ modifier.name }}

                    <span
                    v-if="modifier.extra_price > 0"
                    class="text-green-600 font-bold"
                    >
                    (+S/ {{ modifier.extra_price }})
                    </span>
                    </label>
                </div>
            </div>

                    <button
                        type="submit"
                        class="w-full bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800"
                    >
                        {{ editingProduct ? 'Actualizar Producto' : 'Guardar Producto' }}
                    </button>

                    <button
                        v-if="editingProduct"
                        type="button"
                        @click="resetForm"
                        class="w-full bg-zinc-200 text-zinc-800 rounded-xl py-3 font-bold hover:bg-zinc-300"
                    >
                        Cancelar edición
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold">
                        Lista de Productos
                    </h3>

                    <Link
                        :href="route('categorias')"
                        class="bg-zinc-900 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-black"
                    >
                        🗂️ Categorías
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="border-b text-left text-zinc-500">
                        <th class="py-3">Producto</th>
                        <th>Categoría</th>
                        <th>Costo</th>
                        <th>Precio</th>
                        <th>Utilidad</th>
                        <th>Margen</th>
                        <th>Stock</th>
                        <th>Impresión</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                        </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="product in products"
                                :key="product.id"
                                class="border-b"
                            >
                                <td class="py-3 font-semibold">
                                    {{ product.name }}
                                </td>

                                <td>
                                    {{ product.category?.name ?? 'Sin categoría' }}
                                </td>

                                <td>
                                S/ {{ money(product.cost) }}
                                </td>

                                <td>
                                S/ {{ money(product.price) }}
                                </td>

                                <td
                                class="font-bold"
                                :class="profit(product) >= 0 ? 'text-green-600' : 'text-red-600'"
                                >
                                S/ {{ money(profit(product)) }}
                                </td>

                                <td
                                class="font-bold"
                                :class="margin(product) >= 30 ? 'text-green-600' : 'text-orange-600'"
                                >
                                {{ money(margin(product)) }}%
                                </td>

                                <td
                                    class="font-bold"
                                    :class="product.stock <= 5 ? 'text-red-600' : 'text-green-600'"
                                >
                                    {{ product.stock }}
                                </td>

                                <td class="uppercase">
                                    {{ product.printer_area }}
                                </td>

                                <td>
                                    <span
                                        class="px-2 py-1 rounded-lg text-xs font-bold"
                                        :class="product.is_active
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-red-100 text-red-700'"
                                    >
                                        {{ product.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>

                                <td class="text-right space-x-2">
                                    <button
                                        @click="editProduct(product)"
                                        class="bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-blue-700"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        @click="deleteProduct(product)"
                                        class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-red-700"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="products.length === 0">
                                <td colspan="10" class="py-6 text-center text-zinc-500">
                                    Aún no hay productos registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
