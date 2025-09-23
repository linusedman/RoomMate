<template>
  <div class="floor-selector">
    <label>Select Floor:</label>
    <select v-model="selected" @change="emit('change', selected)">
      <option v-for="floor in floors" :key="floor" :value="floor">Floor {{ floor }}</option>
    </select>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  floors: Array
})

const emit = defineEmits(['change'])

const selected = ref(props.floors[0] || 1)
</script>

<style scoped>
.floor-selector {
  margin-bottom: 1rem;
}
</style>
