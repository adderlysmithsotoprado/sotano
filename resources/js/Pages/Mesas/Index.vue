<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    tables: Array,
});

const now = ref(new Date());
let timer = null;

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 60000);
});

onUnmounted(() => {
    clearInterval(timer);
});

const selectedTable = ref(null);

const editForm = useForm({
    name: '',
    capacity: 4,
    status: 'libre',
    reserved_by: '',
    reserved_at: '',
    is_active: true,
});

const formatDateTimeLocal = (value) => {
    if (!value) return '';

    const date = new Date(value);

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const selectTable = (table) => {
    if (!table) return;

    selectedTable.value = table;

    editForm.name = table.name ?? '';
    editForm.capacity = Number(table.capacity ?? 4);
    editForm.status = table.status ?? 'libre';
    editForm.reserved_by = table.reserved_by ?? '';
    editForm.reserved_at = formatDateTimeLocal(table.reserved_at);
    editForm.is_active = Boolean(table.is_active ?? true);

    setTimeout(() => {
        document
            .getElementById('administrar-mesa')
            ?.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
    }, 50);
};

const updateSelectedTable = () => {
    if (!selectedTable.value) {
        alert('Selecciona una mesa del plano.');
        return;
    }

    if (editForm.status !== 'reservada') {
        editForm.reserved_by = '';
        editForm.reserved_at = '';
    }

    editForm.put(
        route('mesas.update', selectedTable.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedTable.value = null;
                editForm.reset();
                editForm.capacity = 4;
                editForm.status = 'libre';
                editForm.is_active = true;
            },
        }
    );
};

const cancelTableEdition = () => {
    selectedTable.value = null;
    editForm.reset();
    editForm.capacity = 4;
    editForm.status = 'libre';
    editForm.is_active = true;
};

const moveModal = ref(false);

const mergeModal = ref(false);
const tableToMerge = ref(null);

const mergeForm = useForm({
    source_table_id: '',
});

const occupiedTables = () => {
    return props.tables.filter(table => {
        return table.id !== tableToMerge.value?.id
            && currentOrder(table);
    });
};

const openMergeModal = (table) => {
    tableToMerge.value = table;
    mergeForm.source_table_id = '';
    mergeModal.value = true;
};

const closeMergeModal = () => {
    mergeModal.value = false;
    tableToMerge.value = null;
    mergeForm.reset();
};

const mergeTables = () => {
    if (!mergeForm.source_table_id) {
        alert('Selecciona una mesa.');
        return;
    }

    mergeForm.put(route('mesas.merge', tableToMerge.value.id), {
        preserveScroll: true,
        onSuccess: () => closeMergeModal(),
    });
};
const tableToMove = ref(null);
const actionMenu = ref(null);

const moveForm = useForm({
    target_table_id: '',
});

const tableLayout = [
    { number: 20, col: 1, row: 1 },
    { number: 5, col: 5, row: 1 },

    { number: 19, col: 1, row: 2 },
    { number: 15, col: 2, row: 2 },
    { number: 12, col: 3, row: 2 },
    { number: 8, col: 4, row: 2 },
    { number: 4, col: 5, row: 2 },

    { number: 18, col: 1, row: 3 },
    { number: 14, col: 2, row: 3 },
    { number: 11, col: 3, row: 3 },
    { number: 7, col: 4, row: 3 },
    { number: 3, col: 5, row: 3 },

    { number: 17, col: 1, row: 4 },
    { number: 13, col: 2, row: 4 },
    { number: 10, col: 3, row: 4 },
    { number: 6, col: 4, row: 4 },
    { number: 2, col: 5, row: 4 },

    { number: 16, col: 1, row: 5 },
    { number: 9, col: 2, row: 5 },
    { number: 1, col: 3, row: 5 },

    // NUEVAS MESAS
    { number: 21, col: 4, row: 5 },
    { number: 22, col: 5, row: 5 },

    { number: 23, col: 1, row: 6 },
    { number: 24, col: 2, row: 6 },
    { number: 25, col: 3, row: 6 },
    { number: 26, col: 4, row: 6 },
    { number: 27, col: 5, row: 6 },
];

const tableByNumber = (number) => {
    return props.tables.find((table) => {
        const tableNumber = String(table.name).match(/\d+/)?.[0];
        return Number(tableNumber) === number;
    });
};

const barTable = () => {
    return props.tables.find(t => t.name.toLowerCase() === 'barra');
};

const currentOrder = (table) => {
    return table?.orders && table.orders.length > 0 ? table.orders[0] : null;
};

