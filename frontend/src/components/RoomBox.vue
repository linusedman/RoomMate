<template>
  <div
    class="room-box"
    :class="{
      booked: booked,
      available: !booked && !availableByFilter,
      filteredAvailable: !booked && availableByFilter,
      selected: selected
    }"
    @click="handleClick"
  >
    {{ room.roomname }}

  </div>
</template>

<script setup>
const props = defineProps({
  room: Object,
  booked: Boolean,
  selected: Boolean,
  availableByFilter: Boolean
})

const emit = defineEmits(['select'])

function handleClick() {
  if (!props.booked) emit('select', props.room.id)
}
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
  z-index: 20; /* stay above popover */
}
/* compleately occupied */
.booked {
  background-color: #6c757d;
  color: #fff;
  cursor: not-allowed;
}

.available {
  background-color: #486950;
  color: #fff;
}
/* when the room fulfills the filtering requirements */
.filteredAvailable {
  background-color: #2ecc71;
  color: #fff;
  box-shadow: 0 0 0 3px rgba(46,204,113,0.15);
}
.selected {
  border: 3px solid #0d6efd;
}
</style>
