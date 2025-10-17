<template>
  <path
    :d="path"
    stroke="black"
    stroke-width="0.5px"
    fill="#f0f0f0"
  />
</template>

<script setup>
const props = defineProps({
  floor: Number,
  path: String,
})

</script>

<style scoped>
</style>
