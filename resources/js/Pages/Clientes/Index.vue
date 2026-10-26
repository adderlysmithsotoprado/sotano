<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    customers: Array,
});

const editingCustomer = ref(null);
const search = ref('');

const form = useForm({
    name: '',
    document: '',
    phone: '',
    email: '',
    birthday: '',
    is_active: true,
});

const filteredCustomers = computed(() => {
    const term = search.value.toLowerCase();

    return (props.customers ?? []).filter(customer =>
        customer.name?.toLowerCase().includes(term) ||
        customer.document?.toLowerCase().includes(term) ||
        customer.phone?.toLowerCase().includes(term)
    );
});

const resetForm = () => {
    editingCustomer.value = null;
    form.reset();
    form.is_active = true;
};

const save = () => {
    if (editingCustomer.value && editingCustomer.value.id) {
        form.put(route('clientes.update', editingCustomer.value.id), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
            onError: (errors) => console.log(errors),
        });

        return;
    }

    form.post(route('clientes.store'), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
        onError: (errors) => console.log(errors),
    });
};

const editCustomer = (customer) => {
    editingCustomer.value = { ...customer };

    form.name = customer.name ?? '';
    form.document = customer.document ?? '';
    form.phone = customer.phone ?? '';
    form.email = customer.email ?? '';
    form.birthday = customer.birthday
        ? customer.birthday.substring(0, 10)
        : '';
    form.is_active = Boolean(customer.is_active);
};

const deleteCustomer = (customer) => {
    if (!confirm('¿Eliminar este cliente?')) return;

    router.delete(route('clientes.destroy', customer.id), {
        preserveScroll: true,
    });
};

const money = (value) => Number(value || 0).toFixed(2);
</script>

<template>
    <Head title="Clientes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                👥 Clientes
            </h2>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-5">
                    {{ editingCustomer ? 'Editar cliente' : 'Nuevo cliente' }}
                </h3>

                <form @submit.prevent="save" class="space-y-4">
                    <input v-model="form.name" type="text" placeholder="Nombre completo" class="w-full rounded-xl border-zinc-300" required>
                    <input v-model="form.document" type="text" placeholder="DNI / RUC" class="w-full rounded-xl border-zinc-300">
                    <input v-model="form.phone" type="text" placeholder="Teléfono" class="w-full rounded-xl border-zinc-300">
                    <input v-model="form.email" type="email" placeholder="Correo" class="w-full rounded-xl border-zinc-300">
                    <input v-model="form.birthday" type="date" class="w-full rounded-xl border-zinc-300">

                    <label class="flex items-center gap-2 text-sm font-bold">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-zinc-300">
                        Cliente activo
                    </label>

                    <button
                    type="submit"
                    class="w-full bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800"
                    >
                    {{ editingCustomer?.id ? 'Actualizar cliente' : 'Guardar cliente' }}
                    </button>

                    <button
                        v-if="editingCustomer"
                        type="button"
                        @click="resetForm"
                        class="w-full bg-zinc-200 text-zinc-800 rounded-xl py-3 font-bold hover:bg-zinc-300"
                    >
                        Cancelar edición
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                    <h3 class="text-xl font-bold">
                        Lista de clientes
                    </h3>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nombre, documento o teléfono"
                        class="rounded-xl border-zinc-300 w-full md:w-80"
                    >
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-zinc-500">
                                <th class="py-3">Cliente</th>
                                <th>Documento</th>
                                <th>Teléfono</th>
                                <th>Puntos</th>
                                <th>Total gastado</th>
                                <th>Estado</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="customer in filteredCustomers" :key="customer.id" class="border-b">
                                <td class="py-3">
                                    <p class="font-bold">{{ customer.name }}</p>
                                    <p class="text-xs text-zinc-500">{{ customer.email ?? 'Sin correo' }}</p>
                                </td>

                                <td>{{ customer.document ?? '-' }}</td>
                                <td>{{ customer.phone ?? '-' }}</td>
                                <td class="font-bold text-purple-600">{{ customer.points }}</td>
                                <td class="font-bold text-green-600">S/ {{ money(customer.total_spent) }}</td>

                                <td>
                                    <span
                                        class="px-2 py-1 rounded-lg text-xs font-bold"
                                        :class="customer.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                    >
                                        {{ customer.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>

                                <td class="text-right space-x-2">
                                    <button @click="editCustomer(customer)" class="bg-blue-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-blue-700">
                                        Editar
                                    </button>

                                    <button @click="deleteCustomer(customer)" class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-red-700">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="filteredCustomers.length === 0">
                                <td colspan="7" class="py-8 text-center text-zinc-500">
                                    No hay clientes encontrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
