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
        :favorites="favorites"
        :selectedFavoriteId="selectedFavoriteId"
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
        :favorites="favorites"
        :instruments="instruments"
        :instrumentTypes="instrumentTypes"
        :selectedFavoriteId="selectedFavoriteId"
        @selectedRoom="onRoomSelected"
        @favoritesChanged="refreshData"
      />
      <CurrentBookings />
      <FavoriteRooms
        :selectedFavoriteId="selectedFavoriteId"
        @selectFavorite="onFavoriteSelected"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import FilterPanel from '../components/FilterPanel.vue'
import ScheduleView from './ScheduleView.vue'
import LayoutView from './LayoutView.vue'
import CurrentBookings from '../components/CurrentBookings.vue'
import FavoriteRooms from '../components/FavoriteRooms.vue'

const filter = ref({ day: '', start: '', end: '' })
const rooms = ref([])
const bookings = ref([])
const floors = ref([])
const filterPanelRef = ref(null)
const allRooms = ref([])
const favorites = ref([])
const instruments = ref([])
const instrumentTypes = ref([])
const selectedFavoriteId = ref(null)

function onFavoriteSelected(id) {
  selectedFavoriteId.value = id
}

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
    const rFav = await fetch("http://localhost/RoomMate/backend/pages/favorites.php", {
      credentials: "include"
    })
    const favoriteRooms = await rFav.json()
    favorites.value = Array.isArray(favoriteRooms) ? favoriteRooms : []

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

    const r3 = await fetch("http://localhost/RoomMate/backend/pages/get_floors.php", { credentials: "include" })
    floors.value = await r3.json()

    const rTypes = await fetch("http://localhost/RoomMate/backend/pages/get_instrument_types.php", {
      credentials: "include"
    })
    instrumentTypes.value = await rTypes.json()

    const rInstruments = await fetch("http://localhost/RoomMate/backend/pages/get_instruments.php", {
      credentials: "include"
    })
    instruments.value = await rInstruments.json()

  } catch (e) {
    console.error(e)
  }
  
}


function onRoomSelected(room) {
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
