<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    order: Object,
});

const money = (value) => Number(value || 0).toFixed(2);
</script>

<template>
    <Head title="Detalle de Venta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🧾 Venta #{{ order.id }}
            </h2>
        </template>

        <div class="bg-white rounded-2xl shadow p-6">

            <div class="grid md:grid-cols-2 gap-4">

                <div>
                    <p><strong>Mesa:</strong> {{ order.table?.name ?? 'Sin mesa' }}</p>
                    <p><strong>Cajero:</strong> {{ order.user?.name ?? 'Sin usuario' }}</p>
                    <p><strong>Fecha:</strong> {{ order.paid_at_formatted }}</p>
                </div>

                <div>
                    <p>
                        <strong>Cliente:</strong>
                        {{ order.customer?.name ?? 'Cliente General' }}
                    </p>

                    <p>
                        <strong>Documento:</strong>
                        {{ order.customer?.document ?? '-' }}
                    </p>

                    <p>
                        <strong>Teléfono:</strong>
                        {{ order.customer?.phone ?? '-' }}
                    </p>
                </div>

            </div>

            <div class="mt-6 border-t pt-4">

                <h3 class="font-black mb-3">
                    Productos
                </h3>

                <div
                    v-for="item in order.items"
                    :key="item.id"
                    class="flex justify-between border-b py-2"
                >
                    <span>
                        {{ item.quantity }} x {{ item.product_name }}
                    </span>

                    <span>
                        S/ {{ money(item.subtotal) }}
                    </span>
                </div>

            </div>

            <div class="mt-6 border-t pt-4">

                <h3 class="font-black mb-3">
                    Pagos
                </h3>

                <div
                    v-for="payment in order.payments"
                    :key="payment.id"
                    class="flex justify-between border-b py-2"
                >
                    <span>

                        {{ payment.method.toUpperCase() }}

                        <span v-if="payment.operation_number">
                            ({{ payment.operation_number }})
                        </span>

                    </span>

                    <span>
                        S/ {{ money(payment.amount) }}
                    </span>

                </div>

            </div>

            <div class="mt-6 border-t pt-4 space-y-2">

                <div class="flex justify-between">
                    <span>Total</span>
                    <strong>S/ {{ money(order.total) }}</strong>
                </div>

                <div class="flex justify-between">
                    <span>Recibido</span>
                    <strong>S/ {{ money(order.amount_received) }}</strong>
                </div>

                <div class="flex justify-between">
                    <span>Vuelto</span>
                    <strong class="text-green-700">
                        S/ {{ money(order.change_amount) }}
                    </strong>
                </div>

            </div>

            <div class="mt-8 flex gap-3">

                <Link
                    :href="route('ventas.historial')"
                    class="bg-zinc-200 px-5 py-3 rounded-xl font-bold"
                >
                    Volver
                </Link>

                <Link
                    :href="route('ticket.show', order.id)"
                    class="bg-black text-white px-5 py-3 rounded-xl font-bold"
                >
                    Ver Ticket
                </Link>

            </div>

        </div>

    </AuthenticatedLayout>
</template>
