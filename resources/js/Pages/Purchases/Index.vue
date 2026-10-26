<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    purchases: Array,
    suppliers: Array,
    products: Array,
});

const form = useForm({
    supplier_id: '',
    document_type: 'factura',
    document_number: '',
    purchase_date: new Date().toISOString().slice(0, 10),
    notes: '',
    items: [
        {
            product_id: '',
            quantity: 1,
            unit_cost: 0,
        },
    ],
});

const money = (value) => Number(value || 0).toFixed(2);

const total = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (Number(item.quantity || 0) * Number(item.unit_cost || 0));
    }, 0);
});

const addItem = () => {
    form.items.push({
        product_id: '',
        quantity: 1,
        unit_cost: 0,
    });
};

const removeItem = (index) => {
    if (form.items.length === 1) {
        return;
    }

    form.items.splice(index, 1);
};

const save = () => {
    form.post(route('compras.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.document_type = 'factura';
            form.purchase_date = new Date().toISOString().slice(0, 10);
            form.items = [
                {
                    product_id: '',
                    quantity: 1,
                    unit_cost: 0,
                },
            ];
        },
    });
};
</script>

<template>
    <Head title="Compras" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold">
                🧾 Compras a Proveedores
            </h2>
        </template>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-black mb-5">
                    Registrar compra
                </h3>

                <form @submit.prevent="save" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <select
                            v-model="form.supplier_id"
                            class="rounded-xl border-zinc-300"
                        >
                            <option value="">Sin proveedor</option>

                            <option
                                v-for="supplier in suppliers"
                                :key="supplier.id"
                                :value="supplier.id"
                            >
                                {{ supplier.business_name }}
                            </option>
                        </select>

                        <select
                            v-model="form.document_type"
                            class="rounded-xl border-zinc-300"
                        >
                            <option value="factura">Factura</option>
                            <option value="boleta">Boleta</option>
                            <option value="nota">Nota</option>
                        </select>

                        <input
                            v-model="form.document_number"
                            placeholder="N° documento"
                            class="rounded-xl border-zinc-300"
                        >

                        <input
                            v-model="form.purchase_date"
                            type="date"
                            class="rounded-xl border-zinc-300"
                            required
                        >
                    </div>

                    <textarea
                        v-model="form.notes"
                        placeholder="Observaciones"
                        class="w-full rounded-xl border-zinc-300"
                    />

                    <div class="border rounded-2xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-100">
                                <tr class="text-left text-zinc-500">
                                    <th class="p-3">Producto</th>
                                    <th>Cantidad</th>
                                    <th>Costo unit.</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="border-t"
                                >
                                    <td class="p-3">
                                        <select
                                            v-model="item.product_id"
                                            class="w-full rounded-lg border-zinc-300"
                                            required
                                        >
                                            <option value="">Seleccione producto</option>

                                            <option
                                                v-for="product in products"
                                                :key="product.id"
                                                :value="product.id"
                                            >
                                                {{ product.name }} - Stock: {{ product.stock }}
                                            </option>
                                        </select>
                                    </td>

                                    <td>
                                        <input
                                            v-model="item.quantity"
                                            type="number"
                                            min="1"
                                            class="w-24 rounded-lg border-zinc-300"
                                            required
                                        >
                                    </td>

                                    <td>
                                        <input
                                            v-model="item.unit_cost"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-28 rounded-lg border-zinc-300"
                                            required
                                        >
                                    </td>

                                    <td class="font-bold">
                                        S/ {{ money(Number(item.quantity || 0) * Number(item.unit_cost || 0)) }}
                                    </td>

                                    <td class="text-right pr-3">
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
                                            class="text-red-600 font-bold"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button
                        type="button"
                        @click="addItem"
                        class="bg-zinc-200 px-5 py-3 rounded-xl font-bold hover:bg-zinc-300"
                    >
                        + Agregar producto
                    </button>

                    <div class="flex items-center justify-between border-t pt-5">
                        <div class="text-2xl font-black">
                            Total: S/ {{ money(total) }}
                        </div>

                        <button
                            type="submit"
                            class="bg-black text-white px-8 py-3 rounded-xl font-bold hover:bg-zinc-800"
                            :disabled="form.processing"
                        >
                            Guardar compra
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-black mb-5">
                    Últimas compras
                </h3>

                <div class="space-y-3">
                    <div
                        v-for="purchase in purchases"
                        :key="purchase.id"
                        class="border rounded-xl p-4"
                    >
                        <p class="font-black">
                            Compra #{{ purchase.id }}
                        </p>

                        <p class="text-sm text-zinc-500">
                            {{ purchase.supplier?.business_name ?? 'Sin proveedor' }}
                        </p>

                        <p class="text-sm">
                            {{ purchase.document_type ?? '-' }} {{ purchase.document_number ?? '' }}
                        </p>

                        <p class="font-black mt-2">
                            S/ {{ money(purchase.total) }}
                        </p>

                        <Link
                        :href="route('compras.show', purchase.id)"
                        class="mt-3 inline-block bg-black text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-zinc-800"
                        >
                        Ver detalle
                        </Link>

                        <div class="mt-2 text-xs text-zinc-500">
                            <p
                                v-for="item in purchase.items"
                                :key="item.id"
                            >
                                {{ item.quantity }} x {{ item.product?.name }} - S/ {{ money(item.subtotal) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="purchases.length === 0"
                        class="text-center text-zinc-500 py-10"
                    >
                        Aún no hay compras registradas.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
