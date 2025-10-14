<template>
  <div class="main-view d-flex">
    <div class="left-panel p-3">
      <FilterPanel 
        ref="filterPanelRef"
        :initialStart="filter.start" 
        :initialEnd="filter.end" 
        @filter="onFilter" 
        />
      <ScheduleView
        :filter="filter"
        :rooms="rooms"
        :bookings="bookings"
        @booked="refreshData"
        @resetFilter="resetFilter"
      />
    </div>

    <div class="right-panel p-3">
      <LayoutView
        :filter="filter"
        :rooms="rooms"
        :allRooms="allRooms"
        :floors="floors"
        :bookings="bookings"
        @selectedRoom="onRoomSelected"
      />
      <CurrentBookings />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import FilterPanel from '../components/FilterPanel.vue'
import ScheduleView from './ScheduleView.vue'
import LayoutView from './LayoutView.vue'
import CurrentBookings from '../components/CurrentBookings.vue'

const filter = ref({ day: '', start: '', end: '' })
const rooms = ref([])
const bookings = ref([])
const floors = ref([])
const filterPanelRef = ref(null)
const allRooms = ref([])

function onFilter(f) {
  Object.assign(filter.value, f)
  nextTick(() => {
    refreshData()
  })
}

function resetFilter() {
  filter.value.instrumentId = ''
  refreshData()
 
  if (filterPanelRef.value?.resetInstrument) {
    filterPanelRef.value.resetInstrument()
  }
}


async function refreshData() {
  try {
    const form = new URLSearchParams()

    if (filter.value.instrumentId) {
      form.append('instrumentId', filter.value.instrumentId)
    }

    const r1 = await fetch("http://localhost/RoomMate/backend/pages/get_rooms.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: form.toString()
    })
    rooms.value = await r1.json()

    const f = filter.value
    const start = new Date(f.start)
    const end = new Date(f.end)

    const filteredRooms = rooms.value.filter(room => {
    const bookingsForRoom = bookings.value.filter(b => Number(b.room_id) === Number(room.id))

    const fullyBooked = bookingsForRoom.some(b => {
      const bs = new Date(b.start_time)
      const be = new Date(b.end_time)
      return bs <= start && be >= end
    })

    return !fullyBooked
  })

  rooms.value = filteredRooms

    const rAll = await fetch("http://localhost/RoomMate/backend/pages/get_rooms.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams().toString() // empty filter to get all rooms
    })
    allRooms.value = await rAll.json()

    const r2 = await fetch("http://localhost/RoomMate/backend/pages/get_bookings.php", { credentials: "include" })
    bookings.value = await r2.json()

  } catch (e) {
    console.error(e)
  }
}
function onRoomSelected(room) {
}

async function fetchFloors() {
  try {
    const r3 = await fetch("http://localhost/RoomMate/backend/pages/get_floors.php", {credentials: "include"})
    floors.value = await r3.json()
  } catch (e) {
    console.error(e)
  }
}

onMounted(async () => {
    await refreshData()
    await fetchFloors()
})
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
