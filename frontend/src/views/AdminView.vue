<template>
  <div class="admin-view d-flex">
    <div class="left-panel p-3">
      <AdminUser/>
      <AdminInstruments/>
    </div>

    <div class="right-panel p-3">
      <AdminBookings/>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminUser from "../components/AdminUser.vue";
import AdminBookings from "../components/AdminBookings.vue";
import AdminInstruments from "../components/AdminInstruments.vue";
</script>

<style scoped>
.admin-view { display:flex; gap:1rem; }
.left-panel, .right-panel { width:50%; }

</style>
