<template>
  <div>
    <header class="bg-success text-white p-3 text-center">
      <h1>RoomMate</h1>
    </header>

    <main class="container mt-4">
      <router-view />
    </main>

    <footer class="bg-light text-center p-3 mt-5 border-top">
      <small>&copy; 2025 RoomMate. All rights reserved.</small>
    </footer>
  </div>
</template>

<script>
export default {
  name: "BaseLayout",
};
</script>

<style scoped>
main {
  min-height: 70vh;
}
</style>