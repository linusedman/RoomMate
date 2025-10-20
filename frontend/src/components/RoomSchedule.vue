<template>
  <div class="room-row">
    <div class="room-label">
      {{ room.roomname }}
      <span v-if="isFavorite(room.id)" class="favorite-tag">★</span>
    </div>
    <div class="timeline-wrapper" :class="{ highlighted: highlighted }">

      <div
        class="timeline"
        ref="timeline"
        @mousedown="onMouseDown"
        @mousemove="onMouseMove"
        @mouseup="onMouseUp"
        @mouseleave="onMouseLeave"
        @touchstart.prevent="onTouchStart"
        @touchmove.prevent="onTouchMove"
        @touchend.prevent="onTouchEnd"
      >
        <div class="timeline-content" ref="content">
          <div v-for="h in ticksMSHours"
               :style="styleForTicks(h)"
               :key="room.id + h"
               class="tick"

          ></div>
          <div
            v-for="b in bookings"
            :key="b.id || (b.start_time + b.end_time)"
            class="booked-block"
            :style="styleForBooking(b.start_time, b.end_time)"
          ></div>
          <div
            class="past-block"
            :style="styleForPast()"
          ></div>

          <div v-if="selectionVisible" class="selection" :style="selectionStyle"></div>
        </div>
      </div>

      <div v-if="dragging" class="drag-label mt-2">
        <strong>Selected:</strong>
        <span class="ms-2">{{ dragLabelStart }} → {{ dragLabelEnd }}</span>
        <button class="btn btn-sm btn-success ms-3" @click="confirmSelection">Book</button>
        <button class="btn btn-sm btn-secondary ms-2" @click="clearSelection">Cancel</button>
      </div>

      <div v-if="pendingSelection && !dragging" class="pending-row mt-2">
        <strong>Selected:</strong>
        <span class="ms-2">{{ pendingSelection.displayStart }} → {{ pendingSelection.displayEnd }}</span>
        <button class="btn btn-sm btn-success ms-3" @click="confirmSelection">Book</button>
        <button class="btn btn-sm btn-secondary ms-2" @click="clearSelection">Cancel</button>
      </div>
      <p v-if="messageText" :class="['mt-2 fw-bold', messageType === 'error' ? 'custom-error' : 'custom-success']">
        {{ messageText }}
      </p>

    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'

const messageText = ref('')
const messageType = ref('')
const props = defineProps({
  room: { type: Object, required: true },
  dayStart: { type: Date, required: true },
  dayEnd: { type: Date, required: true },
  bookings: { type: Array, default: () => [] },
  bookingStatus: { type: Object, default: null }, 
  highlighted: { type: Boolean, default: false },
  favorites: { type: Array, default: () => [] },
})
const emit = defineEmits(['confirmBooking'])

const timeline = ref(null)
const content = ref(null)
const dragging = ref(false)
const dragStartX = ref(0)
const dragCurrentX = ref(0)
const selectionStyle = ref({ left: '0px', width: '0px' })
const selectionVisible = ref(false)
const pendingSelection = ref(null)
const dragLabelStyle = ref({ left: '0px', transform: 'translateX(-50%)' })
const dragLabelStart = ref('')
const dragLabelEnd = ref('')

function isFavorite(id) {
  return props.favorites.some(f => f.room_id === id)
}

function clamp(v,a=0,b=1){ return Math.max(a, Math.min(b,v)) }
function fractionToMs(frac){ return props.dayStart.getTime() + frac * (props.dayEnd.getTime() - props.dayStart.getTime()) }
function msToFraction(ms){ return clamp((ms - props.dayStart.getTime()) / (props.dayEnd.getTime() - props.dayStart.getTime())) }
function snapToMinutes(ms, minutes=5){ const step = minutes*60*1000; return Math.round(ms/step)*step }
function isoUTCFromMs(ms){ const d=new Date(ms); return d.toISOString().slice(0, 16) + 'Z' }
function displayTimeFromMs(ms){ const d=new Date(ms); return `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}` }

const ticksMSHours = computed(() => {
  const start = new Date(props.dayStart);
  const end = new Date(props.dayEnd);
  const timestamps = [];

  const firstHour = new Date(start);
  firstHour.setHours(firstHour.getHours() + 1, 0, 0, 0);

  const lastHour = new Date(end);
  lastHour.setMinutes(0, 0, 0);
  if (lastHour.getTime() >= end.getTime()) {
    lastHour.setHours(lastHour.getHours() - 1);
  }

  let cur = firstHour;
  while (cur <= lastHour) {
    timestamps.push(cur.getTime());
    cur.setHours(cur.getHours() + 1);
  }
  return timestamps;
})

