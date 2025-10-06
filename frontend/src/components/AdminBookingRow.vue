<template>
  <tr>
    <td> {{ user }}</td>
    <td> {{ room }}</td>
    <td> {{ displayTimeFromMs(start) }}</td>
    <td> {{ displayTimeFromMs(end) }}</td>
    <td>
      <button @click="deleteBooking">
        Delete
      </button>
    </td>
  </tr>
</template>




<script setup>
import { ref } from 'vue'
const props = defineProps({
  id: Number,
  user: String,
  room: String,
  start: String,
  end: String,
})

const emit = defineEmits(["bookingDeleted"])

async function deleteBooking() {
  const confirmed = confirm(`Are you sure you want to delete ${props.user}'s booking in room ${props.room}?`)
  if (!confirmed) return
  try {
    const response = await fetch(`http://localhost/RoomMate/backend/admin/bookings.php?action=delete`, {
      method: "POST",
      credentials: "include",
      body: new URLSearchParams({ id: props.id }),
    })

    const result = await response.json()

    if (result.status === "success") {
      alert("Booking deleted successfully!")
      emit("bookingDeleted", props.id) // tell parent to refresh list
    } else {
      alert(result.message || "Failed to delete booking")
    }
  } catch (error) {
    console.error("Error deleting booking:", error)
    alert("Server error while deleting booking")
  }



}


function displayTimeFromMs(ms){ const d=new Date(ms);
  return d.toLocaleString('swe', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }); }
</script>

<style scoped>
td {
      border: 1px solid #ccc;
      padding: 12px 16px;
    }
tr:nth-child(even) {
      background-color: #fafafa;
    }
button {
  background-color: #2ecc71;
}
</style>