<template>
  <div class="container p-5 d-flex flex-column align-items-center bg-light">
    <form
      @submit.prevent="handleReset"
      class="form-control mt-5 p-4"
      style="width:380px; box-shadow:
             rgba(60, 64, 67, 0.3) 0px 1px 2px,
             rgba(60, 64, 67, 0.15) 0px 2px 6px;"
    >
      <div class="row text-center mb-4">
        <i class="fa fa-unlock-alt fa-3x" style="color: navy;"></i>
        <h5 class="fw-bold mt-3">Reset Your Password</h5>
      </div>

      <div class="mb-3">
        <label for="email"><i class="fa fa-envelope"></i> Email</label>
        <input
          id="email"
          type="email"
          v-model="email"
          class="form-control"
          required
        />
      </div>

      <div class="mb-3">
        <button type="submit" class="btn btn-primary fw-bold w-100">
          Send Reset Link
        </button>
      </div>

      <div class="text-center fw-bold text-primary">
        <router-link to="/login" class="text-decoration-none">
          Back to Login
        </router-link>
      </div>

      <p v-if="message" :class="messageClass" class="mt-3 text-center">
        {{ message }}
      </p>
    </form>
  </div>
</template>

<script>
export default {
  name: "ResetPasswordView",
  data() {
    return {
      email: "",
      message: "",
      messageClass: "text-danger",
    };
  },
  methods: {
    async handleReset() {
      this.message = "";
      try {
        const res = await fetch(
          "http://localhost/RoomMate/backend/pages/resetpassword.php",
          {
            method: "POST",
            credentials: "include",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
              email: this.email,
            }),
          }
        );

        const data = await res.json();
        if (data.status === "success") {
          this.message = data.message;
          this.messageClass = "text-success";
          setTimeout(() => this.$router.push("/login"), 2000);
        } else {
          this.message = data.message || "Reset failed.";
          this.messageClass = "text-danger";
        }
      } catch (e) {
        console.error("Reset error:", e);
        this.message = "Server error. Please try again.";
        this.messageClass = "text-danger";
      }
    },
  },
};
</script>

<style scoped>

</style>
