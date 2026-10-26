<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    promotions: Array,
    products: Array,
});

const form = useForm({
    name: '',
    type: '2x1',
    product_id: '',
    minimum_quantity: 3,
    promotional_unit_price: '',
    discount_percentage: '',
    discount_amount: '',
    days: [],
    start_time: '',
    end_time: '',
    is_active: true,
});

const days = [
    { value: 'monday', label: 'Lunes' },
    { value: 'tuesday', label: 'Martes' },
    { value: 'wednesday', label: 'Miércoles' },
    { value: 'thursday', label: 'Jueves' },
    { value: 'friday', label: 'Viernes' },
    { value: 'saturday', label: 'Sábado' },
    { value: 'sunday', label: 'Domingo' },
];

const submit = () => {
    form.post(route('promociones.store'), {
        preserveScroll: true,
        onSuccess: () => {
    form.reset();

    form.type = '2x1';
    form.minimum_quantity = 3;
    form.is_active = true;
},
    });
};

const deletePromotion = (promotion) => {
    if (confirm('¿Eliminar esta promoción?')) {
        router.delete(route('promociones.destroy', promotion.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Promociones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🎉 Promociones
            </h2>
        </template>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-black mb-4">
                    Promociones
                </h3>
                <div>
    <label class="block text-sm font-bold mb-2">
        Tipo de promoción
    </label>

    <select
        v-model="form.type"
        class="w-full rounded-xl border-zinc-300"
    >
        <option value="2x1">
            2x1
        </option>

        <option value="quantity_price">
            Precio especial por cantidad
        </option>

        <option value="percentage">
            Descuento porcentual
        </option>

        <option value="fixed_discount">
            Descuento fijo
        </option>
    </select>
</div>

                <form @submit.prevent="submit" class="space-y-4">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Ej: Martes de Mojitos"
                        class="w-full rounded-xl border-zinc-300"
                    />

                    <select
                        v-model="form.product_id"
                        class="w-full rounded-xl border-zinc-300"
                    >
                        <option value="">Seleccionar producto</option>

                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="product.id"
                        >
                            {{ product.name }} - S/ {{ product.price }}
                        </option>
                    </select>
                    <div
    v-if="form.type === 'quantity_price'"
    class="grid grid-cols-2 gap-3"
>
    <div>
        <label class="block text-sm font-bold mb-2">
            Cantidad mínima
        </label>

        <input
            v-model="form.minimum_quantity"
            type="number"
            min="2"
            class="w-full rounded-xl border-zinc-300"
            placeholder="Ej: 3"
        />
    </div>

    <div>
        <label class="block text-sm font-bold mb-2">
            Precio unitario promocional
        </label>

        <input
            v-model="form.promotional_unit_price"
            type="number"
            step="0.01"
            min="0"
            class="w-full rounded-xl border-zinc-300"
            placeholder="Ej: 10.00"
        />
    </div>
</div>

<div v-if="form.type === 'percentage'">
    <label class="block text-sm font-bold mb-2">
        Porcentaje de descuento
    </label>

    <input
        v-model="form.discount_percentage"
        type="number"
        step="0.01"
        min="0.01"
        max="100"
        class="w-full rounded-xl border-zinc-300"
        placeholder="Ej: 20"
    />
</div>

<div v-if="form.type === 'fixed_discount'">
    <label class="block text-sm font-bold mb-2">
        Descuento fijo
    </label>

    <input
        v-model="form.discount_amount"
        type="number"
        step="0.01"
        min="0.01"
        class="w-full rounded-xl border-zinc-300"
        placeholder="Ej: 5.00"
    />
</div>


                    <div>
                        <label class="block text-sm font-bold mb-2">
                            Días activos
                        </label>

                        <div class="grid grid-cols-2 gap-2">
                            <label
                                v-for="day in days"
                                :key="day.value"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    :value="day.value"
                                    v-model="form.days"
                                />
                                {{ day.label }}
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold mb-2">
                                Inicio
                            </label>
                            <input
                                v-model="form.start_time"
                                type="time"
                                class="w-full rounded-xl border-zinc-300"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2">
                                Fin
                            </label>
                            <input
                                v-model="form.end_time"
                                type="time"
                                class="w-full rounded-xl border-zinc-300"
                            />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 font-bold">
                        <input
                            type="checkbox"
                            v-model="form.is_active"
                        />
                        Promoción activa
                    </label>

                    <button
                        type="submit"
                        class="w-full bg-black text-white rounded-xl py-3 font-black hover:bg-zinc-800"
                    >
                        Guardar promoción
                    </button>
                </form>
            </div>

            <div class="xl:col-span-2 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-black mb-4">
                    Promociones registradas
                </h3>

                <div class="space-y-3">
                    <div
                        v-for="promotion in promotions"
                        :key="promotion.id"
                        class="border rounded-2xl p-4 flex justify-between gap-4"
                    >
                        <div>
                            <p class="font-black text-lg">
                                {{ promotion.name }}
                            </p>

                            <p class="text-sm text-zinc-500">
                                Tipo: {{ promotion.type }} |
                                Producto: {{ promotion.product?.name }}
                            </p>
                            <p
    v-if="promotion.type === 'quantity_price'"
    class="text-sm font-bold text-blue-700"
>
    Desde {{ promotion.minimum_quantity }} unidades:
    S/ {{ promotion.promotional_unit_price }} cada una
</p>

<p
    v-if="promotion.type === 'percentage'"
    class="text-sm font-bold text-green-700"
>
    Descuento: {{ promotion.discount_percentage }}%
</p>

<p
    v-if="promotion.type === 'fixed_discount'"
    class="text-sm font-bold text-orange-700"
>
    Descuento: S/ {{ promotion.discount_amount }}
</p>

<p
    v-if="promotion.type === '2x1'"
    class="text-sm font-bold text-purple-700"
>
    Se cobra una unidad por cada dos unidades
</p>

                            <p class="text-sm text-zinc-500">
                                Días: {{ promotion.days?.join(', ') || 'Todos' }}
                            </p>

                            <p class="text-sm text-zinc-500">
                                Horario:
                                {{ promotion.start_time || '--' }}
                                -
                                {{ promotion.end_time || '--' }}
                            </p>

                            <p
                                class="text-sm font-bold mt-1"
                                :class="promotion.is_active ? 'text-green-600' : 'text-red-600'"
                            >
                                {{ promotion.is_active ? 'Activa' : 'Inactiva' }}
                            </p>
                        </div>

                        <button
                            @click="deletePromotion(promotion)"
                            class="text-red-600 font-black"
                        >
                            Eliminar
                        </button>
                    </div>

                    <div
                        v-if="promotions.length === 0"
                        class="text-center text-zinc-500 py-10"
                    >
                        Aún no hay promociones.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
