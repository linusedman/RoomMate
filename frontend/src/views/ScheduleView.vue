<template>
  <div class="schedule-view">
    <h4>Schedule</h4>

    <div v-if="!filter.start || !filter.end" class="text-muted">Select day and time in the filter panel</div>

    <div v-else>
      <div class="subtitle mb-2">{{ filter.day }}</div>

      <div class="schedule-grid">
        <div class="time-header-wrapper" ref="timeHeaderWrapper">
          <div class="time-header" :style="ticksGridStyle">
            <div class="time-cell" v-for="t in hours" :key="t">{{ t }}</div>
          </div>
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
            :scrollX="scrollX"
            @confirmBooking="onConfirmBooking"
            @scrollX="syncScroll"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

import { computed } from 'vue'
import RoomSchedule from '../components/RoomSchedule.vue'

const timeHeaderWrapper = ref(null)
const scrollX = ref(0)

const props = defineProps({
  filter: Object,
  rooms: Array,
  bookings: Array
})
const emit = defineEmits(['booked'])

const hourWidth = 60

const dayStart = computed(() => props.filter && props.filter.start ? new Date(props.filter.start) : null)
const dayEnd = computed(() => props.filter && props.filter.end ? new Date(props.filter.end) : null)

const hours = computed(() => {
  if (!props.filter.start || !props.filter.end) return []
  const s = new Date(props.filter.start)
  const e = new Date(props.filter.end)
  const arr = []
  const cur = new Date(s)
  cur.setMinutes(0,0,0)
  while (cur <= e) {
    arr.push(cur.toTimeString().slice(0,5))
    cur.setHours(cur.getHours()+1)
  }
  return arr
})

const ticksGridStyle = computed(() => {
  const count = Math.max(1, hours.value.length)
  return {
    display: 'grid',
    gridTemplateColumns: `repeat(${count}, ${hourWidth}px)`,
    width: `${count * hourWidth}px`
  }
})

const roomsSorted = computed(() => (props.rooms || []).slice().sort((a,b)=>a.id-b.id))

function syncScroll(x) {
  scrollX.value = x
  if (timeHeaderWrapper.value) {
    timeHeaderWrapper.value.scrollLeft = x
  }
}

function bookingsForRoom(roomId) {
  return (props.bookings || []).filter(b => Number(b.room_id) === Number(roomId))
}

function toMysqlDatetime(isoLocal) {
  return isoLocal.replace('T', ' ') + ':00'
}

async function onConfirmBooking({ roomId, startISO, endISO }) {
  const form = new URLSearchParams({
    room_id: roomId,
    start_time: toMysqlDatetime(startISO),
    end_time: toMysqlDatetime(endISO)
  })
  try {
    const res = await fetch("http://localhost/RoomMate/backend/pages/book.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: form.toString()
    })
    const data = await res.json()
    if (data.status === 'success') {
      emit('booked')
      
    } else {
      alert(data.message || 'Booking failed')
    }
  } catch (e) {
    console.error(e)
    alert('Network error')
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
</style>