const toggleActionMenu = (tableId) => {
    actionMenu.value = actionMenu.value === tableId ? null : tableId;
};

const freeTables = () => {
    return props.tables.filter(table => {
        return table.id !== tableToMove.value?.id
            && table.status === 'libre'
            && !currentOrder(table);
    });
};

const openMoveModal = (table) => {
    tableToMove.value = table;
    moveForm.target_table_id = '';
    moveModal.value = true;
};

const closeMoveModal = () => {
    moveModal.value = false;
    tableToMove.value = null;
    moveForm.reset();
};

const moveTable = () => {
    if (!moveForm.target_table_id) {
        alert('Selecciona una mesa destino.');
        return;
    }

    moveForm.put(route('mesas.move', tableToMove.value.id), {
        preserveScroll: true,
        onSuccess: () => closeMoveModal(),
    });
};

const elapsedTime = (table) => {
    const order = currentOrder(table);

    if (!order) return '';

    const start = new Date(order.created_at);
    const diffMinutes = Math.floor((now.value - start) / 60000);

    if (diffMinutes < 1) return 'Ahora';
    if (diffMinutes < 60) return `${diffMinutes} min`;

    const hours = Math.floor(diffMinutes / 60);
    const minutes = diffMinutes % 60;

    return `${hours}h ${minutes}min`;
};

const elapsedClass = (table) => {
    const order = currentOrder(table);

    if (!order) return '';

    const start = new Date(order.created_at);
    const diffMinutes = Math.floor((now.value - start) / 60000);

    if (diffMinutes >= 40) return 'bg-red-600 text-white';
    if (diffMinutes >= 20) return 'bg-orange-600 text-white';

    return 'bg-white/80 text-zinc-700';
};

const tableState = (table) => {
    if (!table) return 'sin_crear';

    const order = currentOrder(table);

    if (table.status === 'reservada' && !order) return 'reservada';
    if (!order) return 'libre';

    const items = order.items ?? [];

    if (items.length === 0) return 'abierto';

    if (items.every(item => item.status === 'listo' || item.status === 'entregado')) {
        return 'listo';
    }

    if (items.some(item =>
        item.status === 'preparando' ||
        item.status === 'pendiente' ||
        item.command_sent
    )) {
        return 'enviado';
    }

    return 'abierto';
};

const stateLabel = (table) => {
    const state = tableState(table);

    if (state === 'sin_crear') return 'Sin crear';
    if (state === 'libre') return 'Libre';
    if (state === 'reservada') return 'Reservada';
    if (state === 'abierto') return 'Pedido abierto';
    if (state === 'enviado') return 'Comanda enviada';
    if (state === 'listo') return 'Listo para cobrar';

    return 'Sin estado';
};

const stateIcon = (table) => {
    const state = tableState(table);

    if (state === 'sin_crear') return '⚪';
    if (state === 'libre') return '🟢';
    if (state === 'reservada') return '🟠';
    if (state === 'abierto') return '🟡';
    if (state === 'enviado') return '🔵';
    if (state === 'listo') return '🟣';

    return '🍽️';
};

const stateClass = (table) => {
    const state = tableState(table);

    if (state === 'sin_crear') return 'bg-zinc-100 border-zinc-300 text-zinc-400';
    if (state === 'libre') return 'bg-green-100 border-green-500 text-green-700';
    if (state === 'reservada') return 'bg-orange-100 border-orange-500 text-orange-700';
    if (state === 'abierto') return 'bg-yellow-100 border-yellow-500 text-yellow-700';
    if (state === 'enviado') return 'bg-blue-100 border-blue-500 text-blue-700';
    if (state === 'listo') return 'bg-purple-100 border-purple-500 text-purple-700';

    return 'bg-zinc-100 border-zinc-400 text-zinc-700';
};

</script>

