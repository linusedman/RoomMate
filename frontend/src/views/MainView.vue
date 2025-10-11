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
      <LayoutView
        :filter="filter"
        :rooms="rooms"
        :floors="floors"
        :bookings="bookings"
        @selectedRoom="onRoomSelected"
      />
      <CurrentBookings />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import FilterPanel from '../components/FilterPanel.vue'
import ScheduleView from './ScheduleView.vue'
import LayoutView from './LayoutView.vue'
import CurrentBookings from '../components/CurrentBookings.vue'

const filter = ref({ day: '', start: '', end: '' })
const rooms = ref([])
const bookings = ref([])
const floors = ref([])

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

    const r3 = await fetch("http://localhost/RoomMate/backend/pages/get_floors.php", { credentials: "include" })
    floors.value = await r3.json()
  } catch (e) {
    console.error(e)
  }
}

onMounted(refreshData)
</script>

<style scoped>
.main-view {
  display: flex; 
  gap: 1rem;
  min-height: 70vh;
  align-items: flex-start;
}

.left-panel,
.right-panel {
  flex: 1 1 0%;
  min-width: 0;
}

.right-panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.right-panel > * {
  flex: 0 0 auto;
  align-self: stretch;
}
</style>
