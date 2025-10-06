<template>
  <div class="room-row">
    <div class="room-label">{{ room.roomname }}</div>

    <div class="timeline-wrapper">
      
      <div
        class="timeline"
        ref="timeline"
        @scroll="onScroll"
        @mousedown="onMouseDown"
        @mousemove="onMouseMove"
        @mouseup="onMouseUp"
        @mouseleave="onMouseLeave"
        @touchstart.prevent="onTouchStart"
        @touchmove.prevent="onTouchMove"
        @touchend.prevent="onTouchEnd"
      >
        <div class="timeline-content" ref="content" :style="contentStyle">
          <div class="ticks" :style="ticksGridStyle" aria-hidden="true">
            <div v-for="(t, idx) in ticks" :key="idx" class="tick">
              <div class="tick-label">{{ t }}</div>
            </div>
          </div>

          <div
            v-for="b in bookings"
            :key="b.id || (b.start_time + b.end_time)"
            class="booked-block"
            :style="styleForBooking(b.start_time, b.end_time)"
          ></div>

          <div v-if="selectionVisible" class="selection" :style="selectionStyle"></div>
        </div>
      </div>

      <div v-if="dragging" class="drag-label" :style="dragLabelStyle">
        <div class="time-text">{{ dragLabelStart }} → {{ dragLabelEnd }}</div>
        <button class="btn btn-sm btn-success ms-2" @click="confirmSelection">Book</button>
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
import { ref, watch, computed } from 'vue'
const messageText = ref('')
const messageType = ref('')
const props = defineProps({
  room: { type: Object, required: true },
  dayStart: { type: Date, required: true },
  dayEnd: { type: Date, required: true },
  bookings: { type: Array, default: () => [] },
  ticksGridStyle: { type: Object, default: () => ({}) },
  hourWidth: { type: Number, default: 60 }
})
const emit = defineEmits(['confirmBooking', 'scrollX'])

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

function onScroll(e) {
  emit('scrollX', e.target.scrollLeft)
}
watch(
  () => props.scrollX,
  (newX) => {
    if (timeline.value && timeline.value.scrollLeft !== newX) {
      timeline.value.scrollLeft = newX
    }
  }
)
function clamp(v,a=0,b=1){ return Math.max(a, Math.min(b,v)) }
function fractionToMs(frac){ return props.dayStart.getTime() + frac * (props.dayEnd.getTime() - props.dayStart.getTime()) }
function msToFraction(ms){ return clamp((ms - props.dayStart.getTime()) / (props.dayEnd.getTime() - props.dayStart.getTime())) }
function snapToMinutes(ms, minutes=5){ const step = minutes*60*1000; return Math.round(ms/step)*step }
function isoLocalFromMs(ms){ const d=new Date(ms); return d.toISOString().slice(0,16) }
function displayTimeFromMs(ms){ const d=new Date(ms); return `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}` }

const ticks = computed(() => {
  const arr = []
  const s = new Date(props.dayStart)
  const e = new Date(props.dayEnd)
  const cur = new Date(s)
  cur.setMinutes(0,0,0)
  while (cur <= e) {
    arr.push(cur.toTimeString().slice(0,5))
    cur.setHours(cur.getHours()+1)
  }
  return arr
})

const ticksGridStyleLocal = computed(() => {
  return Object.assign({}, props.ticksGridStyle || {})
})

const contentStyle = computed(() => {
  return { width: (ticks.value.length * props.hourWidth) + 'px' }
})

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
  pendingSelection.value = {
    startISO: isoLocalFromMs(sMs),
    endISO: isoLocalFromMs(eMs),
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
  messageText.value = `Room ${props.room.roomname} successfully booked from ${sel.displayStart} to ${sel.displayEnd}.`
  messageType.value = 'success'
  clearSelection()
  setTimeout(() => {
    messageText.value = ''
    messageType.value = ''
  }, 5000)
}

function onMouseDown(evt){ if(evt.button===0) return startDragAtClientX(evt.clientX) }
function onMouseMove(evt){ moveDragAtClientX(evt.clientX) }
function onMouseUp(){ endDrag() }
function onMouseLeave(){ if(dragging.value){ dragging.value=false; clearSelection() } }
function onTouchStart(e){ if(e.touches && e.touches[0]) startDragAtClientX(e.touches[0].clientX) }
function onTouchMove(e){ if(e.touches && e.touches[0]) moveDragAtClientX(e.touches[0].clientX) }
function onTouchEnd(){ endDrag() }
</script>

<style scoped>
.room-row { display:flex; align-items:flex-start; gap:12px; margin-bottom:8px; }
.room-label { width:120px; font-weight:600; margin-top:6px; }
.timeline-wrapper { flex:1; position:relative; }
.timeline { position:relative; height:48px; border:1px solid #e0e0e0; background:#f6fff6; overflow:auto; white-space:nowrap; user-select:none; }
.timeline-content { position:relative; height:100%; }
.ticks { position:absolute; inset:0; display:grid; z-index:0; pointer-events:none; }
.tick { border-left:1px dashed rgba(0,0,0,0.03); height:100%; box-sizing:border-box; position:relative; }
.tick-label { font-size:11px; color:rgba(0,0,0,0.45); position:absolute; top:-18px; left:6px; }
.booked-block { position:absolute; top:4px; bottom:4px; background:#6c757d; border-radius:4px; z-index:3; }
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
</style>