<template>
    <Head title="Mesas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-zinc-800">
                🍽️ Mesas
            </h2>
        </template>

        <div class="space-y-5">
            <!-- Plano -->
            <div class="bg-white rounded-2xl shadow p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-2xl font-black">
                            Plano de Mesas
                        </h3>
                        <p class="text-sm text-zinc-500">
                            Distribución real del salón SOTANO Restobar.
                        </p>
                    </div>

                    <div class="hidden md:flex gap-4 text-xs font-bold">
                        <span>🟢 Libre</span>
                        <span>🟡 Pedido</span>
                        <span>🔵 Cocina/Bar</span>
                        <span>🟣 Cobrar</span>
                        <span>🟠 Reserva</span>
                    </div>
                </div>

                <div class="rounded-2xl border bg-amber-50 p-4">
                    <div
                        class="relative grid gap-3"
                        style="grid-template-columns: repeat(6, minmax(110px, 1fr)); grid-template-rows: repeat(7, 108px);"
                    >
                        <!-- Escenario: mismo plano, centrado arriba -->
                        <div
                            class="col-start-2 col-span-3 row-start-1 rounded-2xl bg-zinc-900 text-white flex items-center justify-center shadow-lg"
                        >
                            <div class="text-center">
                                <div class="text-xl">🎤</div>
                                <div class="font-black tracking-widest text-sm">
                                    ESCENARIO
                                </div>
                            </div>
                        </div>

                        <template
                            v-for="position in tableLayout"
                            :key="position.number"
                        >
                            <div
    class="group relative overflow-hidden rounded-2xl border-2 shadow-md p-1.5 text-center flex flex-col justify-center transition hover:scale-[1.02] cursor-pointer"
    :class="[
        stateClass(tableByNumber(position.number)),
        selectedTable?.id === tableByNumber(position.number)?.id
            ? 'ring-4 ring-black ring-offset-2'
            : '',
    ]"
    :style="{
        gridColumn: position.col,
        gridRow: position.row,
    }"
    @click="selectTable(tableByNumber(position.number))"
>
                                <template v-if="tableByNumber(position.number)">
                                    <div class="leading-none">
    <div class="text-lg">
        {{ stateIcon(tableByNumber(position.number)) }}
    </div>

    <div class="text-lg font-black leading-none mt-0.5">
        Mesa {{ position.number }}
    </div>

    <div class="text-[9px] font-black uppercase leading-none mt-1">
        {{ stateLabel(tableByNumber(position.number)) }}
    </div>

                                        <p
                                            v-if="currentOrder(tableByNumber(position.number))"
                                            class="inline-block mt-1 px-2 py-0.5 rounded-lg text-[9px] font-black leading-none"
                                            :class="elapsedClass(tableByNumber(position.number))"
                                        >
                                            ⏱️ {{ elapsedTime(tableByNumber(position.number)) }}
                                        </p>

                                        <div
                                            v-if="currentOrder(tableByNumber(position.number))"
                                            class="mt-1 space-y-1"
                                        >
                                            <div
                                                v-if="currentOrder(tableByNumber(position.number)).guest_name"
                                                class="bg-white/80 rounded-lg px-2 py-1 text-[10px] font-black text-blue-700 truncate"
                                            >
                                                👤 {{ currentOrder(tableByNumber(position.number)).guest_name }}
                                            </div>

                                            <p class="font-black text-xs leading-none">
    S/ {{ currentOrder(tableByNumber(position.number)).total }}
</p>
                                        </div>
                                    </div>

                                    <div
                                    class="absolute inset-0 hidden group-hover:grid bg-black/85 rounded-2xl grid-cols-2 gap-1 p-1.5 z-20 content-center"
                                    >
                                        <Link
    :href="route('pos.show', tableByNumber(position.number).id)"
    @click.stop
    class="col-span-2 bg-white text-black rounded-lg h-7 flex items-center justify-center text-[10px] font-black"
>
    Abrir POS
</Link>

<button
    v-if="currentOrder(tableByNumber(position.number))"
    type="button"
    @click.stop.prevent="openMoveModal(tableByNumber(position.number))"
    class="bg-blue-600 text-white rounded-lg h-7 text-[10px] font-black"
>
    Mover
</button>

<Link
    v-if="currentOrder(tableByNumber(position.number))"
    :href="route('pos.split-bill', currentOrder(tableByNumber(position.number)).id)"
    @click.stop
    class="bg-purple-600 text-white rounded-lg h-7 flex items-center justify-center text-[10px] font-black"
>
    Dividir
</Link>

<button
    v-if="currentOrder(tableByNumber(position.number))"
    type="button"
    @click.stop.prevent="openMergeModal(tableByNumber(position.number))"
    class="col-span-2 bg-orange-600 text-white rounded-lg h-7 text-[10px] font-black"
>
    Unir
