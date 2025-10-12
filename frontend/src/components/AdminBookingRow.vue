<template>
  <tr>
    <td> {{ user }}</td>

    <td v-if="!isEditing">{{ get_roomname(room_id) }}</td>
    <td v-else><select v-model="editedRoom" >
      <option v-for="room in rooms" :key="room.id" :value="room.id">
        {{ room.roomname }} </option>
    </select></td>

    <td v-if="!isEditing"> {{ displayTimeFromMs(start) }}</td>
    <td v-else>
      <input
        type="datetime-local"
        v-model="editedStart"
        class="time-input"
      />
    </td>

    <td v-if="!isEditing"> {{ displayTimeFromMs(end) }}</td>
    <td v-else>
      <input
        type="datetime-local"
        v-model="editedEnd"
        class="time-input"
      />
    </td>

    <td>
      <button v-if="!isEditing" @click="startEditing">Edit</button>
      <button v-if="isEditing" @click="saveEdit">Save</button>
      <button v-if="isEditing" @click="cancelEdit">Cancel</button>
    </td>
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
  room_id: String,
  start: String,
  end: String,
  rooms: Array,
})

const emit = defineEmits(["bookingDeleted", "bookingUpdated"])
const isEditing = ref(false)
const editedRoom = ref(Number(props.room_id));
const editedStart = ref(props.start);
const editedEnd = ref(props.end);

function get_roomname(id){
  const current_room = props.rooms.find(r => Number(r.id) === Number(id)) || {}
  return current_room.roomname
}

function startEditing() {
  isEditing.value = true;
}

function cancelEdit() {
  isEditing.value = false;
  editedRoom.value = Number(props.room_id);
  editedStart.value = props.start;
  editedEnd.value = props.end;
}

async function saveEdit() {
  const formData = new FormData();
  formData.append('id', props.id)
  formData.append('room_id', editedRoom.value);
  formData.append('start_time', editedStart.value);
  formData.append('end_time', editedEnd.value);
  try {
    const res = await fetch(`http://localhost/RoomMate/backend/admin/bookings.php?action=update`, {
      method: "POST",
      credentials: "include",
      body: formData
    });
    const result = await res.json()

    if (result.status === "success") {
      isEditing.value = false;
      alert("Booking updated successfully!")
      emit('bookingUpdated');
    } else {
      alert(result.message || "Failed to update booking")
    }
  } catch (error) {
    console.error("Error updating booking:", error)
    alert("Server error while updating booking")
  }
}


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