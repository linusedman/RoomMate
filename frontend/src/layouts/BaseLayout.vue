<template>
  <div>
    <header class="text-white p-3 text-center" style="background-color: #0087e6;">

      <router-link to="/main" class="logo-link">
        <img
        src="../assets/RoomMate_Logo.png"
        alt="RoomMate Logo"
        class="logo"
        v-show="admin===false"/>
      </router-link>

      <router-link to="/admin" class="logo-link">
        <img
        src="../assets/RoomMate_Logo_inverted.png"
        alt="RoomMate Logo"
        class="logo"
        v-show="admin===true"/>
      </router-link>

      <h1>RoomMate</h1>
      <button @click="logout" class="logout-btn"
      style="margin-left: auto;
              position: absolute;
                right: 20px;"
      v-show="loggedIn">
        Logout
      </button>

      <button @click="settingsPage" class="settings-btn"
      style="margin-left: auto;
              position: absolute;
                right: 130px;"
      v-show="loggedIn">
        {{settingsButtonLabel}}
      </button>

      <button @click="switchPage" class="switch-btn"
      style="margin-left: auto;
              position: absolute;
                right: 250px;"
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
  computed: {
    settingsButtonLabel() {
      return this.$route.path === "/settings" ? "Home" : "Settings";
    },
  },
  methods: {
    async logout() {
      this.$router.push("/logout")
    },
    async switchPage() {
      const current = this.$route.path;
      if (current === '/admin') {
        await this.$router.push('/main');
      } else {
        await this.$router.push('/admin');
      }
    },
     async settingsPage() {
      const current = this.$route.path;
      if (admin) {
        if (current === '/settings') {
          await this.$router.push('/admin');
        } else {
          await this.$router.push('/settings');
        }
      } else {
        if (current === '/settings') {
          await this.$router.push('/main');
        } else {
          await this.$router.push('/settings');
        }
      }
    }
  },
};


</script>

<style scoped>
main {
  min-height: 75vh;
}

header {
  display: flex;
  justify-content: center;  
  align-items: center;      
  position: relative;
  padding: 20px 0;
  border-radius: 8px;          
}

header h1 {
  margin: 0 auto; 
}
.logo {
  position: absolute;
  left: 20px;
  height: 80px;
  object-fit: contain;
  cursor: pointer;
}

.logo-link {
  left: 0px;
  display: flex;
  align-items: center;
}

.logout-btn {
  background-color: transparent;
  color: white;
  border: 2px solid white;
}

.logout-btn:hover {
  background-color: #ff6b5a;
  border-color: #ff6b5a;
}

.switch-btn,
.settings-btn {
  background-color: transparent;
  color: white;
  border: 2px solid white;
}

.settings-btn {
  width: 105px;
}

.switch-btn:hover,
.settings-btn:hover {
  background-color: #03d4a8;
  border-color: #03d4a8;
}

@media (prefers-color-scheme: light) {
  .logout-btn {
    background-color: transparent;
    border-color: white;
    color: white;
  }
  .logout-btn:hover {
    background-color: #e74c3c;
  }

  .switch-btn,
  .settings-btn {
    background-color: transparent;
    border-color: white;
    color: white;
  }
  .switch-btn:hover,
  .settings-btn:hover {
    background-color: #03d4a8;
  }
}


</style>