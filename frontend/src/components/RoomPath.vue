<template>
  <path
      :d = "path"
      :fill =getFill()
    :class="{
      booked: booked,
      available: !booked && !availableByFilter,
      filteredAvailable: !booked && availableByFilter,
      selected: selected
    }"
    @click="(e) => handleClick(e)"
  >

  </path>
</template>

<script setup>
const props = defineProps({
  room: Object,
  path: String,
  booked: Boolean,
  selected: Boolean,
  availableByFilter: Boolean
})

const emit = defineEmits(['select'])

function handleClick(event) {
  if (!props.booked) emit('select', props.room.id, event)
}

function getFill(){
  if (props.booked) {
    return "#6c757d"
  }
  if (props.availableByFilter) {
    return "#6DBE45"
  }
  return "#1F3A93"
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
  background-color: #1F3A93;
  color: #fff;
}

.filteredAvailable {
  background-color: #6DBE45;
  color: #fff;
  box-shadow: 0 0 0 3px rgba(109,190,69,0.15);
}

.selected {
  border: 3px solid #6DBE45;
}
</style>