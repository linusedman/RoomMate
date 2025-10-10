<template>
  <path
    :d="path"
    :fill="currentFill"
    @click="(e) => handleClick(e)"
    @mouseover="hovering = true"
    @mouseleave="hovering = false"
  />
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  room: Object,
  path: String,
  booked: Boolean,
  selected: Boolean,
  matchesFilter: String
})

const emit = defineEmits(['select'])
const hovering = ref(false)



function handleClick(event) {
  if (!props.booked && props.matchesFilter !== 'unmatched') {
    emit('select', props.room.id, event)
  }
}


function getFill(){
  if (props.matchesFilter === 'unmatched') return "#999999" 
  if (props.selected) return "#0087e6"
  if (props.booked) return "#6c757d"
  if (props.matchesFilter === 'matched') return "#03d4a8" 
  return "#0029aa"
}

function getHoverFill(){
  if (props.matchesFilter === 'unmatched') return "#999999"
  if (props.matchesFilter === 'matched') return "#02c4a0"
  if (props.booked) return "#5a6268"             
  return "#0044cc"       
}

const currentFill = computed(() => {
  return hovering.value ? getHoverFill() : getFill()
})

</script>

<style scoped>
.room-box {
  width: 80px;
  height: 80px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 20; 
}
/* compleately occupied */
.booked {
  background-color: #6c757d;
  color: #fff;
  cursor: not-allowed;
}


</style>