</button>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="flex-1 flex flex-col items-center justify-center">
                                        <div class="text-xl">⚪</div>

                                        <div class="text-xl font-black">
                                            Mesa {{ position.number }}
                                        </div>

                                        <div class="text-[10px] font-black uppercase">
                                            Sin crear
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Barra: debajo de columna Mesa 1 y Mesa 6, tamaño de 2 cuadrados -->
                        <template v-if="barTable()">
                            <div
    class="group relative col-start-4 col-span-2 row-start-7 rounded-2xl shadow-lg hover:scale-[1.02] transition flex flex-col items-center justify-center p-3 border-2 cursor-pointer"
    :class="[
        stateClass(barTable()),
        selectedTable?.id === barTable()?.id
            ? 'ring-4 ring-black ring-offset-2'
            : '',
    ]"
    @click="selectTable(barTable())"
>
    <div class="text-xl font-black tracking-widest text-amber-800 leading-none">
        BARRA
    </div>

    <div class="text-[10px] font-black uppercase leading-none mt-1">
        {{ stateLabel(barTable()) }}
    </div>

    <p
        v-if="currentOrder(barTable())"
        class="inline-block mt-1 px-2 py-0.5 rounded-lg text-[9px] font-black leading-none"
        :class="elapsedClass(barTable())"
    >
        ⏱️ {{ elapsedTime(barTable()) }}
    </p>

    <p
        v-if="currentOrder(barTable())"
        class="font-black text-xs leading-none mt-1"
    >
        S/ {{ currentOrder(barTable()).total }}
    </p>

    <div class="mt-2 grid grid-cols-2 gap-1 w-full">
        <Link
            :href="route('pos.show', barTable().id)"
            @click.stop
            class="bg-white text-black rounded-lg h-7 flex items-center justify-center text-[10px] font-black"
        >
            Abrir POS
        </Link>

        <Link
            v-if="currentOrder(barTable())"
            :href="route('pos.split-bill', currentOrder(barTable()).id)"
            @click.stop
            class="bg-purple-600 text-white rounded-lg h-7 flex items-center justify-center text-[10px] font-black"
        >
            Dividir
        </Link>

        <span
            v-else
            class="bg-zinc-200 text-zinc-500 rounded-lg h-7 flex items-center justify-center text-[10px] font-black"
        >
            Sin pedido
        </span>
    </div>
</div>
                        </template>

                        <template v-else>
                            <div
                                class="col-start-4 col-span-2 row-start-7 rounded-2xl bg-amber-50 border-2 border-amber-500 text-amber-800 shadow-lg flex flex-col items-center justify-center p-3"
                            >
                                <div class="text-xl">
                                    🍺
                                </div>

                                <div class="text-2xl font-black tracking-widest">
                                    BARRA
                                </div>

                                <div class="mt-2 flex items-center justify-center gap-2">
                                    <span
                                        v-for="chair in 6"
                                        :key="chair"
                                        class="w-6 h-6 rounded-full bg-white border-2 border-zinc-700"
                                    ></span>
                                </div>

                                <p class="mt-2 text-xs">
                                    Cree una mesa llamada <strong>Barra</strong>
                                </p>
                            </div>
                        </template>
                    </div>
                </div>

                <div
                    v-if="tables.length === 0"
                    class="mt-6 py-10 text-center text-zinc-500"
                >
                    Aún no hay mesas registradas.
                </div>
            </div>
        </div>
        <!-- Administración de la mesa seleccionada -->
<div
    id="administrar-mesa"
    class="bg-white rounded-2xl shadow p-5"
