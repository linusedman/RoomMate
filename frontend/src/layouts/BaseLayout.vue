<template>
  <div>
    <header class="bg-success text-white p-3 text-center">
      <h1>RoomMate</h1>
      <button @click="logout" class="btn btn-outline-danger"
      style="margin-left: auto;
              position: absolute;
                right: 20px;"
      v-show="loggedIn">
        Logout
      </button>

      <button @click="switchPage" class="btn btn-outline-danger"
      style="margin-left: auto;
              position: absolute;
                right: 100px;"
      v-show="admin===true">
        Switch
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
import { loggedIn, admin } from "../router";

export default {
  name: "BaseLayout",
  setup() {
    return { loggedIn, admin };
  },
  methods: {
    async logout() {
      this.$router.push("/logout")
    },
    async switchPage() {
      const current = this.$route.path;
      if (current === '/admin') {
        await this.$router.push('/main');
      } else if (current === '/main') {
        await this.$router.push('/admin');
      }
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
</style>