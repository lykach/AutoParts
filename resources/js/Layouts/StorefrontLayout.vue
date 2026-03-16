<script setup>
import Header from '../Components/Storefront/Header.vue'
import Footer from '../Components/Storefront/Footer.vue'
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <Header />

        <main class="flex-1">
            <slot />
        </main>

        <Footer />
    </div>
</template>