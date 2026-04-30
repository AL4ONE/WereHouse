<script setup>
import { RouterView } from 'vue-router'
import { ref, watch, onMounted } from 'vue'

const isLightMode = ref(localStorage.getItem('theme') === 'light')

watch(isLightMode, (val) => {
  if (val) {
    document.documentElement.setAttribute('data-theme', 'light')
    localStorage.setItem('theme', 'light')
  } else {
    document.documentElement.removeAttribute('data-theme')
    localStorage.setItem('theme', 'dark')
  }
}, { immediate: true })

// Listen for theme changes from other components
onMounted(() => {
  window.addEventListener('storage', (e) => {
    if (e.key === 'theme') {
      isLightMode.value = e.newValue === 'light'
    }
  })
})

</script>

<template>
  <RouterView />
</template>

<style>

</style>
