<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { invoke } from '@tauri-apps/api/core';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps({
    order: Object,
    cashRegister: Object,
});

const paymentMode = ref('efectivo');
const processingPayment = ref(false);
const paymentError = ref('');
const paymentCompleted = ref(false);

const pendingTicket = ref({
    printerName: '',
    content: '',
});

const form = useForm({
    payments: [
        {
            method: 'efectivo',
            amount: props.order.total,
            operation_number: '',
        },
    ],
});

const totalPaid = computed(() => {
    return form.payments.reduce((sum, payment) => {
        return sum + Number(payment.amount || 0);
    }, 0);
});

const change = computed(() => {
    if (paymentMode.value !== 'efectivo') {
        return 0;
    }

    return Math.max(0, totalPaid.value - Number(props.order.total));
});

const pendingAmount = computed(() => {
    return Number(props.order.total) - totalPaid.value;
});

const setSinglePayment = (method) => {
    paymentMode.value = method;

    form.payments = [
        {
            method: method,
            amount: props.order.total,
            operation_number: '',
        },
    ];
};

const setMixedPayment = () => {
    paymentMode.value = 'mixto';

    form.payments = [
        { method: 'efectivo', amount: 0, operation_number: '' },
        { method: 'tarjeta', amount: 0, operation_number: '' },
        { method: 'yape', amount: 0, operation_number: '' },
        { method: 'plin', amount: 0, operation_number: '' },
        { method: 'transferencia', amount: 0, operation_number: '' },
    ];
};

const methodLabel = (method) => {
    if (method === 'efectivo') return '💵 Efectivo';
    if (method === 'tarjeta') return '💳 Tarjeta';
    if (method === 'yape') return '📱 Yape';
    if (method === 'plin') return '📱 Plin';
    if (method === 'transferencia') return '🏦 Transferencia';

    return method;
};

const submitPayment = async () => {
    if (processingPayment.value || paymentCompleted.value) {
        return;
    }

    const validPayments = form.payments.filter(payment =>
        Number(payment.amount) > 0
    );

    if (validPayments.length === 0) {
        alert('Debes ingresar un monto.');
        return;
    }

    const total = validPayments.reduce((sum, payment) => {
        return sum + Number(payment.amount);
    }, 0);

    if (Number(total.toFixed(2)) < Number(props.order.total)) {
        alert('El monto pagado es menor al total del pedido.');
        return;
    }

    processingPayment.value = true;
    paymentError.value = '';

    try {
        const response = await axios.post(
            route('pos.close', props.order.id),
            {
                payments: validPayments,
            },
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );

        const data = response.data;

        paymentCompleted.value = true;

        pendingTicket.value = {
            printerName: data.printer_name ?? '',
            content: data.ticket_content ?? '',
        };

        if (!pendingTicket.value.printerName) {
            paymentError.value =
                'El pago se registró, pero no existe una impresora de Ticket de Caja configurada.';

            return;
        }

        await invoke('print_ticket', {
            printerName: pendingTicket.value.printerName,
            content: pendingTicket.value.content,
        });

        router.visit(data.redirect_url ?? route('mesas'), {
            replace: true,
        });
    } catch (error) {
        console.error(error);

        if (error?.response?.data?.payment_saved) {
            paymentCompleted.value = true;

            paymentError.value =
                error.response.data.message ??
                'El pago fue registrado, pero no se pudo imprimir el ticket.';

            return;
        }

        paymentError.value =
            error?.response?.data?.message ??
            (typeof error === 'string'
                ? error
                : 'No se pudo completar el pago.');
    } finally {
        processingPayment.value = false;
    }
};
const retryTicketPrint = async () => {
    if (
        processingPayment.value ||
        !pendingTicket.value.printerName ||
        !pendingTicket.value.content
    ) {
        return;
    }

    processingPayment.value = true;
    paymentError.value = '';

    try {
        await invoke('print_ticket', {
            printerName: pendingTicket.value.printerName,
            content: pendingTicket.value.content,
        });

        router.visit(route('mesas'), {
            replace: true,
        });
    } catch (error) {
        console.error(error);

        paymentError.value =
            typeof error === 'string'
                ? error
                : 'El pago ya fue registrado, pero el ticket no pudo imprimirse.';
    } finally {
        processingPayment.value = false;
    }
};
</script>

