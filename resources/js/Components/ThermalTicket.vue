<script setup>
const formatDate = (date) => {
    if (!date) return '';

    return new Date(date).toLocaleDateString('es-PE', {
        timeZone: 'America/Lima',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatTime = (date) => {
    if (!date) return '';

    return new Date(date).toLocaleTimeString('es-PE', {
        timeZone: 'America/Lima',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
};

defineProps({
    title: String,
    order: Object,
    business: { type: Object, default: () => ({}) },
    showPayments: { type: Boolean, default: false },
});
</script>

<template>
    <div class="thermal-ticket text-black">
        <div class="text-center border-b border-dashed border-black pb-2">
            <img
                v-if="business?.logo_path"
                :src="'/storage/' + business.logo_path"
                class="w-20 mx-auto mb-1 object-contain"
            />

            <img
                v-else
                src="/images/logo.png"
                class="w-20 mx-auto mb-1 object-contain"
            />

            <h1 class="text-sm font-black">
                {{ business?.business_name ?? 'SOTANO RESTOBAR' }}
            </h1>

            <p v-if="business?.ruc" class="text-[10px]">RUC: {{ business.ruc }}</p>
            <p v-if="business?.address" class="text-[10px]">{{ business.address }}</p>
            <p v-if="business?.phone" class="text-[10px]">Tel: {{ business.phone }}</p>

            <p class="text-xs font-black mt-1">{{ title }}</p>
        </div>

        <div class="text-xs mt-2 space-y-1">
            <p><strong>Pedido:</strong> #{{ order.id }}</p>
            <p><strong>Mesa:</strong> {{ order.table?.name ?? 'Sin mesa' }}</p>
            <p><strong>Atendió:</strong> {{ order.user?.name ?? 'Sin usuario' }}</p>
            <p><strong>Fecha:</strong> {{ formatDate(order.created_at) }}</p>
            <p><strong>Hora:</strong> {{ formatTime(order.created_at) }}</p>
        </div>

        <div class="border-t border-b border-dashed border-black my-2 py-2">
            <div class="flex justify-between text-[10px] font-black border-b border-dashed border-black pb-1 mb-1">
                <span>DESCRIPCION</span>
                <span>TOTAL</span>
            </div>

            <div v-for="item in order.items" :key="item.id" class="text-xs py-1">
                <div class="flex justify-between gap-2">
                    <div class="font-bold leading-tight">
                        {{ item.quantity }} x {{ item.product_name }}
                    </div>

                    <div class="text-right font-bold">
                        S/ {{ Number(item.subtotal || 0).toFixed(2) }}
                    </div>
                </div>

                <div class="text-[10px]">
                    P.U: S/ {{ Number(item.unit_price || 0).toFixed(2) }}
                </div>

                <div v-if="item.modifiers && item.modifiers.length > 0" class="ml-2 mt-1 text-[10px]">
                    <p v-for="modifier in item.modifiers" :key="modifier.id">
                        + {{ modifier.modifier_name }}
                        <span v-if="Number(modifier.extra_price) > 0">
                            S/ {{ modifier.extra_price }}
                        </span>
                    </p>
                </div>

                <p v-if="item.notes" class="ml-2 mt-1 text-[10px]">
                    Nota: {{ item.notes }}
                </p>

                <div v-if="Number(item.discount || 0) > 0" class="ml-2 mt-1 text-[10px] font-bold">
                    <p>{{ item.promotion_name }}</p>
                    <p>Descuento: -S/ {{ Number(item.discount || 0).toFixed(2) }}</p>
                </div>
            </div>
        </div>

        <div
            v-if="showPayments && order.payments && order.payments.length"
            class="border-b border-dashed border-black pb-2 mb-2 text-xs"
        >
            <p class="font-black mb-1">FORMA DE PAGO</p>

            <div
                v-for="payment in order.payments"
                :key="payment.id"
                class="flex justify-between"
            >
                <span>
                    {{ payment.method.toUpperCase() }}
                    <span v-if="payment.operation_number">
                        ({{ payment.operation_number }})
                    </span>
                </span>

                <span>S/ {{ Number(payment.amount || 0).toFixed(2) }}</span>
            </div>

            <div class="border-t border-dashed border-black mt-2 pt-2">
                <div class="flex justify-between font-black">
                    <span>TOTAL A PAGAR</span>
                    <span>S/ {{ Number(order.total || 0).toFixed(2) }}</span>
                </div>

                <div
                    v-if="Number(order.amount_received || 0) > Number(order.total || 0)"
                    class="flex justify-between"
                >
                    <span>EFECTIVO RECIBIDO</span>
                    <span>S/ {{ Number(order.amount_received || 0).toFixed(2) }}</span>
                </div>

                <div
                    v-if="Number(order.change_amount || 0) > 0"
                    class="flex justify-between font-black"
                >
                    <span>VUELTO</span>
                    <span>S/ {{ Number(order.change_amount || 0).toFixed(2) }}</span>
                </div>
            </div>
        </div>

        <div class="text-center text-xs mt-3">
            <p>{{ business?.ticket_message ?? 'Gracias por su preferencia' }}</p>
            <p>Vuelva pronto</p>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: 80mm auto;
        margin: 4mm;
    }

    body {
        background: white !important;
    }

    aside,
    header,
    nav,
    .print\:hidden {
        display: none !important;
    }

    .ml-72 {
        margin-left: 0 !important;
    }

    main {
        padding: 0 !important;
    }
}

.thermal-ticket {
    width: 72mm;
    font-family: monospace;
    font-size: 11px;
}
</style>
