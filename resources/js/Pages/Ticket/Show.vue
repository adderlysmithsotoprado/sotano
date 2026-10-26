<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ThermalTicket from '@/Components/ThermalTicket.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { invoke } from '@tauri-apps/api/core';
import { onMounted, ref } from 'vue';

const props = defineProps({
    order: Object,
    business: Object,
});

const printing = ref(false);
const printError = ref('');

const ticketLines = () => {
    const lines = [];

    lines.push(`PEDIDO: #${props.order.id}`);
    lines.push(`MESA: ${props.order.table?.name ?? 'SIN MESA'}`);
    lines.push(`ATIENDE: ${props.order.user?.name ?? 'ADMINISTRADOR'}`);
    lines.push('--------------------------------');

    props.order.items?.forEach(item => {
        lines.push(
            `${item.quantity} x ${String(item.product_name).toUpperCase()}`
        );

        lines.push(
            `   P.U. S/ ${Number(item.unit_price || 0).toFixed(2)}  TOTAL S/ ${Number(item.subtotal || 0).toFixed(2)}`
        );

        item.modifiers?.forEach(modifier => {
            lines.push(`   + ${modifier.modifier_name}`);
        });

        if (item.notes) {
            lines.push(`   NOTA: ${item.notes}`);
        }
    });

    lines.push('--------------------------------');

    props.order.payments?.forEach(payment => {
        const operation = payment.operation_number
            ? ` (${payment.operation_number})`
            : '';

        lines.push(
            `${String(payment.method).toUpperCase()}${operation}: S/ ${Number(payment.amount || 0).toFixed(2)}`
        );
    });

    lines.push('--------------------------------');
    lines.push(`TOTAL: S/ ${Number(props.order.total || 0).toFixed(2)}`);

    if (Number(props.order.amount_received || 0) > Number(props.order.total || 0)) {
        lines.push(
            `RECIBIDO: S/ ${Number(props.order.amount_received || 0).toFixed(2)}`
        );
    }

    if (Number(props.order.change_amount || 0) > 0) {
        lines.push(
            `VUELTO: S/ ${Number(props.order.change_amount || 0).toFixed(2)}`
        );
    }

    lines.push('--------------------------------');
    lines.push(
        props.business?.ticket_message ?? 'GRACIAS POR SU PREFERENCIA'
    );
    lines.push('VUELVA PRONTO');

    return lines;
};

const printTicket = async () => {
    if (printing.value) return;

    const printerName = props.business?.ticket_printer ?? '';

    if (!printerName) {
        printError.value =
            'No existe una impresora de Ticket de Caja configurada.';
        return;
    }

    printing.value = true;
    printError.value = '';

    try {
        await invoke('print_command', {
            printerName,
            title: props.business?.business_name ?? 'SOTANO RESTOBAR',
            table: props.order.table?.name ?? 'SIN MESA',
            items: ticketLines(),
        });

        router.post(
            route('ticket.printed', props.order.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    printing.value = false;
                },
            }
        );
    } catch (error) {
        console.error(error);

        printError.value =
            typeof error === 'string'
                ? error
                : 'No se pudo imprimir el ticket. Revisa la impresora.';

        printing.value = false;
    }
};

onMounted(() => {
    if (!props.order.ticket_printed_at) {
        setTimeout(() => {
            printTicket();
        }, 500);
    }
});
</script>

<template>
    <Head title="Ticket" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800 print:hidden">
                🧾 Ticket de Venta
            </h2>
        </template>

        <div class="mx-auto bg-white rounded-2xl shadow p-6 w-fit print:shadow-none print:rounded-none print:p-0">

            <ThermalTicket
                title="TICKET DE VENTA"
                :order="order"
                :business="$page.props.business"
                :show-payments="true"
            />

            <!-- AQUÍ VA -->
            <div
                v-if="printError"
                class="mt-4 rounded-xl border border-red-300 bg-red-50 p-3 text-sm font-bold text-red-700 print:hidden"
            >
                {{ printError }}
            </div>

            <div class="mt-6 flex gap-3 print:hidden">

                <button
                    type="button"
                    @click="printTicket"
                    :disabled="printing"
                    class="flex-1 bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800 disabled:opacity-50"
                >
                    {{ printing ? 'Imprimiendo...' : 'Reimprimir ticket' }}
                </button>

                <Link
                    :href="route('caja')"
                    class="flex-1 bg-zinc-200 text-zinc-800 rounded-xl py-3 font-bold text-center hover:bg-zinc-300"
                >
                    Volver
                </Link>

            </div>

        </div>

    </AuthenticatedLayout>
</template>
