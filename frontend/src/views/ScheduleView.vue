<template>
  <div class="schedule-view">
    <h4>Schedule</h4>

    <div>
      <div class="subtitle mb-2">{{ filter.day }}</div>

      <div class="schedule-grid">
        <div class="time-header-wrapper" ref="timeHeaderWrapper">
          <div class="time-header" :style="ticksGridStyle">
            <div class="time-cell" v-for="t in hours" :key="t">{{ t.label }}</div>
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
            :bookingStatus="bookingStatus"
            :highlighted="selectedFavoriteId === room.id"
            :favorites="favorites"
            @confirmBooking="onConfirmBooking"
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

const props = defineProps({
  filter: Object,
  rooms: Array,
  bookings: Array,
  selectedFavoriteId: Number,
  favorites: Array
})
const emit = defineEmits(['booked', 'resetFilter'])
const bookingStatus = ref(null)

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
  while (cur < e) {
    const hourStart = new Date(cur);
    const hourEnd = new Date(cur);
    hourEnd.setHours(hourEnd.getHours() + 1);

    const start = Math.max(hourStart.getTime(), s.getTime());
    const end = Math.min(hourEnd.getTime(), e.getTime());
    const minutes = Math.max(0, (end - start) / 60000); // milliseconds → minutes
    if (minutes >= 45) {
      arr.push({
        label: hourStart.toTimeString().slice(0, 5),
        minutes
      });
    } else {
      arr.push({
        label: "",
        minutes
      });
    }


    cur.setHours(cur.getHours() + 1);
  }
  return arr
})

function showAllRooms() {
  emit('resetFilter')
}

const ticksGridStyle = computed(() => {
  const columns = hours.value.map(h => `${h.minutes}fr`).join(' ');
  return {
    display: 'grid',
    gridTemplateColumns: columns,
    width: `100%`
  }
})

const roomsSorted = computed(() => {
  const all = props.rooms || []
  const favoriteIds = new Set((props.favorites || []).map(f => f.room_id))
  const favorites = all.filter(r => favoriteIds.has(r.id)).sort((a,b) => a.id - b.id)
  const nonFavorites = all.filter(r => !favoriteIds.has(r.id)).sort((a,b) => a.booking_count - b.booking_count)
  return [...favorites, ...nonFavorites]
})

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
  display: grid;
  grid-template-columns: max-content 1fr;
  grid-template-rows: auto 1fr;
  border: 1px solid #ddd;
  padding: 8px;
  position: relative;
  gap: 6px;
  overflow: auto;
  max-height: 60vh;
}

:deep(.room-row) {
  display: contents;
  position: relative;
}

:deep(.room-label) {
  grid-column: 1;
}

:deep(.timeline-wrapper) {
  grid-column: 2;
}

.time-header-wrapper {
  position: sticky;
  grid-column: 2;
  top: 0;
  background: white;
  z-index: 10;
  border-bottom: 1px solid #ccc;
  height: 40px
}

.time-header {
  display: grid;
  margin-bottom: 6px;
  z-index: 10;
}

.time-cell {
  text-align: left;
  font-size: 12px;
  color: #333;
  box-sizing: border-box;
  z-index: 10;
  overflow: hidden;
  text-overflow: clip;
}

.rows {
  display: contents;
  margin-top: 40px;
  row-gap: 6px;
  max-height: 60vh;
  overflow: auto;
}

.subtitle { font-weight: 600; }

.no-rooms {
  text-align: center;
  margin-top: 1rem;
  padding: 1rem;
  background-color: #f9f9f9;
  grid-column: 2
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