<script setup>
import { RouterView } from 'vue-router'
// 🔥 NUEVO: Importamos el componente guardián de caídas del servidor
import ErrorServidorOverlay from './components/ErrorServidorOverlay.vue'
</script>

<template>
  <RouterView />
  
  <ErrorServidorOverlay />
</template>

<style>
/* Estilos globales básicos para que el proyecto se vea limpio */
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f0f2f5;
  color: #333;
}
</style>