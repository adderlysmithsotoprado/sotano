<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({
    users: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'cajero',
});

const submit = () => {
    form.post(route('usuarios.store'), {
        preserveScroll: true,

        onSuccess: () => {
            alert('Usuario registrado correctamente');
            form.reset();
        },

        onError: (errors) => {
            console.log(errors);
            alert(JSON.stringify(errors));
        },
    });
};

const updateRole = (user, role) => {
    router.put(route('usuarios.update', user.id), {
        role: role,
    }, {
        preserveScroll: true,
    });
};

const deleteUser = (user) => {
    if (confirm('¿Eliminar este usuario?')) {
        router.delete(route('usuarios.destroy', user.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                👤 Usuarios
            </h2>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-4">
                    Nuevo Usuario
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    <input v-model="form.name" type="text" placeholder="Nombre"
                        class="w-full rounded-lg border-zinc-300" />

                    <input v-model="form.email" type="email" placeholder="Correo"
                        class="w-full rounded-lg border-zinc-300" />

                    <input v-model="form.password" type="password" placeholder="Contraseña"
                        class="w-full rounded-lg border-zinc-300" />

                    <select v-model="form.role" class="w-full rounded-lg border-zinc-300">
                        <option value="admin">Administrador</option>
                        <option value="cajero">Cajero</option>
                        <option value="mozo">Mozo</option>
                        <option value="cocina">Cocina</option>
                        <option value="bar">Bar</option>
                    </select>

                    <button class="w-full bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800">
                        Guardar Usuario
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-4">
                    Lista de Usuarios
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-zinc-500">
                                <th class="py-3">Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="user in users" :key="user.id" class="border-b">
                                <td class="py-3 font-bold">{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <select
                                        :value="user.role"
                                        @change="updateRole(user, $event.target.value)"
                                        class="rounded-lg border-zinc-300 text-sm"
                                    >
                                        <option value="admin">Admin</option>
                                        <option value="cajero">Cajero</option>
                                        <option value="mozo">Mozo</option>
                                        <option value="cocina">Cocina</option>
                                        <option value="bar">Bar</option>
                                    </select>
                                </td>
                                <td class="text-right">
                                    <button
                                        @click="deleteUser(user)"
                                        class="bg-red-600 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-red-700"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="users.length === 0">
                                <td colspan="4" class="py-8 text-center text-zinc-500">
                                    No hay usuarios registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
