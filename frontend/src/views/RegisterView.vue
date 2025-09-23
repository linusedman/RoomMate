<template>
  <div class="container p-5 d-flex flex-column align-items-center bg-light">
    <form @submit.prevent="handleRegister"
      class="form-control mt-5 p-4"
      style="height:auto; width:380px;
      box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px,
      rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
      
      <div class="row text-center">
        <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="color: green;"></i>
        <h5 class="p-4 fw-bold">Create Your Account</h5>
      </div>

      <div class="mb-2">
        <label for="username"><i class="fa fa-user"></i> User Name</label>
        <input type="text" v-model="username" id="username" class="form-control" required />
      </div>

      <div class="mb-2 mt-2">
        <label for="email"><i class="fa fa-envelope"></i> Email</label>
        <input type="email" v-model="email" id="email" class="form-control" required />
      </div>

      <div class="mb-2 mt-2">
        <label for="password"><i class="fa fa-lock"></i> Password</label>
        <input type="password" v-model="password" id="password" class="form-control" required />
      </div>

      <div class="mb-2 mt-3">
        <button type="submit" class="btn btn-success fw-bold">Create Account</button>
      </div>

      <div class="mb-2 mt-4 text-center fw-bold text-primary">
        <router-link to="/login" class="text-decoration-none">I have an Account</router-link>
      </div>

      <div v-if="message" class="toast align-items-center text-white border-0 mt-3"
        role="alert" aria-live="assertive" aria-atomic="true"
        :style="{ backgroundColor: messageColor }">
        <div class="d-flex">
          <div class="toast-body">{{ message }}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto"
            @click="message = ''" aria-label="Close"></button>
        </div>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  name: "RegisterView",
  data() {
    return {
      username: "",
      email: "",
      password: "",
      message: "",
      messageColor: "#dc3545",
    };
  },
  methods: {
    async handleRegister() {
      const formData = new URLSearchParams();
      formData.append("username", this.username);
      formData.append("email", this.email);
      formData.append("password", this.password);

      try {
        const response = await fetch("http://localhost:80/RoomMate/backend/pages/register.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: formData.toString(),
        });

        const data = await response.json();

        if (data.status === "success") {
          this.message = data.message;
          this.messageColor = "#28a745";
          setTimeout(() => this.$router.push("/login"), 1500);
        } else {
          this.message = data.message || "Registration failed.";
          this.messageColor = "#dc3545";
        }
      } catch {
        this.message = "Server error, try again.";
        this.messageColor = "#dc3545";
      }
    },
  },
};
</script>
