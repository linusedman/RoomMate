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
        ref="scheduleViewRef"
        :filter="filter"
        :rooms="rooms"
        :bookings="bookings"
        :favorites="favorites"
        :selectedFavoriteId="selectedFavoriteId"
        @booked="onBook"
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
      <CurrentBookings 
        ref="currentBookingsRef" 
        @bookingDeleted="onBookingDeleted"
      />
      <FavoriteRooms
        :selectedFavoriteId="selectedFavoriteId"
        @selectFavorite="onFavoriteSelected"
        :favorites="favorites"
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

const filter = ref({ day: '', start: '', end: '', instrumentId: [] })
const rooms = ref([])
const bookings = ref([])
const floors = ref([])
const filterPanelRef = ref(null)
const allRooms = ref([])
const favorites = ref([])
const instruments = ref([])
const instrumentTypes = ref([])
const selectedFavoriteId = ref(null)
const currentBookingsRef = ref(null)
const stats = ref([])


function onFavoriteSelected(id) {
  selectedFavoriteId.value = id
}

function onFilter(f) {
  console.log('MainView received filter:', f)
  console.log('filter.value before assign:', filter.value)
  Object.assign(filter.value, f)
  console.log('filter.value after assign:', filter.value)
  nextTick(() => {
    refreshData()
  })
}


function resetFilter() {
  filter.value.instrumentId = []
  refreshData()
 
  if (filterPanelRef.value?.resetInstrument) {
    filterPanelRef.value.resetInstrument()
  }
}


async function refreshData() {
  await fetchFavorites()
  await fetchInstruments()
  await fetchInstrumentTypes()
  await fetchBookings()
  await updateFilteredRooms()

  await nextTick()
  await currentBookingsRef.value?.loadBookings()

}


function onRoomSelected(room) {
}

function onBook() {
  fetchBookings()
  currentBookingsRef.value?.loadBookings()
}

async function fetchFavorites() {
  try {
    const rFav = await fetch("http://localhost/RoomMate/backend/pages/favorites.php", {
      credentials: "include"
    })
    const favoriteRooms = await rFav.json()
    favorites.value = Array.isArray(favoriteRooms) ? favoriteRooms : []
  } catch (e) {
    console.error(e)
  }
}

async function updateFilteredRooms() {
  try {
    console.log('updateFilteredRooms called with filter:', filter.value)
    console.log('instrumentId:', filter.value.instrumentId)
    console.log('Is array?', Array.isArray(filter.value.instrumentId))
    const form = new URLSearchParams()

    if (filter.value.instrumentId && 
        Array.isArray(filter.value.instrumentId) && 
        filter.value.instrumentId.length > 0) {
      
      filter.value.instrumentId.forEach(id => {
        form.append('instrumentId[]', id)
      })
    }

    const r1 = await fetch("http://localhost/RoomMate/backend/pages/get_rooms.php", {
      method: "POST",
      credentials: "include",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: form.toString()
    })
    rooms.value = await r1.json()

    const rS = await fetch("http://localhost/RoomMate/backend/pages/get_room_statistics.php", {
      credentials: "include",
    })
    const statsJSON = await rS.json()
    stats.value = statsJSON.status === "success" ? statsJSON.data : [];

    const merged = rooms.value.map(room => {
      const match = stats.value.find(s => Number(s.room_id) === Number(room.id));
      return {
        ...room,
        booking_count: match ? parseInt(match.booking_count) : 0
      };
    });

    rooms.value = merged

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
  } catch (e) {
    console.error(e)
  }
}

async function fetchAllRooms() {
  try {

    const rAll = await fetch("http://localhost/RoomMate/backend/pages/get_rooms.php", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams().toString() // empty filter to get all rooms
    })
    allRooms.value = await rAll.json()
  } catch (e) {
    console.error(e)
  }
}

async function fetchBookings() {
  try {
    const r2 = await fetch("http://localhost/RoomMate/backend/pages/get_bookings.php", { credentials: "include" })
    bookings.value = await r2.json()
  } catch (e) {
    console.error(e)
  }
}

async function fetchInstrumentTypes() {
  try {
    const rTypes = await fetch("http://localhost/RoomMate/backend/pages/get_instrument_types.php", {
      credentials: "include"
    })
    instrumentTypes.value = await rTypes.json()
  } catch (e) {
    console.error(e)
  }
}

async function fetchInstruments() {
  try {
    const rInstruments = await fetch("http://localhost/RoomMate/backend/pages/get_instruments.php", {
      credentials: "include"
    })
    instruments.value = await rInstruments.json()
  } catch (e) {
    console.error(e)
  }
}

async function fetchFloors() {
  try {
    const r3 = await fetch("http://localhost/RoomMate/backend/pages/get_floors.php", {credentials: "include"})
    floors.value = await r3.json()
  } catch (e) {
    console.error(e)
  }
}

async function onBookingDeleted() {
  await refreshData()
}

onMounted(async () => {
    await refreshData()
    await fetchAllRooms()
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