>
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
        <div>
            <h3 class="text-2xl font-black text-zinc-900">
                ⚙️ Administrar Mesa
            </h3>

            <p class="text-sm text-zinc-500">
                Selecciona una mesa del plano para editar su capacidad,
                estado o reserva.
            </p>
        </div>

        <div
            v-if="selectedTable"
            class="rounded-xl bg-black text-white px-5 py-3 font-black"
        >
            {{ selectedTable.name }}
        </div>
    </div>

    <div
        v-if="!selectedTable"
        class="rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50 py-10 text-center"
    >
        <div class="text-4xl mb-3">
            👆
        </div>

        <p class="font-black text-zinc-700">
            Selecciona una mesa
        </p>

        <p class="text-sm text-zinc-500 mt-1">
            Haz clic sobre cualquier mesa del plano o sobre la Barra.
        </p>
    </div>

    <form
        v-else
        @submit.prevent="updateSelectedTable"
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4 items-end"
    >
        <div>
            <label class="block text-xs font-black text-zinc-500 uppercase mb-2">
                Mesa
            </label>

            <input
                v-model="editForm.name"
                type="text"
                readonly
                class="w-full rounded-xl border-zinc-300 bg-zinc-100 text-sm font-bold cursor-not-allowed"
            />
        </div>

        <div>
            <label class="block text-xs font-black text-zinc-500 uppercase mb-2">
                Asientos
            </label>

            <input
                v-model="editForm.capacity"
                type="number"
                min="1"
                class="w-full rounded-xl border-zinc-300 text-sm"
            />

            <p
                v-if="editForm.errors.capacity"
                class="mt-1 text-xs font-bold text-red-600"
            >
                {{ editForm.errors.capacity }}
            </p>
        </div>

        <div>
            <label class="block text-xs font-black text-zinc-500 uppercase mb-2">
                Estado
            </label>

            <select
                v-model="editForm.status"
                class="w-full rounded-xl border-zinc-300 text-sm"
            >
                <option value="libre">
                    Libre
                </option>

                <option value="ocupada">
                    Ocupada
                </option>

                <option value="reservada">
                    Reservada
                </option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-black text-zinc-500 uppercase mb-2">
                Fecha / hora
            </label>

            <input
                v-model="editForm.reserved_at"
                type="datetime-local"
                :disabled="editForm.status !== 'reservada'"
                class="w-full rounded-xl border-zinc-300 text-sm disabled:bg-zinc-100 disabled:text-zinc-400"
            />
        </div>

        <div>
            <label class="block text-xs font-black text-zinc-500 uppercase mb-2">
                Titular
            </label>

            <input
                v-model="editForm.reserved_by"
                type="text"
                placeholder="Nombre del cliente"
                :disabled="editForm.status !== 'reservada'"
                class="w-full rounded-xl border-zinc-300 text-sm disabled:bg-zinc-100 disabled:text-zinc-400"
            />
        </div>

        <div class="flex gap-2">
            <button
                type="button"
                @click="cancelTableEdition"
                class="flex-1 bg-zinc-200 hover:bg-zinc-300 text-zinc-800 rounded-xl py-3 font-black"
            >
                Cancelar
            </button>

            <button
                type="submit"
                :disabled="editForm.processing"
                class="flex-1 bg-black hover:bg-zinc-800 text-white rounded-xl py-3 font-black disabled:opacity-50"
            >
                {{ editForm.processing ? 'Guardando...' : 'Guardar' }}
            </button>
        </div>
    </form>
</div>
        <!-- Modal Mover Mesa -->
        <div
            v-if="moveModal"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-2xl font-black mb-2">
                    🔄 Mover mesa
                </h3>

                <p class="text-zinc-500 mb-5">
                    Mesa actual:
                    <strong>{{ tableToMove?.name }}</strong>
                </p>

                <label class="block text-sm font-bold mb-2">
                    Mesa destino
                </label>

                <select
                    v-model="moveForm.target_table_id"
                    class="w-full rounded-xl border-zinc-300"
                >
                    <option value="">
                        Seleccionar mesa libre
                    </option>

                    <option
                        v-for="table in freeTables()"
                        :key="table.id"
                        :value="table.id"
                    >
                        {{ table.name }}
                    </option>
                </select>

                <div class="mt-6 flex gap-3">
                    <button
                        type="button"
                        @click="closeMoveModal"
                        class="flex-1 bg-zinc-200 rounded-xl py-3 font-bold"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        @click="moveTable"
                        class="flex-1 bg-black text-white rounded-xl py-3 font-bold"
                    >
                        Mover
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Unir Mesas -->
        <div
            v-if="mergeModal"
            class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-2xl font-black mb-2">
                    🔀 Unir mesas
                </h3>

                <p class="text-zinc-500 mb-5">
                    Mesa destino:
                    <strong>{{ tableToMerge?.name }}</strong>
                </p>

                <label class="block text-sm font-bold mb-2">
                    Mesa que será unida
                </label>

                <select
                    v-model="mergeForm.source_table_id"
                    class="w-full rounded-xl border-zinc-300"
                >
                    <option value="">
                        Seleccionar mesa
                    </option>

                    <option
                        v-for="table in occupiedTables()"
                        :key="table.id"
                        :value="table.id"
                    >
                        {{ table.name }}
                    </option>
                </select>

                <div class="mt-6 flex gap-3">
                    <button
                        type="button"
                        @click="closeMergeModal"
                        class="flex-1 bg-zinc-200 rounded-xl py-3 font-bold hover:bg-zinc-300"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        @click="mergeTables"
                        :disabled="mergeForm.processing"
                        class="flex-1 bg-orange-600 text-white rounded-xl py-3 font-bold hover:bg-orange-700 disabled:opacity-50"
                    >
                        Unir
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
