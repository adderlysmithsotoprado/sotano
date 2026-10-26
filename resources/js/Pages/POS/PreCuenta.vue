<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ThermalTicket from '@/Components/ThermalTicket.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({
    order: Object,
    business: Object,
});

const printTicket = () => {
    window.print();
};

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head title="Pre-Cuenta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800 print:hidden">
                🧾 Pre-Cuenta
            </h2>
        </template>

        <div class="mx-auto bg-white rounded-2xl shadow p-6 w-fit print:shadow-none print:rounded-none print:p-0">
            <ThermalTicket
            title="TICKET DE VENTA"
            :order="order"
            :business="$page.props.business"
            :show-payments="true"
            />

            <div class="mt-6 flex gap-3 print:hidden">
                <button
                    @click="printTicket"
                    class="flex-1 bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800"
                >
                    Imprimir
                </button>

                <Link
                    :href="route('pos.show', order.restaurant_table_id)"
                    class="flex-1 bg-zinc-200 text-zinc-800 rounded-xl py-3 font-bold text-center hover:bg-zinc-300"
                >
                    Volver
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
