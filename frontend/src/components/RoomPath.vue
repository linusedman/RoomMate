<template>
  <g>
    <path
      ref="pathRef"
      :d="path"
      :fill="currentFill"
      stroke="black"
      stroke-width="0.5px"
      @click="(e) => handleClick(e)"
      @mouseover="hovering = true"
      @mouseleave="hovering = false"
    />

    <svg
      v-if="isFavorite"
      :x="starX"
      :y="starY"
      width="16"
      height="16"
      viewBox="0 0 24 24"
      class="svg-star"
    >
      <path
        d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
        fill="gold"
      />
    </svg>
  </g>
</template>


<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'

const props = defineProps({
  room: Object,
  path: String,
  selected: Boolean,
  matchesFilter: String,
  isFavorite: Boolean,
  selectedFavorite: Boolean
})

const emit = defineEmits(['select'])
const hovering = ref(false)
const starX = ref(0)
const starY = ref(0)
const pathRef = ref(null)


function handleClick(event) {
  if (props.matchesFilter !== 'unmatched') {
    emit('select', props.room.id, event)
  }
}


function getFill(){
  if (props.selectedFavorite) {
    console.log("RoomPath → applying favorite highlight color")
    return "#21d6e0"
  }
  if (props.matchesFilter === 'unmatched') return "#999999" 
  if (props.selected) return "#0087e6"
  if (props.matchesFilter === 'matched') return "#03d4a8" 
  if (props.selectedFavorite) return "#21d6e0"
  return "#0029aa"
}

function getHoverFill(){
  if (props.matchesFilter === 'unmatched') return "#999999"
  if (props.matchesFilter === 'matched') return "#02c4a0"
  return "#0044cc"       
}

const currentFill = computed(() => {
  return hovering.value ? getHoverFill() : getFill()
})

onMounted(() => {
  nextTick(() => {
    if (pathRef.value) {
      const bbox = pathRef.value.getBBox()
      starX.value = bbox.x + bbox.width - 18
      starY.value = bbox.y + 4
    }
  })
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

.star-icon {
  position: absolute;
  pointer-events: none;
}


</style>