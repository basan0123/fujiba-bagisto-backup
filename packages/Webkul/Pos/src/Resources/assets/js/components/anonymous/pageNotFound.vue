<template>
	<div class="px-4 text-center">
        <div class="flex h-screen flex-col items-center justify-center gap-4">
            <h1 class="text-7xl font-extrabold tracking-tight text-slate-600 dark:text-slate-300 lg:text-9xl">
                404
            </h1>

            <p class="text-3xl font-bold tracking-tight text-slate-600 dark:text-slate-300 md:text-4xl">
                {{ $t('pos.page_not_found.title') }}
            </p>

            <p class="text-lg font-normal text-gray-600 dark:text-gray-400">
                {{ $t('pos.page_not_found.message') }}
            </p>

            <router-link
                to="/"
                class="primary-button"
            >
                <span class="icon-home text-2xl"></span>

                {{ $t('pos.page_not_found.back_btn_title') }}
            </router-link>
        </div>
    </div>
</template>
