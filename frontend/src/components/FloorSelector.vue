<template>
  <div class="panel-selector d-flex">
    <div
      class="panel-box"
      v-for="floor in floors"
      :key = floor
      :class="{ active: selected === floor }"
      @click="onClick(floor)"
    >
      {{ floor }}
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  floors: Array
})

const emit = defineEmits(['change'])

const selected = ref(props.floors[0] || 1)

async function onClick(id) {
  selected.value = id;
  emit('change', selected.value)
}
</script>

<style scoped>
.panel-selector {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  justify-content: center;
  width:100%
}

.panel-box {
  padding: 1rem 2rem;
  border: 1px solid #ccc;
  border-radius: 0.5rem;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s;
  user-select: none;
}

.panel-box:hover {
  background-color: #f0f0f0;
}

.panel-box.active {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}
</style>
