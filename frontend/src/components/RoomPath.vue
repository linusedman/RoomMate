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
  availableByFilter: Boolean
})

const emit = defineEmits(['select'])
const hovering = ref(false)



function handleClick(event) {
  if (!props.booked) emit('select', props.room.id, event)
}


function getFill(){
  if (props.selected) return "#0087e6"
  if (props.booked) return "#6c757d"
  if (props.availableByFilter) return "#03d4a8"
  return "#1F3A93"
}

function getHoverFill(){
  if (props.booked) return "#5a6268"             
  if (props.availableByFilter) return "#02c4a0"   
  return "#34495E"        
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