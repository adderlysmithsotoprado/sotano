<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const role = computed(() => user.value?.role ?? '');

const currentRoute = (name) => {
    return route().current(name);
};

const canSee = (roles) => {
    return roles.includes(role.value);
};

const menuSections = computed(() => [
    {
        title: 'Principal',
        items: [
            { label: 'Dashboard', icon: '🏠', route: 'dashboard', roles: ['admin', 'cajero'] },
            { label: 'Mesas', icon: '🍽️', route: 'mesas', roles: ['admin', 'cajero', 'mozo'] },
            { label: 'Comandas', icon: '👨‍🍳', route: 'comandas', roles: ['admin', 'cocina', 'bar'] },
        ],
    },
    {
        title: 'Inventario',
        items: [
            { label: 'Productos', icon: '📦', route: 'productos', roles: ['admin', 'cajero'] },
            { label: 'Categorías', icon: '🗂️', route: 'categorias', roles: ['admin', 'cajero'] },
            { label: 'Modificadores', icon: '🍕', route: 'modificadores', roles: ['admin', 'cajero'] },
            { label: 'Promociones',icon: '🎉',route: 'promociones',roles: ['admin', 'cajero'],},
            { label: 'Kardex', icon: '📋', route: 'kardex', roles: ['admin', 'cajero'] },
            { label: 'Compras', icon: '🧾', route: 'compras', roles: ['admin', 'cajero'] },
            { label: 'Proveedores', icon: '🚚', route: 'proveedores', roles: ['admin', 'cajero'] },
            { label: 'Stock Bajo', icon: '📉', route: 'stock.bajo', roles: ['admin', 'cajero'] },
        ],
    },
    {
        title: 'Gestión',
        items: [
            { label: 'Clientes', icon: '👥', route: 'clientes', roles: ['admin', 'cajero'] },
            { label: 'Usuarios', icon: '👤', route: 'usuarios', roles: ['admin'] },
        ],
    },
    {
        title: 'Finanzas',
        items: [
            { label: 'Caja', icon: '💰', route: 'caja', roles: ['admin', 'cajero'] },
            { label: 'Reportes', icon: '📊', route: 'reportes', roles: ['admin'] },
            { label: 'Historial Ventas', icon: '🧾', route: 'ventas.historial', roles: ['admin', 'cajero'] },
        ],
    },
    {
        title: 'Sistema',
        items: [
            { label: 'Configuración', icon: '⚙️', route: 'configuracion', roles: ['admin'] },
            { label: 'Auditoría', icon: '🛡️', route: 'auditoria', roles: ['admin'] },
            { label: 'Impresoras', icon: '🖨️', route: 'impresoras', roles: ['admin'] },
            { label: 'Respaldos',icon: '💾',route: 'respaldos', roles: ['admin'],},
        ],
    },
]);
</script>

<template>
    <div class="min-h-screen bg-zinc-100">
        <aside class="fixed left-0 top-0 h-screen w-72 bg-black text-white flex flex-col overflow-hidden">
            <div class="shrink-0 border-b border-zinc-800 p-6 text-center">
                <img
                    v-if="$page.props.business?.logo_path"
                    :src="'/storage/' + $page.props.business.logo_path"
                    alt="SOTANO POS"
                    class="w-40 mx-auto mb-4 object-contain"
                >

                <img
                    v-else
                    src="/images/logo.png"
                    alt="SOTANO POS"
                    class="w-40 mx-auto mb-4 object-contain"
                >

                <h1 class="text-xl font-black tracking-wide">
                    SOTANO POS
                </h1>

                <p class="text-xs text-zinc-500 mt-1 tracking-widest">
                    SISTEMA DE VENTAS
                </p>
            </div>

            <nav class="sidebar-scroll flex-1 min-h-0 overflow-y-auto px-4 py-5 pr-2 space-y-6">
                <section
                    v-for="section in menuSections"
                    :key="section.title"
                >
                    <p class="px-4 mb-2 text-[11px] font-black uppercase tracking-[0.18em] text-zinc-600">
                        {{ section.title }}
                    </p>

                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items.filter(item => canSee(item.roles))"
                            :key="item.route"
                            :href="route(item.route)"
                            class="group flex items-center gap-3 rounded-xl px-4 py-3 transition-all duration-200"
                            :class="currentRoute(item.route)
                                ? 'bg-white text-black shadow font-bold'
                                : 'text-zinc-400 hover:bg-zinc-900 hover:text-white'"
                        >
                            <span class="text-lg">
                                {{ item.icon }}
                            </span>

                            <span class="text-sm">
                                {{ item.label }}
                            </span>
                        </Link>
                    </div>
                </section>
            </nav>

            <div class="shrink-0 border-t border-zinc-800 p-4 bg-black">
                <div class="bg-zinc-900 rounded-2xl p-4">
                    <p class="font-semibold">
                        {{ user?.name ?? 'Usuario' }}
                    </p>

                    <p class="text-xs text-zinc-500 uppercase">
                        {{ user?.role ?? 'sin rol' }}
                    </p>

                    <div class="mt-3 flex gap-2">
                        <Link
                            :href="route('profile.edit')"
                            class="bg-zinc-800 hover:bg-zinc-700 px-3 py-2 rounded-lg text-xs font-bold"
                        >
                            Perfil
                        </Link>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="bg-red-600 hover:bg-red-700 px-3 py-2 rounded-lg text-xs font-bold"
                        >
                            Salir
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <div class="ml-72">
            <header class="bg-white border-b border-zinc-200 h-20 flex items-center justify-between px-8 shadow-sm">
                <div>
                    <slot name="header" />
                </div>

                <div class="text-sm text-zinc-500">
                    SOTANO POS v1.0 - Ing. Adderly Soto Prado
                </div>
            </header>

            <main class="p-8">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.sidebar-scroll {
    scrollbar-width: thin;
    scrollbar-color: #52525b transparent;
}

.sidebar-scroll::-webkit-scrollbar {
    width: 6px;
}

.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
    background: #3f3f46;
    border-radius: 999px;
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: #71717a;
}
</style>
