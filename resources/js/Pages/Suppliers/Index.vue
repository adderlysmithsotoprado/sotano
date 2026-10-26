<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    suppliers: Array,
});

const form = useForm({
    business_name: '',
    ruc: '',
    phone: '',
    email: '',
    address: '',
});

const save = () => {
    form.post(route('proveedores.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const remove = (supplier) => {
    if (!confirm('¿Desactivar proveedor?')) return;

    useForm({}).delete(route('proveedores.destroy', supplier.id));
};
</script>

<template>
    <Head title="Proveedores" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold">
                🚚 Proveedores
            </h2>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-5">
                    Nuevo proveedor
                </h3>

                <form
                    @submit.prevent="save"
                    class="space-y-4"
                >
                    <input
                        v-model="form.business_name"
                        placeholder="Razón Social"
                        class="w-full rounded-xl border-zinc-300"
                    >

                    <input
                        v-model="form.ruc"
                        placeholder="RUC"
                        class="w-full rounded-xl border-zinc-300"
                    >

                    <input
                        v-model="form.phone"
                        placeholder="Teléfono"
                        class="w-full rounded-xl border-zinc-300"
                    >

                    <input
                        v-model="form.email"
                        placeholder="Correo"
                        class="w-full rounded-xl border-zinc-300"
                    >

                    <textarea
                        v-model="form.address"
                        placeholder="Dirección"
                        class="w-full rounded-xl border-zinc-300"
                    />

                    <button
                        class="bg-black text-white w-full rounded-xl py-3 font-bold"
                    >
                        Guardar proveedor
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6">

                <h3 class="text-xl font-bold mb-5">
                    Lista de proveedores
                </h3>

                <div class="overflow-x-auto">

                    <table class="w-full">

                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-3">Proveedor</th>
                                <th>RUC</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr
                                v-for="supplier in suppliers"
                                :key="supplier.id"
                                class="border-b"
                            >
                                <td class="py-3">
                                    {{ supplier.business_name }}
                                </td>

                                <td>
                                    {{ supplier.ruc }}
                                </td>

                                <td>
                                    {{ supplier.phone }}
                                </td>

                                <td>
                                    {{ supplier.email }}
                                </td>

                                <td>

                                    <span
                                        v-if="supplier.is_active"
                                        class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold"
                                    >
                                        Activo
                                    </span>

                                    <span
                                        v-else
                                        class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold"
                                    >
                                        Inactivo
                                    </span>

                                </td>

                                <td class="text-right">

                                    <button
                                        @click="remove(supplier)"
                                        class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs"
                                    >
                                        Desactivar
                                    </button>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </AuthenticatedLayout>

</template>
