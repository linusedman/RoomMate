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
        placeholder="Search by User"
      />
      <select v-model="selectedRoom">
        <option value="">All Rooms</option>
        <option v-for="room in rooms" :key="room.id" :value="room.id">
          {{ room.roomname }}
        </option>
      </select>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label>From:</label>
        <input
            v-model="searchStart"
            type="datetime-local"
            placeholder="Start Time"
        />
      </div>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label>To:</label>
        <input
          v-model="searchEnd"
          type="datetime-local"
          placeholder="End time"
        />
      </div>
      <button @click="clearSearch">
        Clear search
      </button>
    </div>
    <table>
      <thead>
        <tr>
          <th @click="sortBy('username')" style="cursor: pointer;">
            User
            <span v-if="sortKey === 'username'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th @click="sortBy('room')" style="cursor: pointer;">
            Room
            <span v-if="sortKey === 'room'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th @click="sortBy('start_time')" style="cursor: pointer;">
            Start Time
            <span v-if="sortKey === 'start_time'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th @click="sortBy('end_time')" style="cursor: pointer;">
            End Time
            <span v-if="sortKey === 'end_time'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <template v-if="filteredAndSortedBookings.length > 0">
          <AdminBookingRow
          v-for="booking in filteredAndSortedBookings"
          :key= "Number(booking.id)"
          :id = "Number(booking.id)"
          :user = "booking.username"
          :room_id = "booking.room_id"
          :start = "booking.start_time"
          :end = "booking.end_time"
          :rooms = "rooms"
          @bookingDeleted = "getBookings"
          @bookingUpdated = "getBookings"
          />
        </template>
        <tr v-else>
          <td colspan="6" class="no-results">
            No bookings found
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>




<script setup>
import {ref, onMounted, computed} from 'vue'
import AdminBookingRow from "./AdminBookingRow.vue";
const bookings = ref([])
const rooms = ref([])

const searchQuery = ref('')
const searchStart = ref('')
const searchEnd = ref('')
const sortKey = ref('start_time')
const sortOrder = ref('asc')
const selectedRoom = ref('');


async function getBookings() {
  const res = await fetch("http://localhost/RoomMate/backend/admin/bookings.php?action=list", {
    method: "GET",
    credentials: "include"
  });
  bookings.value = await res.json();
}

async function getRooms() {
  const res = await fetch("http://localhost/RoomMate/backend/pages/get_rooms.php", {
    method: "GET",
    credentials: "include"
  });
  rooms.value = await res.json();
}


onMounted( () => {
  getRooms()
  getBookings()
})

function get_roomname(id){
  const current_room = rooms.value.find(r => Number(r.id) === Number(id)) || {}
  return current_room.roomname
}

function sortBy(key) {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
}

const filteredAndSortedBookings = computed(() => {
  let filtered = bookings.value.filter(u =>
    u.username.toLowerCase().includes(searchQuery.value.toLowerCase())
  );

  if (selectedRoom.value) {
    filtered = filtered.filter(u => Number(u.room_id) === Number(selectedRoom.value));
  }

  if (searchStart.value) {
    const startDate = new Date(searchStart.value).getTime();
    filtered = filtered.filter(u => new Date(u.end_time).getTime() >= startDate);
  }

  if (searchEnd.value) {
    const endDate = new Date(searchEnd.value).getTime();
    filtered = filtered.filter(u => new Date(u.start_time).getTime() <= endDate);
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
  searchStart.value=''
  searchEnd.value=''
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
  background-color: #21d6e0;
}

.no-results {
  text-align: center;
  padding: 12px;
  color: #666;
  font-style: italic;
}
</style>