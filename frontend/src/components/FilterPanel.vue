<template>
  <div class="filter-panel mb-3">
    <h4>Filter</h4>
    <label>Day</label>
    <input type="date" v-model="day" />

    <div class="d-flex gap-2 mt-2">
      <div>
        <label>Start</label>
        <input type="time" v-model="startTime" />
      </div>
      <div>
        <label>End</label>
        <input type="time" v-model="endTime" />
      </div>
    </div>

    <div class="d-flex gap-2 mt-2">
      <button class="btn btn-primary" @click="applyFilter">Apply</button>
      <button class="btn btn-outline-secondary" @click="clearFilter">Clear</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  initialStart: { type: String, default: '' },
  initialEnd: { type: String, default: '' }
})
const emit = defineEmits(['filter'])

const day = ref('')
const startTime = ref('08:00')
const endTime = ref('17:00')

onMounted(() => {
  if (props.initialStart) {
    const s = new Date(props.initialStart)
    day.value = s.toISOString().slice(0,10)
    startTime.value = String(s.getHours()).padStart(2,'0') + ':' + String(s.getMinutes()).padStart(2,'0')
  }
  if (props.initialEnd) {
    const e = new Date(props.initialEnd)
    endTime.value = String(e.getHours()).padStart(2,'0') + ':' + String(e.getMinutes()).padStart(2,'0')
  }
})

function applyFilter() {
  if (!day.value) return
  const start = `${day.value}T${startTime.value}`
  const end = `${day.value}T${endTime.value}`
  emit('filter', { day: day.value, start, end })
}

function clearFilter() {
  day.value = ''
  startTime.value = '08:00'
  endTime.value = '17:00'
  emit('filter', { day: '', start: '', end: '' })
}
</script>

<style scoped>
.filter-panel input { display:block; }
</style>
