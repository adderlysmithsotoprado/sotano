<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    cashRegister: Object,
    todaySales: Number,
    sales: Array,
    cashIncomes: Number,
    cashExpenses: Number,
    expectedCash: Number,
});

const openForm = useForm({
    opening_amount: '',
});

const closeForm = useForm({
    closing_amount: '',
});

const movementForm = useForm({
    cash_register_id: props.cashRegister?.id ?? '',
    type: 'ingreso',
    amount: '',
    reason: '',
});

const formatDateTime = (date) => {
    if (!date) return 'Sin fecha';

    return new Date(date).toLocaleString('es-PE', {
        timeZone: 'America/Lima',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
};

const money = (value) => {
    return Number(value || 0).toFixed(2);
};

const paymentSummary = computed(() => {
    const summary = {
        efectivo: 0,
        yape: 0,
        plin: 0,
        tarjeta: 0,
        transferencia: 0,
    };

    props.sales.forEach((sale) => {
        sale.payments?.forEach((payment) => {
            if (summary[payment.method] !== undefined) {
                summary[payment.method] += Number(payment.amount || 0);
            }
        });
    });

    return summary;
});

const openCash = () => {
    openForm.post(route('caja.open'), {
        preserveScroll: true,
        onSuccess: () => openForm.reset(),
    });
};

const closeCash = () => {
    if (confirm('¿Seguro que deseas cerrar la caja?')) {
        closeForm.post(route('caja.close', props.cashRegister.id), {
            preserveScroll: true,
            onSuccess: () => closeForm.reset(),
        });
    }
};

const saveMovement = () => {
    movementForm.cash_register_id = props.cashRegister.id;

    movementForm.post(route('caja.movimientos.store'), {
        preserveScroll: true,
        onSuccess: () => {
            movementForm.reset();
            movementForm.type = 'ingreso';
        },
    });
};
</script>

<template>
    <Head title="Caja" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                💰 Caja
            </h2>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-4">
                    Estado de Caja
                </h3>

                <div v-if="cashRegister" class="space-y-4">
                    <div class="p-4 rounded-xl bg-green-100 text-green-700 font-bold">
                        Caja Abierta
                    </div>

                    <p><strong>Monto inicial:</strong> S/ {{ money(cashRegister.opening_amount) }}</p>
                    <p><strong>Ventas del día:</strong> S/ {{ money(todaySales) }}</p>
                    <p><strong>Ingresos:</strong> S/ {{ money(cashIncomes) }}</p>
                    <p><strong>Egresos:</strong> S/ {{ money(cashExpenses) }}</p>
                    <p><strong>Esperado:</strong> S/ {{ money(expectedCash) }}</p>
                    <p><strong>Abierta desde:</strong> {{ formatDateTime(cashRegister.opened_at) }}</p>
                </div>

                <div v-else class="space-y-4">
                    <div class="p-4 rounded-xl bg-red-100 text-red-700 font-bold">
                        Caja Cerrada
                    </div>

                    <p class="text-zinc-500">
                        Debes abrir caja antes de iniciar operaciones.
                    </p>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl shadow p-6">
                <div v-if="!cashRegister">
                    <h3 class="text-xl font-bold mb-4">
                        Abrir Caja
                    </h3>

                    <form @submit.prevent="openCash" class="space-y-4 max-w-md">
                        <input
                            v-model="openForm.opening_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Monto inicial"
                            class="w-full rounded-lg border-zinc-300"
                        />
                        <div
    v-if="closeForm.closing_amount !== ''"
    class="rounded-xl p-4"
    :class="Number(closeForm.closing_amount) - Number(expectedCash) === 0
        ? 'bg-green-100 text-green-700'
        : 'bg-red-100 text-red-700'"
>
    <p class="font-black">
        Diferencia:
        S/ {{ money(Number(closeForm.closing_amount) - Number(expectedCash)) }}
    </p>
</div>

                        <button
                            type="submit"
                            class="bg-black text-white rounded-xl px-6 py-3 font-bold hover:bg-zinc-800"
                        >
                            Abrir Caja
                        </button>
                    </form>
                </div>

                <div v-else>
                    <h3 class="text-xl font-bold mb-4">
                        Resumen de Caja
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div class="rounded-xl bg-zinc-100 p-4">
                            <p class="text-sm text-zinc-500">Inicial</p>
                            <p class="text-xl font-black">S/ {{ money(cashRegister.opening_amount) }}</p>
                        </div>

                        <div class="rounded-xl bg-zinc-100 p-4">
                            <p class="text-sm text-zinc-500">Ventas</p>
                            <p class="text-xl font-black">S/ {{ money(todaySales) }}</p>
                        </div>

                        <div class="rounded-xl bg-green-100 p-4 text-green-700">
                            <p class="text-sm">Ingresos</p>
                            <p class="text-xl font-black">S/ {{ money(cashIncomes) }}</p>
                        </div>

                        <div class="rounded-xl bg-red-100 p-4 text-red-700">
                            <p class="text-sm">Egresos</p>
                            <p class="text-xl font-black">S/ {{ money(cashExpenses) }}</p>
                        </div>

                        <div class="rounded-xl bg-black p-4 text-white">
                            <p class="text-sm">Esperado</p>
                            <p class="text-xl font-black">S/ {{ money(expectedCash) }}</p>
                        </div>
                    </div>

                    <h4 class="font-black mb-3">
                        Desglose por método de pago
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-6">
                        <div class="rounded-xl bg-green-100 p-4 text-green-700">
                            <p class="text-xs font-bold">💵 Efectivo</p>
                            <p class="text-xl font-black">S/ {{ money(paymentSummary.efectivo) }}</p>
                        </div>

                        <div class="rounded-xl bg-purple-100 p-4 text-purple-700">
                            <p class="text-xs font-bold">📱 Yape</p>
                            <p class="text-xl font-black">S/ {{ money(paymentSummary.yape) }}</p>
                        </div>

                        <div class="rounded-xl bg-blue-100 p-4 text-blue-700">
                            <p class="text-xs font-bold">📱 Plin</p>
                            <p class="text-xl font-black">S/ {{ money(paymentSummary.plin) }}</p>
                        </div>

                        <div class="rounded-xl bg-zinc-100 p-4 text-zinc-700">
                            <p class="text-xs font-bold">💳 Tarjeta</p>
                            <p class="text-xl font-black">S/ {{ money(paymentSummary.tarjeta) }}</p>
                        </div>

                        <div class="rounded-xl bg-orange-100 p-4 text-orange-700">
                            <p class="text-xs font-bold">🏦 Transferencia</p>
                            <p class="text-xl font-black">S/ {{ money(paymentSummary.transferencia) }}</p>
                        </div>
                    </div>

                    <div class="border-t pt-6">
                        <h4 class="font-black mb-3">
                            Registrar ingreso / egreso
                        </h4>

                        <form @submit.prevent="saveMovement" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <select
                                v-model="movementForm.type"
                                class="rounded-lg border-zinc-300"
                            >
                                <option value="ingreso">Ingreso</option>
                                <option value="egreso">Egreso</option>
                            </select>

                            <input
                                v-model="movementForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="Monto"
                                class="rounded-lg border-zinc-300"
                                required
                            />

                            <input
                                v-model="movementForm.reason"
                                type="text"
                                placeholder="Motivo"
                                class="rounded-lg border-zinc-300"
                                required
                            />

                            <button
                                type="submit"
                                class="bg-black text-white rounded-xl px-5 py-3 font-bold hover:bg-zinc-800"
                                :disabled="movementForm.processing"
                            >
                                Guardar movimiento
                            </button>
                        </form>

                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-zinc-500">
                                        <th class="py-3">Fecha</th>
                                        <th>Tipo</th>
                                        <th>Usuario</th>
                                        <th>Motivo</th>
                                        <th class="text-right">Monto</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="movement in cashRegister.movements"
                                        :key="movement.id"
                                        class="border-b"
                                    >
                                        <td class="py-3">
                                            {{ formatDateTime(movement.created_at) }}
                                        </td>

                                        <td>
                                            <span
                                                class="px-2 py-1 rounded-lg text-xs font-bold uppercase"
                                                :class="movement.type === 'ingreso'
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-red-100 text-red-700'"
                                            >
                                                {{ movement.type }}
                                            </span>
                                        </td>

                                        <td>
                                            {{ movement.user?.name ?? 'Sistema' }}
                                        </td>

                                        <td>
                                            {{ movement.reason }}
                                        </td>

                                        <td
                                            class="text-right font-black"
                                            :class="movement.type === 'ingreso' ? 'text-green-600' : 'text-red-600'"
                                        >
                                            {{ movement.type === 'ingreso' ? '+' : '-' }} S/ {{ money(movement.amount) }}
                                        </td>
                                    </tr>

                                    <tr v-if="cashRegister.movements.length === 0">
                                        <td colspan="5" class="py-6 text-center text-zinc-500">
                                            No hay movimientos registrados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form @submit.prevent="closeCash" class="space-y-4 max-w-md">
                        <input
                            v-model="closeForm.closing_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Monto final contado"
                            class="w-full rounded-lg border-zinc-300"
                        />

                        <button
                            type="submit"
                            class="bg-red-600 text-white rounded-xl px-6 py-3 font-bold hover:bg-red-700"
                        >
                            Cerrar Caja
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-white rounded-2xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">
                    Ventas del día
                </h3>

                <span class="text-sm text-zinc-500">
                    {{ sales.length }} venta(s)
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-zinc-500">
                            <th class="py-3">Pedido</th>
                            <th>Mesa</th>
                            <th>Cajero</th>
                            <th>Fecha y hora</th>
                            <th>Pago</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Ticket</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="sale in sales"
                            :key="sale.id"
                            class="border-b"
                        >
                            <td class="py-3 font-bold">
                                #{{ sale.id }}
                            </td>

                            <td>
                                {{ sale.table?.name ?? 'Sin mesa' }}
                            </td>

                            <td>
                                {{ sale.user?.name ?? 'Sin usuario' }}
                            </td>

                            <td>
                                {{ sale.paid_at_formatted }}
                            </td>

                            <td>
                                <span
                                    v-for="payment in sale.payments"
                                    :key="payment.id"
                                    class="block text-xs font-bold"
                                >
                                    {{ payment.method.toUpperCase() }}: S/ {{ money(payment.amount) }}
                                </span>
                            </td>

                            <td class="text-right font-bold">
                                S/ {{ money(sale.total) }}
                            </td>

                            <td class="text-right">
                                <a
                                    :href="route('ticket.show', sale.id)"
                                    target="_blank"
                                    class="bg-black text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-zinc-800"
                                >
                                    Ver ticket
                                </a>
                            </td>
                        </tr>

                        <tr v-if="sales.length === 0">
                            <td colspan="7" class="py-8 text-center text-zinc-500">
                                Aún no hay ventas registradas hoy.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
