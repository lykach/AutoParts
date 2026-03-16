<script setup>
import { Head } from '@inertiajs/vue3'
import StorefrontLayout from '../../Layouts/StorefrontLayout.vue'
</script>

<template>
    <Head title="Storefront" />

    <StorefrontLayout>
        <div class="mx-auto max-w-7xl p-10">
            <h1 class="text-3xl font-bold">
                Головна сторінка магазину
            </h1>

            <p class="mt-4">
                Storefront layout працює.
            </p>
        </div>
    </StorefrontLayout>
</template>