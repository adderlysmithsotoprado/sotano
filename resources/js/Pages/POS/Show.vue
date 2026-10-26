<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { invoke } from '@tauri-apps/api/core';
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    table: Object,
    order: Object,
    cashRegister: Object,
    business: Object,
    categories: Array,
    products: Array,
    hasPendingCommandItems: Boolean,
    customers: Array,
    tables: Array,
});

const selectedCategory = ref('todos');
const searchProduct = ref('');
const selectedProductForModifiers = ref(null);
const selectedModifierIds = ref([]);
const modifierNote = ref('');

const cancelOrder = () => {
    const reason = prompt('Motivo de cancelación:');

    if (!reason?.trim()) {
        return;
    }

    if (!confirm(
        '¿Seguro que deseas cancelar este pedido y liberar la mesa? Esta acción devolverá el stock.'
    )) {
        return;
    }

    router.post(
        route('pos.order.cancel', props.order.id),
        {
            cancel_reason: reason.trim(),
        },
        {
            preserveScroll: true,
        }
    );
};

const form = useForm({
    product_id: '',
    quantity: 1,
    notes: '',
    modifier_ids: [],
});

const customerForm = useForm({
    customer_id: props.order?.customer_id ?? '',
});

const moveItemModal = ref(false);
const itemToMove = ref(null);

const moveItemForm = useForm({
    target_table_id: '',
    quantity: 1,
});

const availableTables = computed(() => {
    return props.tables?.filter(table => table.id !== props.table.id) ?? [];
});

const openMoveItemModal = (item) => {
    itemToMove.value = item;
    moveItemForm.target_table_id = '';
    moveItemForm.quantity = 1;
    moveItemModal.value = true;
};

const closeMoveItemModal = () => {
    moveItemModal.value = false;
    itemToMove.value = null;
    moveItemForm.reset();
    moveItemForm.quantity = 1;
};

const moveItemToTable = () => {
    if (!moveItemForm.target_table_id) {
        alert('Selecciona una mesa destino.');
        return;
    }

    moveItemForm.put(
        route(
            'pos.items.move-table',
            itemToMove.value.id
        ),
        {
            preserveScroll: true,
            preserveState: true,
            only: [
                'order',
                'hasPendingCommandItems',
            ],
            onSuccess: () => {
                closeMoveItemModal();
            },
        }
    );
};

const guestForm = useForm({
    guest_name: props.order?.guest_name ?? '',
});

const updateGuestName = () => {
    guestForm.put(
        route(
            'pos.order.guest-name',
            props.order.id
        ),
        {
            preserveScroll: true,
            preserveState: true,
            only: ['order'],
        }
    );
};

const updateCustomer = () => {
    customerForm.put(
        route(
            'pos.order.customer',
            props.order.id
        ),
        {
            preserveScroll: true,
            preserveState: true,
            only: ['order'],
        }
    );
};

const filteredProducts = computed(() => {
    let list = props.products;

    if (
        selectedCategory.value === 'todos' &&
        searchProduct.value.trim() === ''
    ) {
        list = list.filter(product => !product.is_favorite);
    }

    if (selectedCategory.value !== 'todos') {
        list = list.filter(
            product => product.category_id === selectedCategory.value
        );
    }

    if (searchProduct.value.trim() !== '') {
        const search = searchProduct.value.toLowerCase();

        list = list.filter(product => {
            return (
                product.name?.toLowerCase().includes(search) ||
                product.category?.name?.toLowerCase().includes(search) ||
                String(product.price).includes(search) ||
                product.printer_area?.toLowerCase().includes(search)
            );
        });
    }

    return list;
});

const favoriteProducts = computed(() => {
    return props.products.filter(product => product.is_favorite);
});

