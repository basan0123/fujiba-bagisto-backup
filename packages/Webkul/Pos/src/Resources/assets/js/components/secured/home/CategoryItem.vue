<template>
    <div class="flex items-center gap-2.5">
        <span
            @click="handleCategoryClick(category.id)"
            :class="[
                'flex h-8 cursor-pointer items-center whitespace-nowrap rounded-lg border-2 border-transparent px-3 py-2 text-base font-medium transition-colors duration-200',
                activeCategoryId === category.id
                    ? 'bg-gray-100 dark:bg-gray-800 text-slate-600 dark:text-slate-200 !border-slate-600 dark:!border-slate-400'
                    : 'text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
        >
            {{ category.name }}
        </span>

        <template
            v-if="
                category.children?.length
                && (
                    activeCategoryId === category.id
                    || isChildActive(category.children)
                )"
        >
            <span
                class="icon-chevron-right text-xl text-slate-600 dark:text-slate-300"
                aria-hidden="true"
            ></span>

            <category-item
                v-for="child in category.children"
                :key="`child-${category.id}-${child.id}`"
                :category="child"
                :active-category-id="activeCategoryId"
                :handle-category-click="handleCategoryClick"
                :is-child-active="isChildActive"
            />
        </template>
    </div>
</template>

<script setup>
    defineProps({
        category: Object,
        activeCategoryId: [Number, String],
        handleCategoryClick: Function,
        isChildActive: Function
    });
</script>