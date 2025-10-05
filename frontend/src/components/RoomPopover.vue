<template>
  <div class="popover" v-if="room">
    <div class="room-shape" :style="shapeStyle">
      Room {{ room.roomname }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  room: { type: Object, required: true },
  width: { type: Number, default: 220 },
  height: { type: Number, default: 160 }
})

// Convert SVG path to CSS clip-path polygon
function pathToPolygon(path) {
  try {
    const svgPath = document.createElementNS("http://www.w3.org/2000/svg", "path")
    svgPath.setAttribute("d", path)
    const length = svgPath.getTotalLength()
    const points = []

    for (let i = 0; i <= 50; i++) {
      const pt = svgPath.getPointAtLength((i / 50) * length)
      points.push([pt.x, pt.y])
    }

    const xs = points.map(p => p[0])
    const ys = points.map(p => p[1])
    const minX = Math.min(...xs)
    const maxX = Math.max(...xs)
    const minY = Math.min(...ys)
    const maxY = Math.max(...ys)

    const polygonPoints = points.map(p => {
      const x = ((p[0] - minX) / (maxX - minX)) * 100
      const y = ((p[1] - minY) / (maxY - minY)) * 100
      return `${x}% ${y}%`
    })

    return `polygon(${polygonPoints.join(",")})`
  } catch (err) {
    console.error("Failed to convert path to polygon:", err)
    return "none"
  }
}


const shapeStyle = computed(() => {
  if (!props.room || !props.room.path) return {}
  return {
    width: props.width + "px",
    height: props.height + "px",
    clipPath: pathToPolygon(props.room.path),
    backgroundColor: "#0087e6",
    color: "white",
    fontWeight: "600",
    display: "flex",
    alignItems: "center",
    justifyContent: "center"
  }
})
</script>

<style scoped>
.popover {
  position: relative;
  padding: 0;
  background: transparent;
  border: none;
  box-shadow: none;
  pointer-events: auto;
  overflow: visible; /* allow shape to extend freely */
}

.room-shape {
  overflow: visible;
}
</style>
