<template>
    <router-view v-slot="{ Component }">
        <Component :is="Component" />
    </router-view>
</template>
