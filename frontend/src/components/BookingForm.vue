<template>
  <form @submit.prevent="submitForm">
    <div class="mb-3">
      <label for="start_time" class="form-label">Start Time</label>
      <input type="datetime-local" v-model="start_time" id="start_time" class="form-control" required />
    </div>

    <div class="mb-3">
      <label for="end_time" class="form-label">End Time</label>
      <input type="datetime-local" v-model="end_time" id="end_time" class="form-control" required />
    </div>

    <button type="submit" class="btn btn-success" :disabled="isBooked || !isAvailable">
      Book Room
    </button>
  </form>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  roomId: { type: Number, required: true },
  isBooked: { type: Boolean, default: false },
  bookings: { type: Array, default: () => [] } // array of {room_id, start_time, end_time}
})

const emit = defineEmits(['booked', 'error'])
const start_time = ref('')
const end_time = ref('')

function toDate(s) {
  return s ? new Date(s.replace(' ', 'T')) : null
}

const isAvailable = computed(() => {
  if (!start_time.value || !end_time.value) return false
  const s = toDate(start_time.value.replace('T', ' '))
  const e = toDate(end_time.value.replace('T', ' '))
  if (!s || !e || e <= s) return false

  // check existing bookings for the same room
  const conflicts = props.bookings.some(b => {
    if (b.room_id !== props.roomId) return false
    const bs = toDate(b.start_time)
    const be = toDate(b.end_time)
    return bs < e && be > s
  })
  return !conflicts
})

const submitForm = async () => {
  if (!isAvailable.value) {
    emit('error', 'Selected time is not available')
    return
  }

  const form = new URLSearchParams({
    room_id: props.roomId,
    start_time: start_time.value.replace('T', ' '),
    end_time: end_time.value.replace('T', ' ')
  })

  try {
    const res = await fetch("http://localhost/RoomMate/backend/pages/book.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: form.toString()
    })
    const data = await res.json()
    if (data.status === "success") {
      emit('booked')
      start_time.value = ''
      end_time.value = ''
    } else {
      emit('error', data.message || 'Booking failed')
    }
  } catch (err) {
    emit('error', 'Network error')
    console.error(err)
  }
}
</script>