const addProduct = (product) => {
    if (form.processing || Number(product.stock) <= 0) {
        return;
    }

    form.product_id = product.id;
    form.quantity = 1;
    form.notes = '';
    form.modifier_ids = [];

    form.post(
        route('pos.items.add', props.order.id),
        {
            preserveScroll: true,

            onSuccess: () => {
                form.reset();
                form.quantity = 1;
                form.notes = '';
                form.modifier_ids = [];
                searchProduct.value = '';
            },

            onError: (errors) => {
                console.error(
                    'Error al agregar producto:',
                    errors
                );
            },
        }
    );
};
const addProductWithNote = (product) => {
    if (product.stock <= 0) {
        return;
    }

    const note = prompt('Ingrese una nota para este producto:');

    if (note === null) {
        return;
    }

    form.product_id = product.id;
    form.quantity = 1;
    form.notes = note;

    form.post(route('pos.items.add', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
    form.reset();
    form.quantity = 1;
    form.notes = '';
    form.modifier_ids = [];

    searchProduct.value = '';
        },
    });
};

const openModifierModal = (product) => {
    if (product.stock <= 0) {
        return;
    }

    if (!product.modifiers || product.modifiers.length === 0) {
        addProduct(product);
        return;
    }

    selectedProductForModifiers.value = product;
    selectedModifierIds.value = [];
    modifierNote.value = '';
};

const closeModifierModal = () => {
    selectedProductForModifiers.value = null;
    selectedModifierIds.value = [];
    modifierNote.value = '';
};

const addProductWithModifiers = () => {
    if (!selectedProductForModifiers.value) {
        return;
    }

    const selectedNames = selectedProductForModifiers.value.modifiers
        .filter(modifier => selectedModifierIds.value.includes(modifier.id))
        .map(modifier => {
            return Number(modifier.extra_price) > 0
                ? `${modifier.name} (+S/ ${modifier.extra_price})`
                : modifier.name;
        });

    const finalNote = [
        ...selectedNames,
        modifierNote.value,
    ].filter(Boolean).join(' | ');

    form.product_id = selectedProductForModifiers.value.id;
    form.quantity = 1;
    form.notes = finalNote;

    // 👇 Agregar esta línea
    form.modifier_ids = selectedModifierIds.value;

    form.post(route('pos.items.add', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
    closeModifierModal();
    form.reset();

    form.quantity = 1;
    form.notes = '';
    form.modifier_ids = [];

    selectedModifierIds.value = [];
    searchProduct.value = '';
        },
    });
};

const addFirstFilteredProduct = () => {
    const product = filteredProducts.value.find(product => product.stock > 0);

    if (!product) {
        alert('No hay productos disponibles con stock.');
        return;
    }

    addProduct(product);
};

const hasKitchenItems = computed(() => {
    return props.order.items.some(item => item.printer_area === 'cocina');
});

const hasBarItems = computed(() => {
    return props.order.items.some(item => item.printer_area === 'bar');
});

const increaseItem = (item) => {
    router.post(
        route('pos.items.increase', item.id),
        {},
        {
            preserveScroll: true,

        }
    );
};

const decreaseItem = (item) => {
    router.post(
        route('pos.items.decrease', item.id),
        {},
        {
            preserveScroll: true,

        }
    );
};

const editNote = (item) => {
    const note = prompt(
        'Editar nota del producto:',
        item.notes ?? ''
    );

    if (note === null) {
        return;
    }

    router.put(
        route('pos.items.note', item.id),
        {
            notes: note,
        },
        {
            preserveScroll: true,
        }
    );
};

const removeItem = (item) => {
    const reason = prompt(
        `Motivo de cancelación de "${item.product_name}":`
    );

    if (reason === null) {
        return;
    }

    if (!reason.trim()) {
        alert('Debes escribir el motivo de la cancelación.');
        return;
    }

    router.delete(
        route('pos.items.remove', item.id),
        {
            data: {
                reason: reason.trim(),
            },
            preserveScroll: true,


            onError: (errors) => {
                console.error(errors);
                alert('No se pudo cancelar el producto.');
            },
        }
    );
};

const kitchenItems = () => {
    return props.order.items.filter(item =>
        item.printer_area === 'cocina' &&
        !item.command_sent
    );
};

