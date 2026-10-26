<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({
    order: Object,
    area: String,
    items: Array,
    business: Object,
});

const printCommand = () => {
    window.print();
};

const formatDateTime = (date) => {
    if (!date) return '';

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

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head title="Comanda" />

    <div class="min-h-screen bg-zinc-100 p-6 print:bg-white print:p-0">
        <div class="mx-auto bg-white rounded-2xl shadow p-6 max-w-sm print:shadow-none print:rounded-none print:p-0 print:max-w-none">
            <div class="command text-black">
                <div class="text-center border-b border-dashed border-black pb-3">
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

                <h2 class="text-sm font-black">
                 {{ business?.business_name ?? 'SOTANO RESTOBAR' }}
                </h2>

                <p v-if="business?.ruc" class="text-[10px]">
                RUC: {{ business.ruc }}
                </p>

                <p v-if="business?.address" class="text-[10px]">
                {{ business.address }}
                </p>

                <p v-if="business?.phone" class="text-[10px]">
                Tel: {{ business.phone }}
                </p>

                <h1 class="text-xl font-black uppercase mt-2">
                COMANDA {{ area }}
                </h1>

                    <p class="text-sm font-bold mt-2">
                        Pedido #{{ order.id }}
                    </p>

                    <p class="text-sm">
                        Mesa: {{ order.table?.name ?? 'Sin mesa' }}
                    </p>
                </div>

                <div class="border-b border-dashed border-black my-3 py-2">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="py-2 text-sm"
                    >
                        <p class="font-black">
                            {{ item.quantity }} x {{ item.product_name }}
                        </p>

                        <div
                            v-if="item.modifiers && item.modifiers.length > 0"
                            class="ml-3 mt-1 text-xs"
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

                        <p v-if="item.notes" class="text-xs mt-1">
                            Nota: {{ item.notes }}
                        </p>
                    </div>

                    <div v-if="items.length === 0" class="text-center text-xs py-4">
                        Sin productos para esta área.
                    </div>
                </div>

                <div class="text-center text-xs mt-3 border-t border-dashed border-black pt-2">
                <p>
                {{ formatDateTime(order.created_at) }}
                </p>
                </div>
            </div>

            <div class="mt-6 flex gap-3 print:hidden">
                <button
                    @click="printCommand"
                    class="flex-1 bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800"
                >
                    Imprimir
                </button>

                <Link
                    :href="route('comandas')"
                    class="flex-1 bg-zinc-200 text-zinc-800 rounded-xl py-3 font-bold text-center hover:bg-zinc-300"
                >
                    Volver
                </Link>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: 80mm auto;
        margin: 4mm;
    }

    .command {
        width: 72mm;
        font-family: monospace;
        font-size: 12px;
    }
}
</style>