function styleForTicks(hour){
  const sMs = new Date(hour).getTime()
  const leftFrac = msToFraction(sMs)
  const el = content.value
  if(!el) return { left: '0px', width: '0px' }
  return { left: (leftFrac * el.offsetWidth) + 'px', width: '1px' }
}

function clientXToFraction(clientX){
  const el = content.value
  if (!el) return 0
  const rect = el.getBoundingClientRect()
  const x = clientX - rect.left + el.scrollLeft
  return clamp(x / el.scrollWidth)
}

function updateSelectionVisual(startFrac, endFrac){
  const el = content.value
  if(!el) return
  const leftPx = Math.min(startFrac, endFrac) * el.scrollWidth
  const widthPx = Math.abs(endFrac - startFrac) * el.scrollWidth
  selectionStyle.value = { left: leftPx + 'px', width: widthPx + 'px' }
  selectionVisible.value = widthPx > 6
  const centerPx = leftPx + widthPx/2
  dragLabelStyle.value = { left: centerPx + 'px', transform: 'translateX(-50%)' }
}

function styleForBooking(start, end){
  const sMs = new Date(start).getTime()
  const eMs = new Date(end).getTime()
  const leftFrac = msToFraction(sMs)
  const rightFrac = msToFraction(eMs)
  const el = content.value
  if(!el) return { left: '0px', width: '0px' }
  return { left: (leftFrac * el.scrollWidth) + 'px', width: ((rightFrac - leftFrac) * el.scrollWidth) + 'px' }
}

function styleForPast(){
  const start = new Date(props.dayStart)
  const end = new Date(props.dayEnd)
  const now = new Date()
  if ((now > start)&&(now < end)) {
    const leftFrac = msToFraction(start)
    const rightFrac = msToFraction(now)
    const el = content.value
    if(!el) return { left: '0px', width: '0px' }
    return { left: (leftFrac * el.scrollWidth) + 'px', width: ((rightFrac - leftFrac) * el.scrollWidth) + 'px' }
  }
  if (now > start) {
    const leftFrac = msToFraction(start)
    const rightFrac = msToFraction(end)
    const el = content.value
    if(!el) return { left: '0px', width: '0px' }
    return { left: (leftFrac * el.scrollWidth) + 'px', width: ((rightFrac - leftFrac) * el.scrollWidth) + 'px' }
  }

  return { display: "none" }
}

function overlapsExistingRange(sMs,eMs){
  return props.bookings.some(b => {
    const bs = new Date(b.start_time).getTime()
    const be = new Date(b.end_time).getTime()
    return bs < eMs && be > sMs
  })
}

function startDragAtClientX(clientX){
  dragging.value = true
  pendingSelection.value = null
  const frac = clientXToFraction(clientX)
  dragStartX.value = frac
  dragCurrentX.value = frac
  updateSelectionVisual(dragStartX.value, dragCurrentX.value)
  updateDragLabels(dragStartX.value, dragCurrentX.value)
}

function moveDragAtClientX(clientX){
  if(!dragging.value) return
  dragCurrentX.value = clientXToFraction(clientX)
  updateSelectionVisual(dragStartX.value, dragCurrentX.value)
  updateDragLabels(dragStartX.value, dragCurrentX.value)
}

function endDrag(){
  if(!dragging.value) return
  dragging.value = false
  const a = Math.min(dragStartX.value, dragCurrentX.value)
  const b = Math.max(dragStartX.value, dragCurrentX.value)
  let sMs = fractionToMs(a)
  let eMs = fractionToMs(b)
  sMs = snapToMinutes(sMs,5)
  eMs = snapToMinutes(eMs,5)
  if(eMs <= sMs){ clearSelection(); return }
  if (overlapsExistingRange(sMs,eMs)) {
    messageText.value = 'Selected time overlaps an existing booking.'
    messageType.value = 'error'
    clearSelection()
    setTimeout(() => {
      messageText.value = ''
      messageType.value = ''
    }, 5000)
    return
  }
  if (sMs < new Date()) {
    messageText.value = 'Cannot book in the past.'
    messageType.value = 'error'
    clearSelection()
    setTimeout(() => {
      messageText.value = ''
      messageType.value = ''
    }, 5000)
    return
  }
  pendingSelection.value = {
    startISO: isoUTCFromMs(sMs),
    endISO: isoUTCFromMs(eMs),
    displayStart: displayTimeFromMs(sMs),
    displayEnd: displayTimeFromMs(eMs)
  }
  const el = content.value
  updateSelectionVisual(msToFraction(sMs), msToFraction(eMs))

}

