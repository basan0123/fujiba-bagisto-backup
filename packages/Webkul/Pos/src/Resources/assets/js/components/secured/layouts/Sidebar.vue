<template>
    <div class="fixed top-16 z-[1000] hidden h-[calc(100vh-68px)] bg-white p-2 shadow-[-1px_0px_0px_0px_rgba(0,0,0,0.1)_inset] dark:bg-gray-900 lg:block">
        <div class="journal-scroll h-[calc(80vh-80px)] overflow-auto">
            <nav class="grid w-full">
                <router-link
                    v-for="(menuItem, index) in menuItems"
                    :key="index"
                    :to="menuItem.path"
                    :class="[
                        $route.path.split('/')[1] === menuItem.name 
                            ? 'text-slate-600 border-2 border-slate-600 bg-gray-100 dark:text-white dark:border-white dark:bg-gray-900' 
                            : 'text-neutral-400 dark:text-neutral-500'
                    ]"
                    class="grid h-20 w-20 cursor-pointer place-items-center content-center rounded-lg hover:text-slate-600 dark:hover:text-white"
                >
                    <i
                        class="icon text-2xl"
                        :class="menuItem.icon"
                    ></i>

                    <span class="text-center text-[12px] font-medium leading-4">
                        {{ $t(`pos.layout.sidebar.${menuItem.name}`) }}
                    </span>
                </router-link>
            </nav>
        </div>

        <div class="mt-4 grid items-center gap-1">
            <div class="flex items-center justify-center">
                <template v-if="agent?.imageUrl">
                    <img
                        :src="agent.imageUrl"
                        class="h-10 w-10 cursor-pointer rounded-full"
                        alt="profile image"
                    >
                </template>
                <template v-else>
                    <img
                        src="@images/user-placeholder.png"
                        class="h-10 w-10 cursor-pointer rounded-full"
                        alt="profile image"
                    >
                </template>
            </div>

            <div
                class="grid h-20 w-20 cursor-pointer place-items-center content-center rounded-lg text-neutral-400 hover:text-slate-600 dark:text-neutral-500 dark:hover:text-white"
                @click="logout()"
            >
                <i class="icon icon-logout text-2xl"></i>

                <span class="text-[12px] font-medium leading-4">
                    {{ $t('pos.layout.sidebar.logout') }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { ref, watchEffect } from 'vue';
    import { useOutlet } from '@src/composable/outlet';
    import { useRouter } from 'vue-router';
    import { useMutation } from '@vue/apollo-composable';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { LOGOUT } from '@src/graphql/session';

    /**
     * General use variables
     */
    const DB = useIndexedDB();
    const { getMenuItems } = useOutlet();
    const menuItems = getMenuItems();

    /**
     * Get Agent Profile
     */
    const agent = ref({});

    watchEffect(async () => {
        agent.value = await DB.getAgent()
    });

    /**
     * Logout
     */
    const { mutate:agentLogout } = useMutation(LOGOUT);

    const router = useRouter();

    const logout = async () => {
        try {
            const data = await agentLogout();

            if (data?.data?.agentLogout?.success) {
                localStorage.removeItem('accessToken');

                router.push({path: '/'});
            }
        } catch (err) {}
    };
</script>
