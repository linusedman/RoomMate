<template>
  <div class="container p-5 d-flex flex-column align-items-center bg-light">
    <form @submit.prevent="handleChangePassword"
      class="form-control mt-5 p-4"
      style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 
             0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
      <div class="row text-center">
        <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="color: #005cbf;"></i>

        <h5 class="p-4 fw-bold">Change Your Password</h5>
      </div>
      <div class="mb-3">
        <label for="currentPassword"><i class="fa fa-lock"></i> Current Password</label>
        <input type="password" v-model="currentPassword" id="currentPassword" class="form-control" required />
      </div>
      <div class="mb-3">
        <label for="newPassword"><i class="fa fa-lock"></i> New Password</label>
        <input type="password" v-model="newPassword" id="newPassword" class="form-control" required minlength="8" />
        <small class="text-muted">Password must be at least 8 characters long.</small>
      </div>
      <div class="mb-3">
        <label for="confirmPassword"><i class="fa fa-lock"></i> Confirm New Password</label>
        <input type="password" v-model="confirmPassword" id="confirmPassword" class="form-control" required minlength="8" />
      </div>
      <div class="mb-3">
        <button type="update" class="btn btn-update fw-bold" :disabled="submitting">
          {{ submitting ? 'Saving…' : 'Update Password' }}
        </button>
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
      identifier: "",
      email: "",
      password: "",
      errorMessage: "",
      successMessage: "",
    };
  },
  methods: {
    async handleLogin() {
      const formData = new URLSearchParams();
      formData.append("identifier", this.identifier);
      formData.append("password", this.password);

      try {
        const response = await fetch("http://localhost/RoomMate/backend/pages/login.php", {
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
          if (data.role === true) {
            setTimeout(() => {
              this.$router.push("/admin");
            }, 1500);
          }
          else {
            setTimeout(() => {
              this.$router.push("/main");
            }, 1500);
          }
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

<style scoped>
.btn-update {
  background-color: #005cbf;
  border: none;
  color: white;
}
.btn-update:disabled {
  background-color: #7fb8ea;
  cursor: not-allowed;
}

</style>

