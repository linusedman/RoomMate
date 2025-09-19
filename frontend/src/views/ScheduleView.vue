<template>
  <div class="schedule-page container p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Room Booking</h2>
      <button @click="logout" class="btn btn-outline-danger">Logout</button>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div v-if="loadingRooms" class="text-muted">
          Loading rooms…
        </div>
        <div v-else-if="rooms.length === 0" class="text-danger">
          No rooms found in the database.
        </div>
        <div v-else>
          <div
            v-for="(floorRooms, floor) in roomsByFloor"
            :key="floor"
            class="mb-4"
          >
            <h5>Floor {{ floor }}</h5>
            <div class="d-flex flex-wrap">
              <div
                v-for="room in floorRooms"
                :key="room.id"
                class="room-box d-flex align-items-center justify-content-center m-2"
                :class="{
                  booked: isBooked(room.id),
                  available: !isBooked(room.id),
                  selected: selectedRoom === room.id
                }"
                @click="!isBooked(room.id) && selectRoom(room.id)"
              >
                {{ room.roomname }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <h5>Booking</h5>
        <p v-if="!selectedRoom">Select a room above</p>
        <div v-else>
          <p>Selected Room: <strong>{{ roomName(selectedRoom) }}</strong></p>
          <button
            class="btn btn-success"
            :disabled="isBooked(selectedRoom)"
            @click="bookRoom"
          >
            Book Room
          </button>
        </div>

        <h5 class="mt-4">Schedule</h5>
        <ul class="list-group">
          <li
            v-for="room in rooms"
            :key="room.id"
            class="list-group-item d-flex justify-content-between align-items-center"
          >
            {{ room.roomname }}
            <span
              :class="[
                'badge',
                isBooked(room.id) ? 'bg-secondary' : 'bg-success'
              ]"
            >
              {{ isBooked(room.id) ? 'Booked' : 'Available' }}
            </span>
          </li>
          <li v-if="rooms.length && bookings.length === 0" class="list-group-item">
            No active bookings
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ScheduleView",
  data() {
    return {
      rooms: [],
      bookings: [],
      selectedRoom: null,
      loadingRooms: false,
    };
  },
  computed: {
    roomsByFloor() {
      return this.rooms.reduce((map, room) => {
        const f = room.floor || 1;
        if (!map[f]) map[f] = [];
        map[f].push(room);
        return map;
      }, {});
    },
  },
  methods: {
    async fetchData() {
      this.loadingRooms = true;
      try {
        let res = await fetch(
          "http://localhost/RoomMate/login/pages/get_rooms.php",
          { credentials: "include" }
        );
        let roomsJson = await res.json();
        this.rooms = roomsJson.map(r => ({
          id:       Number(r.id),
          roomname: r.roomname,
          floor:    Number(r.floor),
        }));

        res = await fetch(
          "http://localhost/RoomMate/login/pages/get_bookings.php",
          { credentials: "include" }
        );
        let bookingsJson = await res.json();
        this.bookings = bookingsJson.map(b => Number(b));
      } catch (e) {
        console.error("Fetch error:", e);
      } finally {
        this.loadingRooms = false;
      }
    },
    isBooked(roomId) {
      return this.bookings.includes(roomId);
    },
    selectRoom(id) {
      this.selectedRoom = id;
    },
    roomName(id) {
      const room = this.rooms.find(r => r.id === id);
      return room ? room.roomname : "";
    },
    async bookRoom() {
      const now   = new Date();
      const later = new Date(now.getTime() + 3600 * 1000);
      const form  = new URLSearchParams({
        room_id:    this.selectedRoom,
        start_time: now.toISOString().slice(0, 19).replace("T", " "),
        end_time:   later.toISOString().slice(0, 19).replace("T", " "),
      });

      try {
        const res = await fetch(
          "http://localhost/RoomMate/login/pages/book.php",
          {
            method: "POST",
            credentials: "include",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: form.toString(),
          }
        );
        const data = await res.json();
        if (data.status === "success") {
          await this.fetchData();
          this.selectedRoom = null;
        } else {
          alert(data.message);
        }
      } catch (e) {
        console.error("Book error:", e);
      }
    },
    async logout() {
      try {
        await fetch("http://localhost/RoomMate/login/pages/logout.php", {
          method: "POST",
          credentials: "include"
        });
      } catch (e) {
        console.error("Logout failed:", e);
      } finally {
        this.$router.push("/login");
      }
    },
  },
  mounted() {
    this.fetchData();
  },
};
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
}
.available {
  background-color: #28a745;
  color: #fff;
}
.booked {
  background-color: #6c757d;
  color: #fff;
  cursor: not-allowed;
}
.selected {
  border: 3px solid #0d6efd;
}
</style>
