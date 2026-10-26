<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';


const resetConfirmation = ref('');
const resettingSystem = ref(false);

const resetSystem = () => {
    if (resetConfirmation.value !== 'REINICIAR SOTANO') {
        alert('Escribe exactamente: REINICIAR SOTANO');
        return;
    }

    const confirmed = confirm(
        'Esta acción eliminará permanentemente ventas, pedidos, productos, compras, caja, clientes y demás datos. ¿Deseas continuar?'
    );

    if (!confirmed) {
        return;
    }

    resettingSystem.value = true;

    router.post(
        route('system.reset'),
        {
            confirmation: resetConfirmation.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                resetConfirmation.value = '';
                alert('Sistema reiniciado correctamente.');
            },

            onFinish: () => {
                resettingSystem.value = false;
            },
        }
    );
};

const props = defineProps({
    settings: Object,
});

const logoPreview = ref(null);

const form = useForm({
    business_name: props.settings?.business_name ?? '',
    ruc: props.settings?.ruc ?? '',
    address: props.settings?.address ?? '',
    phone: props.settings?.phone ?? '',
    ticket_message: props.settings?.ticket_message ?? '',
    logo: null,
});

const currentLogo = computed(() => {
    if (logoPreview.value) return logoPreview.value;
    if (props.settings?.logo_path) return `/storage/${props.settings.logo_path}`;
    return '/images/logo.png';
});

const handleLogo = (event) => {
    const file = event.target.files[0];
    form.logo = file;

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
};

const save = () => {
    form.post(route('configuracion.update'), {
        forceFormData: true,
        _method: 'put',
    });
};
</script>

<template>
    <Head title="Configuración" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold">
                ⚙️ Configuración General
            </h2>
        </template>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-2xl shadow p-8">
                <h3 class="text-xl font-bold mb-6">
                    Datos del negocio
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 font-semibold">
                            Nombre Comercial
                        </label>

                        <input
                            v-model="form.business_name"
                            class="w-full rounded-xl border-zinc-300"
                        >
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold">
                            RUC
                        </label>

                        <input
                            v-model="form.ruc"
                            class="w-full rounded-xl border-zinc-300"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 font-semibold">
                            Dirección
                        </label>

                        <input
                            v-model="form.address"
                            class="w-full rounded-xl border-zinc-300"
                        >
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold">
                            Teléfono
                        </label>

                        <input
                            v-model="form.phone"
                            class="w-full rounded-xl border-zinc-300"
                        >
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold">
                            Mensaje del Ticket
                        </label>

                        <input
                            v-model="form.ticket_message"
                            class="w-full rounded-xl border-zinc-300"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 font-semibold">
                            Logo del negocio
                        </label>

                        <input
                            type="file"
                            accept="image/*"
                            @change="handleLogo"
                            class="w-full rounded-xl border border-zinc-300 p-2"
                        >

                        <p v-if="settings?.logo_path" class="text-sm text-zinc-500 mt-2">
                            Logo actual: {{ settings.logo_path }}
                        </p>
                    </div>
                </div>


                <div class="mt-8 border-t pt-6 flex justify-end">
                    <button
                        @click="save"
                        class="bg-black hover:bg-zinc-800 text-white px-8 py-3 rounded-xl font-bold disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        💾 Guardar Configuración
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow p-8 h-fit">
                <h3 class="text-xl font-bold mb-6">
                    Vista previa del ticket
                </h3>

                <div class="mx-auto bg-white border rounded-xl p-4 w-[280px] font-mono text-black">
                    <div class="text-center border-b border-dashed border-black pb-3">
                        <img
                            :src="currentLogo"
                            class="w-20 mx-auto mb-2 object-contain"
                            alt="Logo"
                        >

                        <h4 class="text-sm font-black">
                            {{ form.business_name || 'SOTANO RESTOBAR' }}
                        </h4>

                        <p v-if="form.ruc" class="text-[10px]">
                            RUC: {{ form.ruc }}
                        </p>

                        <p v-if="form.address" class="text-[10px]">
                            {{ form.address }}
                        </p>

                        <p v-if="form.phone" class="text-[10px]">
                            Tel: {{ form.phone }}
                        </p>

                        <p class="text-xs font-black mt-2">
                            TICKET DE VENTA
                        </p>
                    </div>

                    <div class="text-xs mt-3 space-y-1">
                        <p><strong>Pedido:</strong> #001</p>
                        <p><strong>Mesa:</strong> Mesa 01</p>
                        <p><strong>Fecha:</strong> 25/06/2026</p>
                        <p><strong>Hora:</strong> 18:45:20</p>
                    </div>

                    <div class="border-t border-b border-dashed border-black my-3 py-2 text-xs">
                        <div class="flex justify-between">
                            <span>1 x Producto</span>
                            <span>S/ 25.00</span>
                        </div>

                        <div class="flex justify-between">
                            <span>2 x Bebida</span>
                            <span>S/ 16.00</span>
                        </div>
                    </div>

                    <div class="flex justify-between text-sm font-black border-b border-dashed border-black pb-2">
                        <span>TOTAL</span>
                        <span>S/ 41.00</span>
                    </div>

                    <div class="text-center text-xs mt-3">
                        <p>{{ form.ticket_message || 'Gracias por su preferencia' }}</p>
                        <p>Vuelva pronto</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-8 rounded-2xl border-2 border-red-300 bg-red-50 p-6">
    <h3 class="text-xl font-black text-red-700">
        ⚠️ Reiniciar sistema
    </h3>

    <p class="mt-2 text-sm text-red-700">
        Elimina todos los datos de ventas, pedidos, caja, compras,
        productos, clientes, kardex y reportes.
    </p>

    <p class="mt-2 text-sm font-bold text-zinc-700">
        Se conservarán el usuario administrador, las 20 mesas, la Barra,
        los datos del negocio y las impresoras.
    </p>

    <label class="mt-5 block text-sm font-black text-red-700">
        Escribe REINICIAR SOTANO para confirmar
    </label>

    <input
        v-model="resetConfirmation"
        type="text"
        autocomplete="off"
        placeholder="REINICIAR SOTANO"
        class="mt-2 w-full max-w-md rounded-xl border-red-300"
    />

    <button
        type="button"
        @click="resetSystem"
        :disabled="
            resettingSystem ||
            resetConfirmation !== 'REINICIAR SOTANO'
        "
        class="mt-4 rounded-xl bg-red-600 px-6 py-3 font-black text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
    >
        {{
            resettingSystem
                ? 'Reiniciando...'
                : '🗑 Reiniciar todos los datos'
        }}
    </button>

    <p
        v-if="$page.props.errors?.reset"
        class="mt-3 text-sm font-bold text-red-700"
    >
        {{ $page.props.errors.reset }}
    </p>
</div>
    </AuthenticatedLayout>
</template>