<template>
    <Head title="Cobrar Pedido" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                💳 Cobrar Pedido #{{ order.id }}
            </h2>
        </template>
        <div
    v-if="paymentError"
    class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 font-bold text-red-700"
>
    <p>{{ paymentError }}</p>

    <button
        v-if="paymentCompleted && pendingTicket.printerName"
        type="button"
        @click="retryTicketPrint"
        :disabled="processingPayment"
        class="mt-3 rounded-lg bg-red-700 px-4 py-2 text-sm font-black text-white disabled:opacity-50"
    >
        {{ processingPayment ? 'Reintentando...' : 'Reintentar impresión' }}
    </button>

    <Link
        v-if="paymentCompleted"
        :href="route('mesas')"
        class="ml-2 inline-block rounded-lg bg-zinc-800 px-4 py-2 text-sm font-black text-white"
    >
        Ir a Mesas
    </Link>
</div>

        <div
            v-if="$page.props.errors?.order || $page.props.errors?.payment"
            class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl font-bold"
        >
            {{ $page.props.errors.order || $page.props.errors.payment }}
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-black mb-4">
                    Resumen del pedido
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-zinc-100 rounded-xl p-4">
                        <p class="text-sm text-zinc-500">Mesa</p>
                        <p class="text-xl font-black">
                            {{ order.table?.name ?? 'Sin mesa' }}
                        </p>
                    </div>

                    <div class="bg-zinc-100 rounded-xl p-4">
                        <p class="text-sm text-zinc-500">Pedido</p>
                        <p class="text-xl font-black">
                            #{{ order.id }}
                        </p>
                    </div>

                    <div class="bg-green-100 rounded-xl p-4">
                        <p class="text-sm text-green-700">Total</p>
                        <p class="text-3xl font-black text-green-700">
                            S/ {{ order.total }}
                        </p>
                    </div>
                </div>

                <div class="divide-y">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="py-4 flex justify-between gap-4"
                    >
                        <div>
                            <p class="font-bold">
                                {{ item.quantity }} x {{ item.product_name }}
                            </p>

                            <div
                                v-if="item.modifiers && item.modifiers.length > 0"
                                class="mt-1 text-sm text-zinc-600"
                            >
                                <p
                                    v-for="modifier in item.modifiers"
                                    :key="modifier.id"
                                >
                                    + {{ modifier.modifier_name }}
                                    <span v-if="Number(modifier.extra_price) > 0">
                                        (+S/ {{ modifier.extra_price }})
                                    </span>
                                </p>
                            </div>

                            <p
                                v-if="item.notes"
                                class="mt-1 text-sm text-orange-600 font-bold"
                            >
                                Nota: {{ item.notes }}
                            </p>
                        </div>

                        <p class="font-black">
                            S/ {{ item.subtotal }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 h-fit">
                <h3 class="text-xl font-black mb-4">
                    Método de pago
                </h3>

                <div
                    v-if="!cashRegister"
                    class="mb-4 bg-red-100 text-red-700 rounded-xl p-4 font-bold"
                >
                    Debes abrir caja antes de cobrar.
                </div>

                <div class="grid grid-cols-2 gap-2 mb-5">
                    <button
                        type="button"
                        @click="setSinglePayment('efectivo')"
                        class="rounded-xl py-3 font-bold"
                        :class="paymentMode === 'efectivo'
                            ? 'bg-black text-white'
                            : 'bg-zinc-100 text-zinc-700'"
                    >
                        💵 Efectivo
                    </button>

                    <button
                        type="button"
                        @click="setSinglePayment('tarjeta')"
                        class="rounded-xl py-3 font-bold"
                        :class="paymentMode === 'tarjeta'
                            ? 'bg-black text-white'
                            : 'bg-zinc-100 text-zinc-700'"
                    >
                        💳 Tarjeta
                    </button>

                    <button
                        type="button"
                        @click="setSinglePayment('yape')"
                        class="rounded-xl py-3 font-bold"
                        :class="paymentMode === 'yape'
                            ? 'bg-black text-white'
                            : 'bg-zinc-100 text-zinc-700'"
                    >
                        📱 Yape
                    </button>

                    <button
                        type="button"
                        @click="setSinglePayment('plin')"
                        class="rounded-xl py-3 font-bold"
                        :class="paymentMode === 'plin'
                            ? 'bg-black text-white'
                            : 'bg-zinc-100 text-zinc-700'"
                    >
                        📱 Plin
                    </button>

                    <button
                        type="button"
                        @click="setSinglePayment('transferencia')"
                        class="rounded-xl py-3 font-bold"
                        :class="paymentMode === 'transferencia'
                            ? 'bg-black text-white'
                            : 'bg-zinc-100 text-zinc-700'"
                    >
                        🏦 Transferencia
                    </button>

                    <button
                        type="button"
                        @click="setMixedPayment"
                        class="rounded-xl py-3 font-bold"
                        :class="paymentMode === 'mixto'
                            ? 'bg-black text-white'
                            : 'bg-zinc-100 text-zinc-700'"
                    >
                        🔀 Mixto
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(payment, index) in form.payments"
                        :key="index"
                        class="rounded-xl border p-4"
                    >
                        <p class="font-black mb-3">
                            {{ methodLabel(payment.method) }}
                        </p>

                        <input
                            v-model="payment.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border-zinc-300"
                            placeholder="Monto"
                        />

                        <input
                            v-if="payment.method !== 'efectivo'"
                            v-model="payment.operation_number"
                            type="text"
                            class="mt-3 w-full rounded-lg border-zinc-300"
                            placeholder="N° operación"
                        />
                    </div>
                </div>

                <div class="mt-5 bg-zinc-100 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between font-bold">
                        <span>Total pedido</span>
                        <span>S/ {{ order.total }}</span>
                    </div>

                    <div class="flex justify-between font-bold">
                        <span>Total recibido</span>
                        <span>S/ {{ totalPaid.toFixed(2) }}</span>
                    </div>

                    <div
                        v-if="paymentMode === 'efectivo'"
                        class="flex justify-between font-bold text-green-700"
                    >
                        <span>Vuelto</span>
                        <span>S/ {{ change.toFixed(2) }}</span>
                    </div>

                    <div
                        v-if="paymentMode === 'mixto'"
                        class="flex justify-between font-bold"
                        :class="pendingAmount === 0 ? 'text-green-700' : 'text-red-600'"
                    >
                        <span>Pendiente</span>
                        <span>S/ {{ pendingAmount.toFixed(2) }}</span>
                    </div>
                </div>

                <button
    type="button"
    @click="submitPayment"
    :disabled="!cashRegister || processingPayment || paymentCompleted"
    class="mt-5 w-full bg-green-600 hover:bg-green-700 text-white rounded-xl py-4 font-black disabled:opacity-50 disabled:cursor-not-allowed"
>
    <span v-if="processingPayment">
        Procesando e imprimiendo...
    </span>

    <span v-else-if="paymentCompleted">
        Pago registrado
    </span>

    <span v-else>
        Confirmar pago
    </span>
</button>

                <Link
                    :href="route('pos.show', order.restaurant_table_id)"
                    class="mt-3 block w-full bg-zinc-200 text-zinc-800 rounded-xl py-4 font-black text-center hover:bg-zinc-300"
                >
                    Volver al POS
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

