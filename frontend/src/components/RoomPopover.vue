<template>
  <svg
    :width="width"
    :height="height"
    :viewBox="viewBox"
    class="room-svg"
  >
    <g :transform="transform">
      <path
        ref="pathRef"
        :d="props.room.path"
        fill="#0087e6"
      />
    </g>


    <text
      :x="textX"
      :y="textY"
      dominant-baseline="middle"
      text-anchor="middle"
      fill="white"
      font-weight="600"
      font-size="12"
    >
      Room {{ props.room.roomname }}
    </text>


    <g v-if="props.instrumentName">

      <text
        :x="textX"
        :y="textY + 16"
        text-anchor="middle"
        font-size="10"
        font-weight="500"
        fill="white"
      >
        Contains instrument:
      </text>


      <rect
        :x="textX - instrumentNameWidth / 2"
        :y="textY + 18"
        :width="instrumentNameWidth"
        height="18"
        rx="4"
        fill="#0029aa"
      />


      <text
        :x="textX"
        :y="textY + 30"
        text-anchor="middle"
        fill="white"
        font-size="10"
        font-weight="500"
      >
        {{ props.instrumentName }}
      </text>

    </g>

    <g
      class="favorite-star"
      @click="toggleFavorite"
      :transform="`translate(${starX}, ${starY}) scale(1.2)`"
      cursor="pointer"
    >
      <path
        :d="isFavorite ? filledStar : outlinedStar"
        :fill="isFavorite ? 'gold' : 'white'"
        stroke="gold"
        stroke-width="2"
      />
      <title>{{ isFavorite ? 'Unmark room as favorite' : 'Mark room as favorite' }}</title>
    </g>
  </svg>
</template>





<script setup>
import { ref, computed, nextTick, watch } from 'vue'

const emit = defineEmits(['favoritesChanged'])

const props = defineProps({
  room: { type: Object, required: true },
  favorites: { type: Array, default: () => [] },
  width: { type: Number, default: 220 },
  height: { type: Number, default: 160 },
  instrumentName: { type: String, default: '' }
})

const isFavorite = computed(() =>
  props.favorites.some(f => f.room_id === props.room.id)
)
const filledStar = "M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
const outlinedStar = "M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.64-7.03L22 9.24z"

const instrumentNameWidth = computed(() => {
  const avgCharWidth = 6.5 
  return props.instrumentName.length * avgCharWidth + 4 
})

const pathRef = ref(null)
const bbox = ref({ x: 0, y: 0, width: 100, height: 100 })
const viewBox = ref("0 0 220 160")
const transform = ref("")
const textX = ref(0)
const textY = ref(0)
const starX = ref(0)
const starY = ref(0)


watch(() => props.room.path, (newPath) => {
  if (!newPath) return
  nextTick(() => {
    if (pathRef.value) {
      const b = pathRef.value.getBBox()
      bbox.value = b

      const scaleX = props.width / b.width
      const scaleY = props.height / b.height
      const scale = Math.min(scaleX, scaleY)

      const scaledWidth = b.width * scale
      const scaledHeight = b.height * scale

      const offsetX = (props.width - scaledWidth) / 2 - b.x * scale
      const offsetY = (props.height - scaledHeight) / 2 - b.y * scale

      transform.value = `translate(${offsetX}, ${offsetY}) scale(${scale})`

      textX.value = offsetX + (b.x + b.width / 2) * scale
      textY.value = offsetY + (b.y + b.height / 2) * scale

      starX.value = offsetX + b.x * scale 
      starY.value = offsetY + b.y * scale 
    }
  })
}, { immediate: true })



async function toggleFavorite() {
  
  const method = isFavorite.value ? 'DELETE' : 'POST'
  await fetch("http://localhost/RoomMate/backend/pages/favorites.php", {
    method,
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ room_id: props.room.id })
  })
  emit('favoritesChanged')
}


</script>

<style scoped>
.room-svg {
  overflow: visible;
  cursor: default;
}

</style>
