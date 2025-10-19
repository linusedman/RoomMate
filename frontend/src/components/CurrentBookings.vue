<template>
  <div class="current-bookings" :class="{ 'has-scroll': bookings.length > 2 }">
    <h4 class="title">My Current Bookings</h4>

    <div v-if="loading" class="loading">Loading...</div>
    <div v-else-if="bookings.length === 0" class="no-bookings">
      No current bookings.
    </div>

    <div v-else class="booking-list">
      <div
        v-for="(b, idx) in bookings"
        :key="idx"
        class="booking-card"
      >
        <div class="booking-content">
          <div class="room">{{ b.roomname }}</div>
          <div class="time-info">
            <div><strong>Date:</strong> {{ formatDate(b.start_time) }}</div>
            <div><strong>Start:</strong> {{ formatTime(b.start_time) }}</div>
            <div><strong>End:</strong> {{ formatTime(b.end_time) }}</div>
          </div>
          <div v-if="deletedBookingId === b.booking_id && message" style="color: #dc3545; font-size: 14px; margin-top: 8px;">
            {{ message }}
          </div>
        </div>

        <button 
        class="delete-btn" 
        @click="deleteBooking(b.booking_id)"
        >
        Delete
        </button>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

defineExpose({
  loadBookings
})

const bookings = ref([])
const loading = ref(true)
const message = ref("")
const deletedBookingId = ref(null)

const emit = defineEmits(["bookingDeleted"])


async function loadBookings() {
  try {
    const res = await fetch("http://localhost/RoomMate/backend/pages/get_user_bookings.php", {
      method: "GET",
      credentials: "include"
    })
    const data = await res.json()
    if (data.status === "success") {
      const now = new Date()
      bookings.value = data.bookings.filter(b => new Date(b.end_time) > now)
    } else {
      bookings.value = []
    }
  } catch (err) {
    console.error(err)
    bookings.value = []
  } finally {
    loading.value = false
  }
}

async function deleteBooking(bookingId) {
  const booking = bookings.value.find(b => b.booking_id === bookingId)
  const roomname = booking?.roomname || "unknown room"
  try {
    const res = await fetch("http://localhost/RoomMate/backend/pages/delete_bookings.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ booking_id: bookingId })
    })
    const data = await res.json()
    if (data.status === "success") {
      message.value = `Booking of ${roomname} was successfully deleted.`
      deletedBookingId.value = bookingId
      
      setTimeout(() => {
        message.value = ""
        deletedBookingId.value = null
        loadBookings()
        emit("bookingDeleted")
      }, 5000)
    } else {
      console.error("Failed to delete booking:", data.message)
    }
  } catch (err) {
    console.error("Error deleting booking:", err)
  }
}

function formatDate(datetime) {
  const d = new Date(datetime)
  return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "numeric" })
}

function formatTime(datetime) {
  const d = new Date(datetime)
  return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
}

onMounted(loadBookings)
</script>

<style scoped>
.current-bookings {
  background: #f9fafb;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 16px;
  margin-top: 16px;
}

.current-bookings.has-scroll {
  max-height: 400px;
  overflow-y: auto;}

.title {
  font-weight: 600;
  margin-bottom: 10px;
}

.loading, .no-bookings {
  text-align: center;
  color: #666;
  font-style: italic;
}

.booking-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.booking-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 10px 12px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.booking-content {
  margin: 0 auto;
  text-align: center;
}

.room {
  font-weight: 600;
  color: #03d4a8;
  margin-bottom: 4px;
}

.time-info {
  font-size: 13px;
  color: #333;
}

.delete-btn {
  background-color: #ff4d4f;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
}

.delete-btn:hover {
  background-color: #d9363e;
}

</style>
