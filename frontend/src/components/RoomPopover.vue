<template>
  <div class="popover">
    Room {{room.roomname}}
    <div class="room-visual"
    style="align-content: center;
          text-align: center">

    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  room: { type: Object, required: false }
})
</script>

<style scoped>
.popover {
  padding: 10px;
  background: transparent;
  border-radius: 6px;
  width: auto;
  height: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: none;
  pointer-events: auto; /* allow interactions inside popover if needed later */
}


.room-visual {
  width: 200px;
  height: 120px;
  background: #34495E;
  border: 2px solid #1F3A93;
  border-radius: 6px;
  box-sizing: border-box;
  z-index: 5;
}

</style>
