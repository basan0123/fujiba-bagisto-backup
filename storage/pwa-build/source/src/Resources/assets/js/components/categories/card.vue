<template>
    <li class="category-card">
        <router-link :to="'/categories/' + category.id">
            <div class="image">
                <img
                    alt="category-image"
                    onerror="this.src = '/themes/pwa/default/build/assets/images/category-image.png'"
                    :src="category.logo_url
                          ? category.logo_url
                          : '/themes/pwa/default/build/assets/images/category-image.png'"
                />
            </div>

            <div class="name">
                {{ category.name }}
            </div>
        </router-link>
    </li>
</template>

<script>
    export default {
        name: 'category-card',

        props: ['category']
    }
</script>

<style lang="scss" scoped>
    li.category-card {
        flex: 0 0 auto;
        width: 76px;
        margin-right: 10px;
        list-style: none;
        text-align: center;

        a {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .image {
            width: 64px;
            height: 64px;
            margin: 0 auto 6px;
            border-radius: 16px;
            overflow: hidden;
            background: #f5f0eb;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .name {
            font-size: 11px;
            font-weight: 600;
            line-height: 1.25;
            color: #1a0800;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            min-height: 28px;
        }
    }
</style>
