<template>
  <div class="popover">
    <div class="room-visual"></div>
  </div>
</template>

<script setup>
const props = defineProps({
  room: { type: Object, required: false }
})
</script>

<style scoped>
.popover {
  padding: 0;
  background: transparent;
  border-radius: 6px;
  width: 220px;
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: none;
  pointer-events: auto; /* allow interactions inside popover if needed later */
}

/* A box representing the room */
.room-visual {
  width: 200px;
  height: 140px;
  background: #f6fff6;
  border: 2px solid #2ecc71;
  border-radius: 6px;
  box-sizing: border-box;
  z-index: 5;
}
</style>
