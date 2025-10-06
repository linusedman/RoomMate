<template>
  <h1>Admin</h1>
  <div class="panel-selector d-flex">
    <div
      class="panel-box"
      :class="{ active: activePanel === 'users' }"
      @click="activePanel = 'users'"
    >
      Users
    </div>
    <div
      class="panel-box"
      :class="{ active: activePanel === 'instruments' }"
      @click="activePanel = 'instruments'"
    >
      Instruments
    </div>
    <div
      class="panel-box"
      :class="{ active: activePanel === 'bookings' }"
      @click="activePanel = 'bookings'"
    >
      Bookings
    </div>
  </div>
  <div class="admin-view d-flex">
    <AdminUser
        v-if="activePanel==='users'"
    />
    <AdminInstruments
        v-if="activePanel==='instruments'"
    />
    <AdminBookings
        v-if="activePanel==='bookings'"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminUser from "../components/AdminUser.vue";
import AdminBookings from "../components/AdminBookings.vue";
import AdminInstruments from "../components/AdminInstruments.vue";
const activePanel = ref('users')


</script>

<style scoped>
.admin-view { display:flex; gap:1rem; }
.panel-selector {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  justify-content: center;
  width:100%
}

.panel-box {
  padding: 1rem 2rem;
  border: 1px solid #ccc;
  border-radius: 0.5rem;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s;
  user-select: none;
}

.panel-box:hover {
  background-color: #f0f0f0;
}

.panel-box.active {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}
</style>