const barItems = () => {
    return props.order.items.filter(item =>
        item.printer_area === 'bar' &&
        !item.command_sent
    );
};

const sendCommand = async () => {
    const pendingKitchenItems = props.order.items.filter(item =>
        item.printer_area === 'cocina' &&
        !item.command_sent
    );

    const pendingBarItems = props.order.items.filter(item =>
        item.printer_area === 'bar' &&
        !item.command_sent
    );

    if (
        pendingKitchenItems.length === 0 &&
        pendingBarItems.length === 0
    ) {
        alert('No hay productos nuevos para enviar a comandas.');
        return;
    }

    const formatItems = (items) => {
        return items.flatMap(item => {
            const lines = [
                `${item.quantity} x ${item.product_name.toUpperCase()}`,
            ];

            if (item.modifiers?.length) {
                item.modifiers.forEach(modifier => {
                    lines.push(`   + ${modifier.modifier_name}`);
                });
            }

            if (item.notes) {
                lines.push(`   NOTA: ${item.notes}`);
            }

            return lines;
        });
    };
    const now = new Date();

const commandDate = now.toLocaleDateString('es-PE', {
    timeZone: 'America/Lima',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const commandTime = now.toLocaleTimeString('es-PE', {
    timeZone: 'America/Lima',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
});

    try {
        if (pendingKitchenItems.length > 0) {
            await invoke('print_batch', {
                printerName: props.business?.kitchen_printer ?? '',
                area: 'COCINA',
                table: props.table.name,
                orderNumber: Number(props.order.id),
                waiter: props.order.user?.name ?? 'Administrador',
                date: commandDate,
                time: commandTime,
                items: formatItems(pendingKitchenItems),

            });
        }

        if (pendingBarItems.length > 0) {
            await invoke('print_batch', {
                printerName: props.business?.bar_printer ?? '',
                area: 'BAR',
                table: props.table.name,
                orderNumber: Number(props.order.id),
                waiter: props.order.user?.name ?? 'Administrador',
                date: commandDate,
                time: commandTime,
                items: formatItems(pendingBarItems),

            });
        }

        router.post(route('pos.command.send', props.order.id), {}, {
            preserveScroll: true,
            preserveState: true,
            only: ['order', 'hasPendingCommandItems'],
            onSuccess: () => {
                alert('Comandas impresas y enviadas correctamente.');
            },
        });
    } catch (error) {
        console.error(error);

        alert(
            typeof error === 'string'
                ? error
                : 'No se pudo imprimir la comanda. Los productos no fueron marcados como enviados.'
        );
    }
};

const printPreCuenta = async () => {

    try {

        const { data } = await axios.get(
            route('pos.precuenta', props.order.id)
        );

        await invoke('print_ticket', {
            printerName: data.printer_name,
            content: data.ticket_content,
        });

    } catch (e) {

        console.error(e);

        alert('No se pudo imprimir la pre-cuenta.');

    }

};

const closeOrder = () => {
    if (!confirm('¿Deseas cerrar y cobrar esta mesa?')) {
        return;
    }

    router.visit(
        route('pos.payment', props.order.id)
    );
};

const itemStatusLabel = (status) => {
    if (status === 'pendiente') return 'Pendiente';
    if (status === 'preparando') return 'Preparando';
    if (status === 'listo') return 'Listo';
    if (status === 'entregado') return 'Entregado';

    return 'Sin estado';
};

const itemStatusClass = (status) => {
    if (status === 'pendiente') return 'bg-yellow-100 text-yellow-700';
    if (status === 'preparando') return 'bg-blue-100 text-blue-700';
    if (status === 'listo') return 'bg-green-100 text-green-700';
    if (status === 'entregado') return 'bg-zinc-100 text-zinc-700';

    return 'bg-zinc-100 text-zinc-700';
};

</script>

<template>
    <Head title="POS" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🛒 POS - {{ table.name }}
            </h2>
        </template>

        <div
            v-if="$page.props.errors?.order || $page.props.errors?.stock || $page.props.errors?.command"
            class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl font-bold"
        >
            {{ $page.props.errors.order || $page.props.errors.stock || $page.props.errors.command }}
        </div>

        <Link
            v-if="$page.props.errors?.order"
            :href="route('caja')"
            class="inline-block mb-6 bg-black text-white px-5 py-3 rounded-xl font-bold"
        >
            Ir a abrir caja
        </Link>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div
                    v-if="favoriteProducts.length > 0 && selectedCategory === 'todos' && !searchProduct"
                    class="bg-white rounded-2xl shadow p-4"
                >
                    <h3 class="text-lg font-black mb-4">
                        ⭐ Favoritos
                    </h3>

                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div
                            v-for="product in favoriteProducts"
                            :key="'fav-' + product.id"
                            class="bg-yellow-100 border-2 border-yellow-400 rounded-2xl shadow p-4 text-left transition"
                            :class="product.stock <= 0
                                ? 'opacity-50 cursor-not-allowed'
                                : 'hover:scale-105'"
                        >
                            <button
                                type="button"
                                @click="openModifierModal(product)"
                                @dblclick="addProduct(product)"
                                :disabled="product.stock <= 0"
                                class="w-full text-left"
                            >
                                <div class="text-sm text-yellow-700 font-bold">
                                    ⭐ Favorito
                                </div>

                                <div class="font-bold text-lg mt-1">
                                    {{ product.name }}
                                </div>

                                <div class="mt-3 text-xl font-black">
                                    S/ {{ product.price }}
                                </div>

                                <div
                                    class="mt-2 text-xs font-bold"
                                    :class="product.stock <= 0 ? 'text-red-600' : 'text-green-600'"
                                >
                                    {{ product.stock <= 0 ? 'Sin stock' : 'Stock: ' + product.stock }}
                                </div>
                            </button>

                            <button
                                type="button"
                                @click.stop="addProductWithNote(product)"
                                :disabled="product.stock <= 0"
                                class="mt-3 w-full bg-zinc-200 text-zinc-700 rounded-lg py-2 text-xs font-bold hover:bg-zinc-300 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Nota
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow p-4 space-y-4">
                    <input
                        v-model="searchProduct"
                        @keydown.enter.prevent="addFirstFilteredProduct"
                        type="text"
                        placeholder="Buscar producto por nombre, categoría, precio, cocina o bar..."
                        class="w-full rounded-xl border-zinc-300 text-lg"
                    />

                    <button
                        v-if="searchProduct"
                        @click="searchProduct = ''"
                        class="bg-zinc-200 text-zinc-700 rounded-lg px-4 py-2 text-sm font-bold hover:bg-zinc-300"
                    >
                        ✖ Limpiar búsqueda
                    </button>

                    <div class="flex gap-3 overflow-x-auto">
                        <button
                            @click="selectedCategory = 'todos'"
                            class="px-4 py-2 rounded-xl text-sm font-bold"
                            :class="selectedCategory === 'todos'
                                ? 'bg-black text-white'
                                : 'bg-zinc-100 text-zinc-700'"
                        >
                            Todos
                        </button>

                        <button
                            v-for="category in categories"
                            :key="category.id"
                            @click="selectedCategory = category.id"
                            class="px-4 py-2 rounded-xl text-sm font-bold"
                            :class="selectedCategory === category.id
                                ? 'bg-black text-white'
                                : 'bg-zinc-100 text-zinc-700'"
                        >
                            {{ category.name }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="bg-white rounded-2xl shadow p-4 text-left transition"
                        :class="product.stock <= 0
                            ? 'opacity-50 cursor-not-allowed'
                            : 'hover:scale-105'"
                    >
                        <button
                            type="button"
                            @click="openModifierModal(product)"
                            @dblclick="addProduct(product)"
                            :disabled="product.stock <= 0"
                            class="w-full text-left"
                        >
                            <div class="text-sm text-zinc-500">
                                {{ product.category?.name }}
                            </div>

                            <div class="font-bold text-lg mt-1">
                                {{ product.name }}
                            </div>

                            <div class="mt-3 text-xl font-black">
                                S/ {{ product.price }}
                            </div>

                            <div
                                class="mt-2 text-xs font-bold"
                                :class="product.stock <= 0 ? 'text-red-600' : 'text-green-600'"
                            >
                                {{ product.stock <= 0 ? 'Sin stock' : 'Stock: ' + product.stock }}
                            </div>
                        </button>

                        <button
                            type="button"
                            @click.stop="addProductWithNote(product)"
                            :disabled="product.stock <= 0"
                            class="mt-3 w-full bg-zinc-200 text-zinc-700 rounded-lg py-2 text-xs font-bold hover:bg-zinc-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Nota
                        </button>
                    </div>

                    <div
                        v-if="filteredProducts.length === 0"
                        class="col-span-full text-center text-zinc-500 py-10"
                    >
                        No hay productos en esta categoría.
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 h-fit">
                <div class="flex items-center justify-between border-b pb-4">
                    <div>
                        <h3 class="text-xl font-bold">
                            {{ table.name }}
                        </h3>

                        <p class="text-sm text-zinc-500">
                            Pedido #{{ order.id }}
                        </p>

                        <p
                            class="mt-1 text-xs font-bold"
                            :class="cashRegister ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ cashRegister ? 'Caja abierta' : 'Caja cerrada' }}
                        </p>
                    </div>


                    <span
                        class="px-3 py-1 rounded-full text-xs font-bold uppercase"
                        :class="order.command_sent
                            ? (order.ready ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700')
                            : 'bg-yellow-100 text-yellow-700'"
                    >
                        {{ order.command_sent ? (order.ready ? 'listo' : 'enviado') : 'abierto' }}
                    </span>
                </div>

                <div class="mt-5 border-b pb-5 space-y-4">
    <div>
        <label class="block text-sm font-bold mb-2">
            🏷️ Nombre de referencia
        </label>

        <div class="flex gap-2">
            <input
                v-model="guestForm.guest_name"
                type="text"
                placeholder="Ej: Adderly, Familia Pérez, Cumpleaños..."
                class="flex-1 rounded-xl border-zinc-300"
            />

            <button
                @click="updateGuestName"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-xl font-bold"
            >
                Guardar
            </button>
        </div>
    </div>

    <div>
        <label class="block text-sm font-bold mb-2">
            👤 Cliente
        </label>

        <div class="flex gap-2">
            <select
                v-model="customerForm.customer_id"
                class="flex-1 rounded-xl border-zinc-300"
            >
                <option value="">
                    Cliente General
                </option>

                <option
                    v-for="customer in customers"
                    :key="customer.id"
                    :value="customer.id"
                >
                    {{ customer.name }}
                </option>
            </select>

            <button
                @click="updateCustomer"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-xl font-bold"
            >
                Guardar
            </button>
        </div>

        <div
            v-if="order.customer"
            class="mt-3 text-sm text-green-700 font-bold"
        >
            Cliente asignado: {{ order.customer.name }}
        </div>
    </div>
</div>

                <div class="divide-y mt-4">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="py-4"
                    >
                        <div class="flex justify-between gap-4">
                            <div>
                                <p class="font-bold">
                                    {{ item.product_name }}
                                </p>
                                <span
    v-if="item.status === 'cancelado'"
    class="text-red-600 font-bold"
>
    ❌ CANCELADO
</span>

                                <p class="text-sm text-zinc-500 uppercase">
                                    {{ item.printer_area }}
                                </p>

                                <span
                                class="inline-block mt-1 px-2 py-1 rounded-lg text-xs font-bold"
                                :class="itemStatusClass(item.status)"
                                >
                                {{ itemStatusLabel(item.status) }}
                                </span>

                                <p
                                    v-if="item.notes"
                                    class="mt-1 text-xs bg-yellow-100 text-yellow-800 rounded-lg px-2 py-1"
                                >
                                    Nota: {{ item.notes }}
                                </p>
                            </div>

                            <div class="text-right">
    <p
        v-if="Number(item.discount || 0) > 0"
        class="text-xs line-through text-zinc-400"
    >
        S/ {{ Number(item.unit_price * item.quantity).toFixed(2) }}
    </p>

    <p class="font-bold">
        S/ {{ item.subtotal }}
    </p>

    <p
        v-if="Number(item.discount || 0) > 0"
        class="text-xs text-green-600 font-black"
    >
        🎉 {{ item.promotion_name }} - S/ {{ item.discount }}
    </p>
</div>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button
                                    @click="decreaseItem(item)"
                                    :disabled="item.status === 'entregado'"
                                    class="w-8 h-8 rounded-lg bg-zinc-200 font-bold hover:bg-zinc-300"
                                    :class="item.status === 'entregado'
                                    ? 'bg-zinc-200 text-zinc-400 cursor-not-allowed'
                                    : 'bg-zinc-200 hover:bg-zinc-300'"
                                >
                                    -
                                </button>

                                <span class="font-bold w-8 text-center">
                                    {{ item.quantity }}
                                </span>

                                <button
                                    @click="increaseItem(item)"
                                    :disabled="item.status === 'entregado'"
                                    class="w-8 h-8 rounded-lg bg-zinc-900 text-white font-bold hover:bg-black"
                                    :class="item.status === 'entregado'
                                    ? 'bg-zinc-400 cursor-not-allowed'
                                    : 'bg-zinc-900 hover:bg-black'"
                                    >
                                    +
                                </button>
                            </div>

                            <div class="flex gap-2">
                            <button
                            @click="editNote(item)"
                            :disabled="item.status === 'entregado'"
                            class="text-blue-600 text-sm font-bold hover:text-blue-800"
                            :class="item.status === 'entregado'
                            ? 'text-zinc-400 cursor-not-allowed'
                            : 'text-blue-600 hover:text-blue-800'"
                            >
                            ✏️ Nota
                            </button>

                            <button
    type="button"
    :disabled="false"
    @click.prevent.stop="removeItem(item)"
    class="text-sm font-black cursor-pointer"
    style="color: #dc2626 !important; opacity: 1 !important; pointer-events: auto !important;"
>
    🗑 Eliminar
</button>

                            <button
                            @click="openMoveItemModal(item)"
                            :disabled="item.status === 'entregado'"
                            class="text-purple-600 text-sm font-bold hover:text-purple-800"
                            >
                            🔁 Mover
                            </button>

                            </div>
                        </div>
                    </div>

                    <div
                        v-if="order.items.length === 0"
                        class="py-10 text-center text-zinc-500"
                    >
                        Aún no hay productos en esta mesa.
                    </div>
                </div>

                <div class="border-t mt-4 pt-4">
                    <div class="flex justify-between text-xl font-black">
                        <span>Total</span>
                        <span>S/ {{ order.total }}</span>
                    </div>

                    <div
    v-if="order.items.some(item => Number(item.discount || 0) > 0)"
    class="mt-2 rounded-xl bg-green-50 border border-green-200 p-3 text-sm"
>
    <p class="font-black text-green-700">
        🎉 Promoción aplicada
    </p>

    <p class="text-green-700">
        Descuento total:
        -S/
        {{
            order.items
                .reduce((sum, item) => sum + Number(item.discount || 0), 0)
                .toFixed(2)
        }}
    </p>
</div>

                    <button
    type="button"
    @click="sendCommand"
    :disabled="!hasPendingCommandItems"
    class="mt-3 w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-4 font-black disabled:opacity-50 disabled:cursor-not-allowed"
>
    📤 Enviar a comandas
</button>

                    <Link
                    :href="route('pos.payment', order.id)"
                    class="mt-3 block w-full bg-green-600 hover:bg-green-700 text-white rounded-xl py-4 font-black text-center"
                    >
                    💳 Cobrar mesa
                    </Link>

                    <button
    type="button"
    @click="printPreCuenta"
    class="mt-3 w-full bg-amber-600 hover:bg-amber-700 text-white rounded-xl py-4 font-black"
>
    🧾 Imprimir pre-cuenta
</button>

                     <button
                    type="button"
                    @click="cancelOrder"
                    class="mt-3 w-full bg-red-600 hover:bg-red-700 text-white rounded-xl py-4 font-black"
                    >
                    ❌ Cancelar pedido y liberar mesa
                    </button>


                </div>
            </div>
        </div>
        <!-- Modal de Modificadores -->
<div
    v-if="selectedProductForModifiers"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">

        <div class="flex justify-between items-center mb-5">
            <h2 class="text-2xl font-black">
                {{ selectedProductForModifiers.name }}
            </h2>

            <button
                @click="closeModifierModal"
                class="text-red-600 font-bold text-xl"
            >
                ✕
            </button>
        </div>

        <p class="text-zinc-500 mb-4">
            Seleccione los modificadores
        </p>

        <div
            class="space-y-3 max-h-72 overflow-y-auto"
            v-if="selectedProductForModifiers.modifiers.length"
        >
            <label
                v-for="modifier in selectedProductForModifiers.modifiers"
                :key="modifier.id"
                class="flex justify-between items-center border rounded-xl p-3 cursor-pointer hover:bg-zinc-50"
            >
                <div class="flex items-center gap-3">

                    <input
                        type="checkbox"
                        :value="modifier.id"
                        v-model="selectedModifierIds"
                    >

                    <span class="font-semibold">
                        {{ modifier.name }}
                    </span>

                </div>

                <span
                    v-if="Number(modifier.extra_price) > 0"
                    class="font-bold text-green-600"
                >
                    + S/ {{ modifier.extra_price }}
                </span>
            </label>
        </div>

        <div class="mt-5">

            <label class="font-bold text-sm">
                Observación adicional
            </label>

            <textarea
                v-model="modifierNote"
                rows="3"
                class="w-full mt-2 rounded-xl border-zinc-300"
                placeholder="Ej: bien cocido, poco hielo, etc."
            />

        </div>

        <div class="flex gap-3 mt-6">

            <button
                @click="closeModifierModal"
                class="flex-1 bg-zinc-200 rounded-xl py-3 font-bold hover:bg-zinc-300"
            >
                Cancelar
            </button>

            <button
                @click="addProductWithModifiers"
                class="flex-1 bg-black text-white rounded-xl py-3 font-bold hover:bg-zinc-800"
            >
                Agregar
            </button>

        </div>

    </div>
</div>
<!-- Modal Cambiar producto de mesa -->
<div
    v-if="moveItemModal"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-2xl font-black mb-2">
            🔁 Mover producto
        </h2>

        <p class="text-zinc-500 mb-5">
            Producto:
            <strong>{{ itemToMove?.product_name }}</strong>
        </p>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-bold mb-2">
                    Mesa destino
                </label>

                <select
                    v-model="moveItemForm.target_table_id"
                    class="w-full rounded-xl border-zinc-300"
                >
                    <option value="">
                        Seleccionar mesa
                    </option>

                    <option
                        v-for="table in availableTables"
                        :key="table.id"
                        :value="table.id"
                    >
                        {{ table.name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold mb-2">
                    Cantidad a mover
                </label>

                <input
                    v-model="moveItemForm.quantity"
                    type="number"
                    min="1"
                    :max="itemToMove?.quantity"
                    class="w-full rounded-xl border-zinc-300"
                />
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button
                type="button"
                @click="closeMoveItemModal"
                class="flex-1 bg-zinc-200 rounded-xl py-3 font-bold hover:bg-zinc-300"
            >
                Cancelar
            </button>

            <button
                type="button"
                @click="moveItemToTable"
                :disabled="moveItemForm.processing"
                class="flex-1 bg-purple-600 text-white rounded-xl py-3 font-bold hover:bg-purple-700 disabled:opacity-50"
            >
                Mover producto
            </button>
        </div>
    </div>
</div>
    </AuthenticatedLayout>
</template>
