<template>
  <div class="floor-view container">
    <FloorSelector :floors="availableFloors" @change="changeFloor" />

    <div class="room-layout d-flex flex-wrap">
      <svg
          width = 100%
          id="Floor"
          ref="svg"
      >
        <g
        ref="group">
          <FloorPath
          :floor=selectedFloor
          :path="getFPath(selectedFloor)"
          />

          <RoomPath
          v-for="room in roomsOnFloor"
          :key="room.id"
          :room="room"
          :path="getPath(room.id)"
          :booked="isBooked(room.id)"
          :availableByFilter="isAvailableDuringFilter(room.id)"
          :selected="activePopoverId === room.id"
          @select="onRoomClick"
          />
        </g>
      </svg>

      <div
          v-show="activePopoverId !== null"
          class="inline-popover"
          @click.stop
        >
          <RoomPopover :room="getRoom(activePopoverId)" />
      </div>
    </div>
  </div>
</template>

<script setup>
import {ref, computed, watch, onMounted, nextTick, onBeforeUnmount} from 'vue'
import FloorSelector from '../components/FloorSelector.vue'
import RoomPopover from '../components/RoomPopover.vue'
import FloorPath from "../components/FloorPath.vue";
import RoomPath from "../components/RoomPath.vue";

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
const svg = ref(null)
const group = ref(null)

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

function centerGroup() {
  if (!svg.value || !group.value) return

  // Wait for paths to render
  nextTick(() => {
    const bbox = group.value.getBBox()
    const svgWidth = svg.value.viewBox.baseVal.width || svg.value.clientWidth
    const svgHeight = svg.value.viewBox.baseVal.height || svg.value.clientHeight

    const scale = Math.min(svgWidth / bbox.width, svgHeight / bbox.height);
    const dx = (svgWidth - bbox.width * scale) / 2 - bbox.x * scale
    const dy = (svgHeight - bbox.height * scale) / 2 - bbox.y * scale

    group.value.setAttribute('transform', `translate(${dx}, ${dy}) scale(${scale})`)
  })
}

onMounted(() => {
  const floors = availableFloors.value
  if (floors && floors.length) selectedFloor.value = floors[0]
  centerGroup()
  window.addEventListener('resize', centerGroup)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', centerAndScaleGroup)
})

//Currently hardcoded, have idea on how to make it dynamic, but requires database changes
function getPath(id){
    if (id === 1) {
      return "m -127.29961,89.868727 9.90405,49.970393 52.671492,-9.90404 -10.354225,-50.870762 z"
    }
    if (id === 2) {
      return "M -57.102713,75.526386 -46.957695,126.36537 5.9547779,115.5292 -4.3994473,64.835878 Z"
    }
    if (id === 3) {
      return "M 48.658901,54.063157 -4.6404419,64.721831 6.1640666,115.32004 58.835271,105.14317 Z"
    }
    if (id === 4) {
      return "M 65.760276,50.237843 76.429863,101.91212 128.94097,91.033325 118.68979,39.359047 Z"
    }
    if (id === 5) {
      return "M 171.41011,28.689459 182.0797,80.363737 128.94097,91.033325 118.48058,39.149838 Z"
    }
}

function getFPath(id){
    if (id === 1) {
      return "m 239.4026,82.455814 -15.48138,-65.481981 -352.3056,71.758209 12.97087,65.272778 z"
    }
}
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
