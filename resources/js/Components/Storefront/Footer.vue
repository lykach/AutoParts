<script setup>
const currentYear = new Date().getFullYear()
</script>

<template>
    <footer class="bg-gray-800 text-white p-6">
        <div class="mx-auto max-w-7xl text-center">
            <p>© {{ currentYear }} Auto Parts</p>
        </div>
    </footer>
</template>