function clearSelection(){
  selectionVisible.value = false
  selectionStyle.value = { left:'0px', width:'0px' }
  pendingSelection.value = null
  dragLabelStart.value = ''
  dragLabelEnd.value = ''
}

function updateDragLabels(aFrac,bFrac){
  const sMs = snapToMinutes(fractionToMs(Math.min(aFrac,bFrac)),5)
  const eMs = snapToMinutes(fractionToMs(Math.max(aFrac,bFrac)),5)
  dragLabelStart.value = displayTimeFromMs(sMs)
  dragLabelEnd.value = displayTimeFromMs(eMs)
}

function confirmSelection(){
  const sel = pendingSelection.value
  if(!sel) return
  emit('confirmBooking', { roomId: props.room.id, startISO: sel.startISO, endISO: sel.endISO })
  clearSelection()
}


function onMouseDown(evt){ if(evt.button===0) return startDragAtClientX(evt.clientX) }
function onMouseMove(evt){ moveDragAtClientX(evt.clientX) }
function onMouseUp(){ endDrag() }
function onMouseLeave(){ if(dragging.value){ dragging.value=false; clearSelection() } }
function onTouchStart(e){ if(e.touches && e.touches[0]) startDragAtClientX(e.touches[0].clientX) }
function onTouchMove(e){ if(e.touches && e.touches[0]) moveDragAtClientX(e.touches[0].clientX) }
function onTouchEnd(){ endDrag() }

function handleBookingSuccess(message) {
  messageText.value = message
  messageType.value = 'success'
  setTimeout(() => {
    messageText.value = ''
    messageType.value = ''
  }, 5000)
}

function handleBookingError(errorMsg) {
  messageText.value = errorMsg
  messageType.value = 'error'
  setTimeout(() => {
    messageText.value = ''
    messageType.value = ''
  }, 5000)
}

watch(() => props.bookingStatus, (status) => {
  if (!status) return
  if (status.type === 'error' && status.roomId === props.room.id)
    handleBookingError(status.message)
  if (status.type === 'success' && status.roomId === props.room.id)
    handleBookingSuccess(status.message)
})

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('bookingError', (e) => {
      if (e.detail && e.detail.roomId === props.room.id)
        handleBookingError(e.detail.message)
    })
    window.addEventListener('bookingSuccess', (e) => {
      if (e.detail && e.detail.roomId === props.room.id)
        handleBookingSuccess(e.detail.message)
    })
  }
})

</script>

<style scoped>
.room-row { display:flex; align-items:flex-start; gap:12px; margin-bottom:8px; }

.timeline-wrapper.highlighted {
  background-color: rgba(33, 214, 224, 0.1);
  box-shadow: 0 0 0 2px rgba(33, 214, 224, 0.4);
  border-radius: 6px;
}
.room-label {font-weight:600; margin-top:6px; display: flex; flex-direction: column}
.timeline-wrapper { flex:1; position:relative; align-content: center}
.timeline { position:relative; height:48px; border:1px solid #e0e0e0; background:#f6fff6; overflow:auto; white-space:nowrap; user-select:none; }
.timeline-content { position:relative; height:100%; width:100%;}
.tick { position:absolute; border-left:1px dashed rgba(0,0,0,0.2); height:100%;}
.booked-block { position:absolute; top:4px; bottom:4px; background:#6c757d; border-radius:4px; z-index:3; }
.past-block { position:absolute; top:0; bottom:0; background:#6c757d; border-radius:4px; z-index:5; opacity: 50%}
.selection {
  position: absolute;
  top: 4px;
  bottom: 4px;
  background: rgba(3, 212, 168, 0.22);
  border: 2px solid rgba(3, 212, 168, 0.45);
  border-radius: 4px;
  z-index: 4;
}

.drag-label button.btn-success {
  background-color: #03d4a8;
  border-color: #03d4a8;
  color: #fff;
}

.pending-row button.btn-success {
  background-color: #03d4a8;
  border-color: #03d4a8;
  color: #fff;
  }

@media (max-width:720px) { .room-label { width:90px; font-size:13px } .tick-label { display:none } }
.custom-success {
  color: #03d4a8;
}
.custom-error {
  color: #dc3545; 
}
.favorite-tag {
  color: #21d6e0;
  margin-left: 6px;
  font-size: 16px;
}
</style>
