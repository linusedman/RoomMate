<template>
  <div
  style="
  display: flex;
  flex-direction: column;
  justify-content: center;
  width: 100%;
  gap: 1rem"
  >
    <div>
      <form @submit.prevent="addInstrument">
        <input
          v-model="newInstrumentType"
          type="text"
          placeholder="Instrument Type"
          required
        />
        <select v-model="newInstrumentRoom" required>
          <option value="" disabled>Select Room</option>
          <option v-for="room in rooms" :key="room.id" :value="room.id">
            {{ room.roomname }}
          </option>
        </select>
        <button type="submit">Add Instrument</button>
      </form>
    </div>
    <table>
      <thead>
        <tr>
          <th>Instrument</th>
          <th>Room</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <AdminInstrumentRow
        v-for="instrument in instruments"
        :id = "Number(instrument.id)"
        :type = "instrument.typename"
        :room = "get_roomname(instrument.room_id)"
        @instrumentDeleted = "getInstruments"
        @instrumentAdded = "getInstruments"
        />
      </tbody>
    </table>
  </div>
</template>




<script setup>
import { ref, onMounted } from 'vue'
import AdminInstrumentRow from "./AdminInstrumentRow.vue";
const instruments = ref([])
const rooms = ref([])

const newInstrumentType = ref('')
const newInstrumentRoom = ref('')


async function getInstruments() {
  const res = await fetch("http://localhost/RoomMate/backend/admin/instruments.php?action=list", {
    method: "GET",
    credentials: "include"
  });
  instruments.value = await res.json();
}

async function addInstrument() {
  const formData = new FormData();
  formData.append('instrument_type', newInstrumentType.value);
  formData.append('room_id', newInstrumentRoom.value);
  try {
    const res = await fetch(`http://localhost/RoomMate/backend/admin/instruments.php?action=add`, {
      method: "POST",
      credentials: "include",
      body: formData
    });
    const result = await res.json()

    if (result.status === "success") {
      alert("Instrument added successfully!")
      await getInstruments()
    } else {
      alert(result.message || "Failed to add instrument")
    }
  } catch (error) {
    console.error("Error adding instrument:", error)
    alert("Server error while adding instrument")
  }
}

async function getRooms() {
  const res = await fetch("http://localhost/RoomMate/backend/pages/get_rooms.php", {
    method: "GET",
    credentials: "include"
  });
  rooms.value = await res.json();
}

function get_roomname(id){
  const current_room = rooms.value.find(r => Number(r.id) === Number(id)) || {}
  return current_room.roomname
}

onMounted( () => {
  getInstruments()
  getRooms()
})

</script>

<style scoped>
table {
  text-align: center;
  border-collapse: collapse;
  width: 100%;
}

 th{
      border: 1px solid #ccc;
      padding: 12px 16px;
      background-color: #f5f5f5;
    }

form {
  margin-bottom: 16px;
  display: flex;
  width: 100%;
  justify-content: center;
  gap: 8px;
}

input, select{
  padding: 6px 12px;
  font-size: 14px;
  background-color: #f5f5f5;
  color: #000000;
}

button {
  background-color: #2ecc71;
}
</style>
