<template>
  <div class="main-view d-flex">
    <div class="left-panel p-3">
      <FilterPanel :initialStart="filter.start" :initialEnd="filter.end" @filter="onFilter" />
      <ScheduleView
        :filter="filter"
        :rooms="rooms"
        :bookings="bookings"
        @booked="refreshData"
      />
    </div>

    <div class="right-panel p-3">
      <FloorView
        :filter="filter"
        :rooms="rooms"
        :bookings="bookings"
        @selectedRoom="onRoomSelected"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import FilterPanel from '../components/FilterPanel.vue'
import ScheduleView from './ScheduleView.vue'
import FloorView from './FloorView.vue'

const filter = ref({ day: '', start: '', end: '' })
const rooms = ref([])
const bookings = ref([])

function onFilter(f) {
  filter.value = f
}

function onRoomSelected(roomId) {
  // for future highlight in ScheduleView
}

async function refreshData() {
  try {
    const r1 = await fetch("http://localhost/RoomMate/backend/pages/get_rooms.php", { credentials: "include" })
    rooms.value = await r1.json()

    const r2 = await fetch("http://localhost/RoomMate/backend/pages/get_bookings.php", { credentials: "include" })
    bookings.value = await r2.json()
  } catch (e) {
    console.error(e)
  }
}

onMounted(refreshData)
</script>

<style scoped>
.main-view { display:flex; gap:1rem; }
.left-panel, .right-panel { width:50%; }
</style>
