<template>
  <table>
    <thead>
      <tr>
        <th>User</th>
        <th>Room</th>
        <th>Start Time</th>
        <th>End Time</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <AdminBookingRow
      v-for="booking in bookings"
      :id = "Number(booking.id)"
      :user = "booking.username"
      :room = "get_roomname(booking.room_id)"
      :start = "booking.start_time"
      :end = "booking.end_time"
      @bookingDeleted = "getBookings"
      />
    </tbody>
  </table>
</template>




<script setup>
import { ref, onMounted } from 'vue'
import AdminBookingRow from "./AdminBookingRow.vue";
const bookings = ref([])
const rooms = ref([])


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

function get_roomname(id){
  const current_room = rooms.value.find(r => Number(r.id) === Number(id)) || {}
  return current_room.roomname
}

onMounted( () => {
  getRooms()
  getBookings()
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
</style>