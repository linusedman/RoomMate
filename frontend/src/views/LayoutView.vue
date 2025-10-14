<template>
  <div class="floor-view container"
  style="display: flex; flex-direction: column;">
    <FloorSelector :floors="availableFloors" @change="changeFloor" />

    <div class="room-layout d-flex flex-wrap"
    style="width:100%;">
      <div class="svg-box">
        <svg
            class="floor-svg"
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
          :path="room.path"
          :booked="isBooked(room.id)"
          :matchesFilter="matchesFilter(room)"
          :selected="activePopoverId === room.id"
          :isFavorite="favorites.some(f => f.room_id === room.id)"
          :selectedFavorite="props.selectedFavoriteId === room.id"
          @select="(id, e) => onRoomClick(id, e)"
          />
        </g>
      </svg>
      </div>

      <div
          v-show="activePopoverId !== null"
          class="inline-popover"
          :style="{ left: popoverPos.x + 'px', top: popoverPos.y + 'px' }"
          @mousedown="startDrag"
          @click.stop
        >
          <RoomPopover 
            v-if="activePopoverId !== null"
            :room="getRoom(activePopoverId)" 
            :favorites="props.favorites"
            :instrumentName="getInstrumentName(activePopoverId)"
            @favoritesChanged="$emit('favoritesChanged')"
          />
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
  allRooms: Array,
  floors: Array,
  bookings: Array,
  favorites: Array,
  instruments: Array,
  instrumentTypes: Array,
  selectedFavoriteId: [Number, null]
})

const emit = defineEmits(['selectedRoom', 'favoritesChanged'])


const roomsRef = ref([])
const bookingsRef = ref(props.bookings || [])
const floorsRef = ref(props.floors || [])
const selectedFloor = ref(1)
const activePopoverId = ref(null)
const filter = ref(props.filter || { start:'', end:'' })
const svg = ref(null)
const group = ref(null)
const popoverPos = ref({ x: 0, y: 0 })
const isDragging = ref(false)
const dragOffset = ref({ x: 0, y: 0 })


watch(() => props.allRooms, (v) => {
  roomsRef.value = v || []
})
watch(() => props.bookings, (v) => bookingsRef.value = v || [])
watch(() => props.floors, (v) => floorsRef.value = v || [])
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

function isRoomFiltered(roomId) {
  return props.rooms.some(r => r.id === roomId)
}

function matchesFilter(room) {
  const hasFilter = filter.value.instrumentId || (filter.value.start && filter.value.end)

  if (!hasFilter) return 'default'

  return isRoomFiltered(room.id) ? 'matched' : 'unmatched'
}

function getRoom(id) {
  return roomsRef.value.find(r => r.id === id) || {}
}

function changeFloor(f) {
  selectedFloor.value = f
  activePopoverId.value = null 
  CenterAndScaleGroup()
}

function onRoomClick(id, event) {
  if (activePopoverId.value === id) {
    activePopoverId.value = null
  } else {
    const svgRect = svg.value.getBoundingClientRect()
    const roomBox = event.target.getBoundingClientRect()

    
    let x = roomBox.left
    let y = roomBox.bottom + window.scrollY + 4 

    const popoverWidth = 220
    if (x + popoverWidth > window.innerWidth - 10) {
      x = window.innerWidth - popoverWidth - 10
    }

    popoverPos.value = { x, y }

    activePopoverId.value = id
    emit('selectedRoom', id)
  }
}


function CenterAndScaleGroup() {
  if (!svg.value || !group.value) return

  nextTick(() => {
    const bbox = group.value.getBBox()
    if (!bbox.width || !bbox.height) {
      return;
    }
    const svgWidth = svg.value.viewBox.baseVal.width || svg.value.clientWidth
    const svgHeight = svg.value.viewBox.baseVal.height || svg.value.clientHeight

    const scale = Math.min(svgWidth / bbox.width, svgHeight / bbox.height);
    const dx = (svgWidth - bbox.width * scale) / 2 - bbox.x * scale
    const dy = - bbox.y * scale

    group.value.setAttribute('transform', `translate(${dx}, ${dy}) scale(${scale})`)
  })
}


function startDrag(e) {
  isDragging.value = true
  // store offset inside popover where clicked
  dragOffset.value = {
    x: e.clientX - popoverPos.value.x,
    y: e.clientY - popoverPos.value.y
  }
  window.addEventListener('mousemove', onDrag)
  window.addEventListener('mouseup', stopDrag)
}

function onDrag(e) {
  if (!isDragging.value) return
  popoverPos.value = {
    x: e.clientX - dragOffset.value.x,
    y: e.clientY - dragOffset.value.y
  }
}

function stopDrag() {
  isDragging.value = false
  window.removeEventListener('mousemove', onDrag)
  window.removeEventListener('mouseup', stopDrag)
}

onMounted(() => {
  const floors = availableFloors.value
  if (floors && floors.length) selectedFloor.value = floors[0]
  window.addEventListener('resize', CenterAndScaleGroup)
  window.addEventListener('scroll', () => {
  if (activePopoverId.value !== null) {
    const el = document.querySelector('path.selected')
    if (el) {
      const rect = el.getBoundingClientRect()
      let x = rect.left
      let y = rect.bottom + window.scrollY + 4
      const popoverWidth = 220
      if (x + popoverWidth > window.innerWidth - 10) {
        x = window.innerWidth - popoverWidth - 10
      }
      popoverPos.value = { x, y }
    }
  }
})
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', CenterAndScaleGroup)
})

function getFPath(id){
    const current_floor = floorsRef.value.find(r => r.id === id) || {}
    return current_floor.path
}

function getInstrumentName(roomId) {
  if (!roomId) return ''
  const instrument = props.instruments.find(i => Number(i.room_id) === Number(roomId))
  const type = props.instrumentTypes.find(t => Number(t.id) === Number(instrument?.type_id))
  const name = type?.typename || ''
  return name
}

watch(
  () => availableFloors.value,
  (floors) => {
    if (floors && floors.length && !selectedFloor.value) {
      selectedFloor.value = floors[0]
      nextTick(() => {
        CenterAndScaleGroup()
      })
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.room-layout { gap: 10px; display:flex; flex-wrap:wrap; }

.inline-popover {
  position: absolute; 
  width: 220px;
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  overflow: visible;
  pointer-events: auto;
  cursor: grab;
}

.inline-popover:active {
  cursor: grabbing;
}

.inline-popover > svg {
  pointer-events: auto;
}

.svg-box {
  width: 100%;
  aspect-ratio: 16 / 9;
}

.svg-box > .floor-svg {
  width: 100%;
  height: 100%;
  display: block;
}

.floor-svg {
  overflow: visible;
}
</style>
