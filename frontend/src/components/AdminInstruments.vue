<template>
  <div
  style="
  display: flex;
  flex-direction: column;
  justify-content: center;
  width: 100%;
  gap: 1rem"
  >
    <div style="display: flex; justify-content: center; gap: 1rem;">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search Instrument"
        style="padding: 6px 12px; border-radius: 10px; border: 1px solid #000;"
      />
      <select v-model="selectedRoom" style="padding: 6px 12px; border-radius: 10px; border: 1px solid #000;">
        <option value="">All Rooms</option>
        <option v-for="room in rooms" :key="room.id" :value="room.id">
          {{ room.roomname }}
        </option>
      </select>
      <button @click="clearSearch">
        Clear search
      </button>
    </div>
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
          <th @click="sortBy('typename')" style="cursor: pointer;">
            Instrument
            <span v-if="sortKey === 'typename'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th @click="sortBy('room')" style="cursor: pointer;">
            Room
            <span v-if="sortKey === 'room'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <template v-if="filteredAndSortedInstruments.length > 0">
          <AdminInstrumentRow
          v-for="instrument in filteredAndSortedInstruments"
          :key = "Number(instrument.id)"
          :id = "Number(instrument.id)"
          :type = "instrument.typename"
          :room = "get_roomname(instrument.room_id)"
          @instrumentDeleted = "getInstruments"
          @instrumentAdded = "getInstruments"
          />
        </template>
        <tr v-else>
          <td colspan="3" class="no-results">
            No instruments found
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>




<script setup>
import {ref, onMounted, computed} from 'vue'
import AdminInstrumentRow from "./AdminInstrumentRow.vue";
const instruments = ref([])
const rooms = ref([])

const newInstrumentType = ref('')
const newInstrumentRoom = ref('')

const searchQuery = ref('')
const sortKey = ref('instrument_name')
const sortOrder = ref('asc')
const selectedRoom = ref('');


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

function sortBy(key) {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
}

const filteredAndSortedInstruments = computed(() => {
  let filtered = instruments.value.filter(u =>
    u.typename.toLowerCase().includes(searchQuery.value.toLowerCase())
  );

  if (selectedRoom.value) {
    filtered = filtered.filter(u => Number(u.room_id) === Number(selectedRoom.value));
  }

  return filtered.sort((a, b) => {
    let aVal, bVal;

    if (sortKey.value === 'room') {
      aVal = get_roomname(a.room_id) || '';
      bVal = get_roomname(b.room_id) || '';
    } else {
      aVal = a[sortKey.value];
      bVal = b[sortKey.value];
    }

    aVal = aVal?.toString().toLowerCase();
    bVal = bVal?.toString().toLowerCase();
    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});

function clearSearch() {
  searchQuery.value=''
  selectedRoom.value=''
}
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
  background-color: #ffffff;
  color: #000000;
    border-radius: 10px;
  border-color: #000000;
  border-style: solid;
}

button {
  background-color: #2ecc71;
}

.no-results {
  text-align: center;
  padding: 12px;
  color: #666;
  font-style: italic;
}
</style>
