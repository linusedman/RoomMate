<template>
  <div class="schedule-view">
    <h4>Schedule</h4>

    <div>
      <div class="subtitle mb-2">{{ filter.day }}</div>

      <div class="schedule-grid">
        <div class="time-header-wrapper" ref="timeHeaderWrapper">
          <div class="time-header" :style="ticksGridStyle">
            <div class="time-cell" v-for="t in hours" :key="t">{{ t }}</div>
          </div>
        </div>

        <div v-if="roomsSorted.length === 0" class="no-rooms">
          <p>No rooms matched your selected filter.</p>
          <button @click="showAllRooms">Show all rooms</button>
        </div>

        <div class="rows">
          <RoomSchedule
            v-for="room in roomsSorted"
            :key="room.id"
            :room="room"
            :dayStart="dayStart"
            :dayEnd="dayEnd"
            :bookings="bookingsForRoom(room.id)"
            :ticksGridStyle="ticksGridStyle"
            :hourWidth="hourWidth"
            :bookingStatus="bookingStatus"
            :scrollX="scrollX"
            :highlighted="selectedFavoriteId === room.id"
            :favorites="favorites"
            @confirmBooking="onConfirmBooking"
            @scrollX="syncScroll"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

import RoomSchedule from '../components/RoomSchedule.vue'

const timeHeaderWrapper = ref(null)
const scrollX = ref(0)

const props = defineProps({
  filter: Object,
  rooms: Array,
  bookings: Array,
  selectedFavoriteId: Number,
  favorites: Array
})
const emit = defineEmits(['booked', 'resetFilter'])
const bookingStatus = ref(null)

const hourWidth = 60

const today = new Date()
const defaultStart = new Date(today)
defaultStart.setHours(8, 0, 0, 0)

const defaultEnd = new Date(today)
defaultEnd.setHours(17, 0, 0, 0)


const dayStart = computed(() =>
  props.filter && props.filter.start
    ? new Date(props.filter.start)
    : defaultStart
)

const dayEnd = computed(() =>
  props.filter && props.filter.end
    ? new Date(props.filter.end)
    : defaultEnd
)


const hours = computed(() => {
  const s = dayStart.value
  const e = dayEnd.value
  const arr = []
  const cur = new Date(s)
  cur.setMinutes(0, 0, 0)
  while (cur <= e) {
    arr.push(cur.toTimeString().slice(0, 5))
    cur.setHours(cur.getHours() + 1)
  }
  return arr
})

function showAllRooms() {
  emit('resetFilter')
}

const ticksGridStyle = computed(() => {
  const count = Math.max(1, hours.value.length)
  return {
    display: 'grid',
    gridTemplateColumns: `repeat(${count}, ${hourWidth}px)`,
    width: `${count * hourWidth}px`
  }
})

const roomsSorted = computed(() => {
  const all = props.rooms || []
  const favoriteIds = new Set((props.favorites || []).map(f => f.room_id))
  const favorites = all.filter(r => favoriteIds.has(r.id)).sort((a,b) => a.id - b.id)
  const nonFavorites = all.filter(r => !favoriteIds.has(r.id)).sort((a,b) => a.booking_count - b.booking_count)
  return [...favorites, ...nonFavorites]
})

function syncScroll(x) {
  scrollX.value = x
  if (timeHeaderWrapper.value) {
    timeHeaderWrapper.value.scrollLeft = x
  }
}

function bookingsForRoom(roomId) {
  return (props.bookings || []).filter(b => Number(b.room_id) === Number(roomId))
}

function formatLocalTimeFromISO(isoString) {
    const d = new Date(isoString); // JS converts ISO to local timezone automatically
    const hours = String(d.getHours()).padStart(2,'0');
    const minutes = String(d.getMinutes()).padStart(2,'0');
    return `${hours}:${minutes}`;
}

async function onConfirmBooking({ roomId, startISO, endISO }) {

  const start = new Date(startISO)
  const end = new Date(endISO)
  const durationMinutes = (end - start) / 60000
   
  // Frontend restriction before fetch
  
  if (durationMinutes < 30) {
    const evt = new CustomEvent('bookingError', {
      detail: { roomId, message: 'Booking duration must be at least 30 minutes.' }
    })
    window.dispatchEvent(evt)
    return
  }
  if (durationMinutes > 8 * 60) {
    const evt = new CustomEvent('bookingError', {
      detail: { roomId, message: 'Booking duration cannot exceed 8 hours.' }
    })
    window.dispatchEvent(evt)
    return
  }

  const form = new URLSearchParams({
    room_id: roomId,
    start_time: startISO,
    end_time: endISO
  })
  try {
    const res = await fetch("http://localhost/RoomMate/backend/pages/book.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: form.toString()
    })

    const data = await res.json()
    const displayStart = formatLocalTimeFromISO(startISO)
    const displayEnd = formatLocalTimeFromISO(endISO)

    if (data.status === 'success') {
      emit('booked')
      const msg = `Room successfully booked from ${displayStart} to ${displayEnd}.`
      const evt = new CustomEvent('bookingSuccess', {
        detail: { roomId, message: msg }
      })
      window.dispatchEvent(evt)
    } else {
      const evt = new CustomEvent('bookingError', {
        detail: { roomId, message: data.message || 'Booking failed' }
      })
      window.dispatchEvent(evt)
    }
  } catch (e) {
    console.error(e)
    const evt = new CustomEvent('bookingError', {
      detail: { roomId, message: 'Network error' }
    })
    window.dispatchEvent(evt)
  }

}
</script>

<style scoped>
.schedule-grid {
  border: 1px solid #ddd;
  padding: 8px;
  overflow-x: hidden;
  position: relative;
}

.time-header-wrapper {
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
  overflow-x: auto;
  border-bottom: 1px solid #ccc;
  scrollbar-width: none;
}
.time-header-wrapper::-webkit-scrollbar { display: none; }

.time-header {
  display: grid;
  margin-bottom: 6px;
}
.time-cell {
  width: 60px;
  text-align: center;
  font-size: 12px;
  color: #333;
  box-sizing: border-box;
}
.rows {
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 60vh;
  overflow: auto;
}
.subtitle { font-weight: 600; }

.no-rooms {
  text-align: center;
  margin-top: 1rem;
  padding: 1rem;
  background-color: #f9f9f9;
}

.no-rooms p {
  font-size: 1rem;
  color: #555;
  margin-bottom: 0.5rem;
}

.no-rooms button {
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.no-rooms button:hover {
  background-color: #0056b3;
}

</style>