<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    order: Object,
});

const selectedQuantities = reactive({});

const form = useForm({
    items: [],
});

const money = (value) => Number(value || 0).toFixed(2);

const activeItems = computed(() => {
    return (props.order.items ?? []).filter(
        item => item.status !== 'cancelado'
    );
});

const selectedQuantity = (item) => {
    return Number(selectedQuantities[item.id] || 0);
};

const unitSubtotal = (item) => {
    const quantity = Number(item.quantity || 1);

    if (quantity <= 0) {
        return 0;
    }

    return Number(item.subtotal || 0) / quantity;
};

const increaseSelection = (item) => {
    const current = selectedQuantity(item);
    const maximum = Number(item.quantity || 0);

    if (current >= maximum) {
        return;
    }

    selectedQuantities[item.id] = current + 1;
};

const decreaseSelection = (item) => {
    const current = selectedQuantity(item);

    if (current <= 0) {
        return;
    }

    selectedQuantities[item.id] = current - 1;
};

const selectedItemsCount = computed(() => {
    return activeItems.value.reduce((sum, item) => {
        return sum + selectedQuantity(item);
    }, 0);
});

const selectedTotal = computed(() => {
    return activeItems.value.reduce((sum, item) => {
        return sum + (
            unitSubtotal(item) *
            selectedQuantity(item)
        );
    }, 0);
});

const processSplitBill = () => {
    const items = activeItems.value
        .map(item => ({
            item_id: item.id,
            quantity: selectedQuantity(item),
        }))
        .filter(item => item.quantity > 0);

    if (items.length === 0) {
        alert('Selecciona al menos una unidad.');
        return;
    }

    if (!confirm('¿Deseas cobrar estas unidades en una cuenta separada?')) {
        return;
    }

    form.items = items;

    form.post(
        route('pos.split-bill.process', props.order.id),
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <Head title="Dividir Cuenta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                💳 Dividir Cuenta - {{ order.table?.name }}
            </h2>
        </template>

        <div
            v-if="$page.props.errors?.items"
            class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl font-bold"
        >
            {{ $page.props.errors.items }}
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-black mb-2">
                    Selecciona las unidades que pagará esta persona
                </h3>

                <p class="text-sm text-zinc-500 mb-5">
                    Puedes separar una o varias unidades de un mismo producto.
                </p>

                <div class="space-y-4">
                    <div
                        v-for="item in activeItems"
                        :key="item.id"
                        class="border rounded-2xl p-4"
                        :class="selectedQuantity(item) > 0
                            ? 'border-green-600 bg-green-50'
                            : 'border-zinc-200 bg-white'"
                    >
                        <div class="flex justify-between gap-4">
                            <div>
                                <p class="font-black text-lg">
                                    {{ item.product_name }}
                                </p>

                                <p class="text-sm text-zinc-500">
                                    Disponible: {{ item.quantity }}
                                </p>

                                <p v-if="item.notes" class="text-xs text-zinc-500 mt-1">
                                    Nota: {{ item.notes }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="font-black">
                                    S/ {{ money(unitSubtotal(item)) }} c/u
                                </p>

                                <p class="text-sm text-zinc-500">
                                    Subtotal original:
                                    S/ {{ money(item.subtotal) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="decreaseSelection(item)"
                                    :disabled="selectedQuantity(item) <= 0"
                                    class="w-10 h-10 rounded-xl bg-zinc-200 font-black disabled:opacity-40"
                                >
                                    -
                                </button>

                                <span class="w-10 text-center text-xl font-black">
                                    {{ selectedQuantity(item) }}
                                </span>

                                <button
                                    type="button"
                                    @click="increaseSelection(item)"
                                    :disabled="selectedQuantity(item) >= Number(item.quantity)"
                                    class="w-10 h-10 rounded-xl bg-black text-white font-black disabled:opacity-40"
                                >
                                    +
                                </button>
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-zinc-500">
                                    Total seleccionado
                                </p>

                                <p class="text-xl font-black text-green-700">
                                    S/
                                    {{
                                        money(
                                            unitSubtotal(item) *
                                            selectedQuantity(item)
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="activeItems.length === 0"
                        class="py-10 text-center text-zinc-500"
                    >
                        No hay productos disponibles para dividir.
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 h-fit">
                <h3 class="text-xl font-black mb-4">
                    Cuenta seleccionada
                </h3>

                <p class="text-zinc-500">
                    Unidades seleccionadas:
                    <strong>{{ selectedItemsCount }}</strong>
                </p>

                <div class="mt-6 flex justify-between text-2xl font-black">
                    <span>Total</span>
                    <span>S/ {{ money(selectedTotal) }}</span>
                </div>

                <button
                    type="button"
                    @click="processSplitBill"
                    :disabled="selectedItemsCount === 0 || form.processing"
                    class="mt-6 w-full rounded-xl py-4 font-black"
                    :class="selectedItemsCount === 0 || form.processing
                        ? 'bg-zinc-300 text-zinc-500 cursor-not-allowed'
                        : 'bg-green-600 hover:bg-green-700 text-white'"
                >
                    {{
                        form.processing
                            ? 'Separando cuenta...'
                            : 'Cobrar cuenta seleccionada'
                    }}
                </button>

                <p class="text-xs text-zinc-500 mt-3">
                    Las unidades seleccionadas pasarán a una cuenta independiente.
                </p>

                <Link
                    :href="route('pos.show', order.table.id)"
                    class="mt-4 block w-full bg-black text-white rounded-xl py-4 font-black text-center"
                >
                    Volver al POS
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
