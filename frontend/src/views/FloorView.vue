<template>
  <div class="floor-view container">
    <FloorSelector :floors="availableFloors" @change="changeFloor" />

    <div class="room-layout d-flex flex-wrap">
      <div
        v-for="room in roomsOnFloor"
        :key="room.id"
        class="room-wrapper"
      >
        <RoomBox
          :room="room"
          :booked="isBooked(room.id)"
          :availableByFilter="isAvailableDuringFilter(room.id)"
          :selected="activePopoverId === room.id"
          @select="onRoomClick"
        />

        
        <div
          v-if="activePopoverId === room.id"
          class="inline-popover"
          @click.stop
        >
          <RoomPopover :room="getRoom(room.id)" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import FloorSelector from '../components/FloorSelector.vue'
import RoomBox from '../components/RoomBox.vue'
import RoomPopover from '../components/RoomPopover.vue'

const props = defineProps({
  filter: Object,
  rooms: Array,
  bookings: Array
})
const emit = defineEmits(['selectedRoom'])

const roomsRef = ref(props.rooms || [])
const bookingsRef = ref(props.bookings || [])
const selectedFloor = ref(1)
const activePopoverId = ref(null)
const filter = ref(props.filter || { start:'', end:'' })

watch(() => props.rooms, (v) => roomsRef.value = v || [])
watch(() => props.bookings, (v) => bookingsRef.value = v || [])
watch(() => props.filter, (v) => filter.value = v || { start:'', end:'' }, { immediate: true })

const availableFloors = computed(() => {
  const uniqueFloors = [...new Set(roomsRef.value.map(r => r.floor))]
  return uniqueFloors.sort((a,b)=>a-b)
})

const roomsOnFloor = computed(() =>
  roomsRef.value.filter(r => r.floor === selectedFloor.value)
)

function isBooked(roomId) {
  const now = new Date()
  return bookingsRef.value.some(b => {
    if (Number(b.room_id) !== Number(roomId)) return false
    const s = new Date(String(b.start_time).replace(' ', 'T'))
    const e = new Date(String(b.end_time).replace(' ', 'T'))
    return s < now && e > now
  })
}

function parseFilterDates() {
  if (!filter.value.start || !filter.value.end) return null
  const s = new Date(filter.value.start)
  const e = new Date(filter.value.end)
  if (isNaN(s) || isNaN(e) || e <= s) return null
  return { s, e }
}

function isAvailableDuringFilter(roomId) {
  const f = parseFilterDates()
  if (!f) return false
  const conflict = bookingsRef.value.some(b => {
    if (Number(b.room_id) !== Number(roomId)) return false
    const bs = new Date(String(b.start_time).replace(' ', 'T'))
    const be = new Date(String(b.end_time).replace(' ', 'T'))
    return bs < f.e && be > f.s
  })
  return !conflict
}

function getRoom(id) {
  return roomsRef.value.find(r => r.id === id) || {}
}

function changeFloor(f) {
  selectedFloor.value = f
}

function onRoomClick(id) {
  if (activePopoverId.value === id) {
    activePopoverId.value = null
  } else {
    activePopoverId.value = id
    emit('selectedRoom', id)
  }
}

onMounted(() => {
  const floors = availableFloors.value
  if (floors && floors.length) selectedFloor.value = floors[0]
})
</script>

<style scoped>
.room-layout { gap: 10px; display:flex; flex-wrap:wrap; }
.room-wrapper {
  position: relative;
  margin: 6px;
  width: 80px; 
  height: 80px;
  display: inline-block;
}


.inline-popover {
  position: absolute;
  top: calc(100% + 0px); 
  left: 0;
  width: 220px;
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none; 
  z-index: 5;
}


.inline-popover > * {
  pointer-events: none;
}
</style>
