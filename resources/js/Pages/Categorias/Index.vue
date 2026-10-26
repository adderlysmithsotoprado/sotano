<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    categories: Array,
});

const editingCategory = ref(null);

const form = useForm({
    name: '',
    type: 'comida',
    is_active: true,
});

const resetForm = () => {
    editingCategory.value = null;
    form.reset();
    form.name = '';
    form.type = 'comida';
    form.is_active = true;
};

const submit = () => {
    if (editingCategory.value) {
        form.put(route('categorias.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
        return;
    }

    form.post(route('categorias.store'), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const editCategory = (category) => {
    editingCategory.value = category;
    form.name = category.name;
    form.type = category.type ?? 'comida';
    form.is_active = Boolean(category.is_active);
};

const deleteCategory = (category) => {
    if (confirm('¿Eliminar esta categoría?')) {
        router.delete(route('categorias.destroy', category.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Categorías" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🗂️ Categorías
            </h2>
        </template>

        <div
            v-if="$page.props.errors?.category"
            class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl font-bold"
        >
            {{ $page.props.errors.category }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-4">
                    {{ editingCategory ? 'Editar Categoría' : 'Nueva Categoría' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Nombre de categoría"
                        class="w-full rounded-lg border-zinc-300"
                    />

                    <select
                        v-model="form.type"
                        class="w-full rounded-lg border-zinc-300"
                    >
                        <option value="comida">Comida</option>
                        <option value="bebida">Bebida</option>
                        <option value="otro">Otro</option>
                    </select>

                    <label class="flex items-center gap-2 text-sm font-bold">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-zinc-300"
                        />
                        Categoría activa
                    </label>

                    <button
                        type="submit"
                        class="w-full bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800"
                    >
                        {{ editingCategory ? 'Actualizar Categoría' : 'Guardar Categoría' }}
                    </button>

                    <button
                        v-if="editingCategory"
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
                    Lista de Categorías
                </h3>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-zinc-500">
                            <th class="py-3">Nombre</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="category in categories"
                            :key="category.id"
                            class="border-b"
                        >
                            <td class="py-3 font-bold">
                                {{ category.name }}
                            </td>

                            <td class="uppercase">
                                {{ category.type }}
                            </td>

                            <td>
                                <span
                                    class="px-2 py-1 rounded-lg text-xs font-bold"
                                    :class="category.is_active
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-red-100 text-red-700'"
                                >
                                    {{ category.is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>

                            <td class="text-right space-x-2">
                                <button
                                    @click="editCategory(category)"
                                    class="bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-blue-700"
                                >
                                    Editar
                                </button>

                                <button
                                    @click="deleteCategory(category)"
                                    class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-red-700"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>

                        <tr v-if="categories.length === 0">
                            <td colspan="4" class="py-8 text-center text-zinc-500">
                                Aún no hay categorías registradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
