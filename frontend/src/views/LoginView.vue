<template>
  <div class="container p-5 d-flex flex-column align-items-center bg-light">
    <form @submit.prevent="handleLogin"
      class="form-control mt-5 p-4"
      style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 
             0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
      <div class="row text-center">
        <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="color: green;"></i>
        <h5 class="p-4 fw-bold">Login Into Your Account</h5>
      </div>
      <div class="mb-3">
        <label for="email"><i class="fa fa-envelope"></i> Email</label>
        <input type="text" v-model="email" id="email" class="form-control" required />
      </div>
      <div class="mb-3">
        <label for="password"><i class="fa fa-lock"></i> Password</label>
        <input type="password" v-model="password" id="password" class="form-control" required />
      </div>
      <div class="mb-3">
        <button type="submit" class="btn btn-success fw-bold">Login</button>
      </div>
      <div class="mb-2 mt-4 text-center fw-bold text-primary">
        <router-link to="/register" class="text-decoration-none">Create Account</router-link>
        OR
        <router-link to="/resetpassword" class="text-decoration-none">Forgot Password</router-link>
      </div>
      <p v-if="successMessage" class="text-success mt-3">{{ successMessage }}</p>
      <p v-if="errorMessage"   class="text-danger mt-3">{{ errorMessage }}</p>
    </form>
  </div>
</template>

<script>
export default {
  name: "LoginView",
  data() {
    return {
      email: "",
      password: "",
      errorMessage: "",
      successMessage: "",
    };
  },
  methods: {
    async handleLogin() {
      const formData = new URLSearchParams();
      formData.append("email", this.email);
      formData.append("password", this.password);

      try {
        const response = await fetch("http://localhost/RoomMate/login/pages/login.php", {
          method: "POST",
          credentials: "include",  
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: formData.toString(),
        });

        const data = await response.json();

        if (data.status === "success") {
          this.successMessage = data.message;
          this.errorMessage = "";
          setTimeout(() => {
            this.$router.push("/schedule");
          }, 1500);
        } else {
          this.errorMessage = data.message || "Login failed.";
          this.successMessage = "";
        }

      } catch (e) {
        this.errorMessage = "Server error. Please try again.";
        this.successMessage = "";
        console.error("Login fetch error:", e);
      }
    }
  },
};
</script>
