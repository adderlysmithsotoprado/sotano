<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    table: Object,
});

const form = useForm({
    name: props.table.name,
    capacity: props.table.capacity,
    status: props.table.status,
    reserved_by: props.table.reserved_by ?? '',
    reserved_at: props.table.reserved_at ?? '',
    is_active: props.table.is_active ?? true,
});

const submit = () => {
    form.put(route('mesas.update', props.table.id));
};
</script>

<template>
    <Head title="Editar Mesa" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                ✏️ Editar Mesa
            </h2>
        </template>

        <div class="max-w-xl bg-white rounded-2xl shadow p-6">
            <form @submit.prevent="submit" class="space-y-4">
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="Nombre de mesa"
                    class="w-full rounded-lg border-zinc-300"
                />

                <input
                    v-model="form.capacity"
                    type="number"
                    min="1"
                    placeholder="Capacidad"
                    class="w-full rounded-lg border-zinc-300"
                />

                <select
                    v-model="form.status"
                    class="w-full rounded-lg border-zinc-300"
                >
                    <option value="libre">Libre</option>
                    <option value="ocupada">Ocupada</option>
                    <option value="reservada">Reservada</option>
                </select>

                <div
                    v-if="form.status === 'reservada'"
                    class="space-y-4 rounded-xl bg-orange-50 border border-orange-200 p-4"
                >
                    <input
                        v-model="form.reserved_by"
                        type="text"
                        placeholder="Reservado por"
                        class="w-full rounded-lg border-zinc-300"
                    />

                    <input
                        v-model="form.reserved_at"
                        type="datetime-local"
                        class="w-full rounded-lg border-zinc-300"
                    />
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="flex-1 bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800"
                    >
                        Guardar cambios
                    </button>

                    <Link
                        :href="route('mesas')"
                        class="flex-1 bg-zinc-200 text-zinc-800 rounded-xl py-3 font-bold text-center hover:bg-zinc-300"
                    >
                        Volver
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
