<template>
  <div class="container p-5 d-flex flex-column align-items-center">
    <form @submit.prevent="handleChangePassword"
      class="form-control mt-5 p-4"
      style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 
             0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
      <div class="row text-center">
        <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="color: #005cbf;"></i>

        <h5 class="p-4 fw-bold">Change Password</h5>
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
        <label for="confirmPassword"><i class="fa fa-lock"></i> Confirm Password</label>
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
  name: "ChangePasswordView",
  data() {
    return {
      currentPassword: "",
      newPassword: "",
      confirmPassword: "",
      submitting: false,
      errorMessage: "",
      successMessage: "",
    };
  },
    methods: {
    async handleChangePassword() {
      this.errorMessage = ''
      this.successMessage = ''

    //   Same as in RegisterView.vue
      if (this.newPassword.length < 8) {
        this.errorMessage = 'New password must be at least 8 characters long.'
        return
      }
    //   ---------------------------
      if (this.newPassword !== this.confirmPassword) {
        this.errorMessage = 'New password and confirmation do not match.'
        return
      }
      if (this.newPassword === this.currentPassword) {
        this.errorMessage = 'New password must be different from current password.'
        return
      }

      this.submitting = true
      try {
        const body = new URLSearchParams({
        current_password: this.currentPassword,
        new_password: this.newPassword,
        });

        const res = await fetch('http://localhost/RoomMate/backend/pages/user_update_password.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        }
        );

        const text = await res.text();
        console.log('Raw response from backend:', text);

        let data;
        try {
        data = JSON.parse(text);
        } catch (err) {
        console.error('Response is not valid JSON:', err);
        this.errorMessage = 'Server returned invalid response.';
        return;
        }

        if (data.status === 'success') {
        this.successMessage = data.message || 'Password updated.';
        setTimeout(() => this.$router.push('/main'), 800);
        } else {
        this.errorMessage = data.message || 'Failed to update password.';
        }
    } catch (e) {
        console.error('Network or fetch error:', e);
        this.errorMessage = 'Server error. Please try again.';
    } finally {
        this.submitting = false;
    }
    },
  },
}
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

