<script setup>
import LayoutView  from "./LayoutView.vue";
import ScheduleView from "./ScheduleView.vue";
</script>

<template>
  <div
  style="display: flex">
    <div class="app_container">
      <ScheduleView/>
    </div>
    <div class="app_container">
      <LayoutView/>
    </div>
  </div>
</template>

<style scoped>
.app_container{
  width: 50%;
  justify-items: center;
}
</style>