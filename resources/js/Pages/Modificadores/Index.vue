<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    modifiers: Array,
    products: Array,
});

const editingModifier = ref(null);

const form = useForm({
    name: '',
    extra_price: 0,
    is_active: true,
    product_ids: [],
});

const resetForm = () => {
    editingModifier.value = null;

    form.reset();

    form.name = '';
    form.extra_price = 0;
    form.is_active = true;
    form.product_ids = [];
};

const submit = () => {
    if (editingModifier.value) {
        form.put(route('modificadores.update', editingModifier.value.id), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
        return;
    }

    form.post(route('modificadores.store'), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const editModifier = (modifier) => {
    editingModifier.value = modifier;

    form.name = modifier.name;
    form.extra_price = modifier.extra_price ?? 0;
    form.is_active = Boolean(modifier.is_active);

    form.product_ids = modifier.products
        ? modifier.products.map(product => product.id)
        : [];
};

const deleteModifier = (modifier) => {
    if (confirm('¿Eliminar este modificador?')) {
        router.delete(route('modificadores.destroy', modifier.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Modificadores" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🍕 Modificadores
            </h2>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-4">
                    {{ editingModifier ? 'Editar Modificador' : 'Nuevo Modificador' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Ej: Extra queso, Sin cebolla"
                        class="w-full rounded-lg border-zinc-300"
                    />

                    <input
                        v-model="form.extra_price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Precio extra"
                        class="w-full rounded-lg border-zinc-300"
                    />
                    <div>
    <label class="block mb-2 font-bold">
        Aplicar a los productos
    </label>

    <div
        class="border rounded-xl p-3 max-h-72 overflow-y-auto space-y-2"
    >
        <label
            v-for="product in props.products"
            :key="product.id"
            class="flex items-center gap-2"
        >
            <input
                type="checkbox"
                :value="product.id"
                v-model="form.product_ids"
            >

            <span>
                {{ product.name }}
            </span>
        </label>
    </div>
</div>

                    <label class="flex items-center gap-2 text-sm font-bold">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-zinc-300"
                        />
                        Modificador activo
                    </label>

                    <button
                        type="submit"
                        class="w-full bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800"
                    >
                        {{ editingModifier ? 'Actualizar Modificador' : 'Guardar Modificador' }}
                    </button>

                    <button
                        v-if="editingModifier"
                        type="button"
                        @click="resetForm"
                        class="w-full bg-zinc-200 text-zinc-800 rounded-xl py-3 font-bold hover:bg-zinc-300"
                    >
                        Cancelar edición
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-4">
                    Lista de Modificadores
                </h3>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-zinc-500">
                            <th class="py-3">Nombre</th>
                            <th>Precio extra</th>
                            <th>Productos</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="modifier in modifiers"
                            :key="modifier.id"
                            class="border-b"
                        >
                            <td class="py-3 font-bold">
                                {{ modifier.name }}
                            </td>

                            <td>
                                S/ {{ modifier.extra_price }}
                            </td>

                            <td>
    {{ modifier.products?.length ?? 0 }}
</td>

                            <td>
                                <span
                                    class="px-2 py-1 rounded-lg text-xs font-bold"
                                    :class="modifier.is_active
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-700'"
                                >
                                    {{ modifier.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            <td class="text-right space-x-2">
                                <button
                                    @click="editModifier(modifier)"
                                    class="bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-blue-700"
                                >
                                    Editar
                                </button>

                                <button
                                    @click="deleteModifier(modifier)"
                                    class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-red-700"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>

                        <tr v-if="modifiers.length === 0">
                            <td colspan="4" class="py-8 text-center text-zinc-500">
                                Aún no hay modificadores registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
