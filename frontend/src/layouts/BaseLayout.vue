<template>
  <div>
    <header class="bg-success text-white p-3 text-center">
      <h1>RoomMate</h1>
      <button @click="logout" class="btn btn-outline-danger"
      style="margin-left: auto"
      v-show="loggedIn">
        Logout
      </button>
    </header>

    <main class="container mt-4">
      <router-view/>
    </main>

    <footer class="bg-light text-center p-3 mt-5 border-top">
      <small>&copy; 2025 RoomMate. All rights reserved.</small>
    </footer>
  </div>
</template>

<script>
import axios from "axios";
import { loggedIn } from "../router";

export default {
  name: "BaseLayout",
  setup() {
    return { loggedIn };
  },
  methods: {
    async logout() {
      this.$router.push("/logout")
    }
  },
};


</script>

<style scoped>
main {
  min-height: 70vh;
}

header {
  display: flex;
  justify-content: center;  /* centers horizontally */
  align-items: center;      /* centers vertically */
  position: relative;
  padding: 20px 0;          /* adds space above/below */
}

header h1 {
  margin: 0 auto; /* remove default h1 margin */
}

header button {
  position: absolute;
  right: 20px; /* keep logout button on the right */
}
</style>