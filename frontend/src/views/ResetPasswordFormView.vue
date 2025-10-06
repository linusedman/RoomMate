<template>
  <div class="container p-5 d-flex flex-column align-items-center bg-light">
    <div class="card p-4" style="max-width:440px; width:100%;">
      <h4 class="text-center mb-3">Set New Password</h4>

      <div v-if="loading" class="text-center">Checking reset link…</div>

      <div v-else-if="invalid" class="alert alert-danger text-center">
        {{ message }}
      </div>

      <form v-else @submit.prevent="submit" novalidate>
        <div class="mb-3">
          <label for="password" class="form-label">New password</label>
          <input id="password" v-model="password" type="password" class="form-control" minlength="8" required />
        </div>
        <div class="mb-3">
          <label for="confirm" class="form-label">Confirm password</label>
          <input id="confirm" v-model="confirm" type="password" class="form-control" minlength="8" required />
        </div>

        <button class="btn btn-primary w-100" :disabled="submitting">
          {{ submitting ? 'Saving...' : 'Update password' }}
        </button>

        <div v-if="message" :class="msgClass" class="mt-3 text-center">{{ message }}</div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ResetPasswordFormView',
  data() {
    return {
      key: null,
      password: '',
      confirm: '',
      loading: true,
      invalid: false,
      message: '',
      msgClass: '',
      submitting: false
    }
  },
  async mounted() {
    const params = new URLSearchParams(window.location.search);
    this.key = params.get('key');

    if (!this.key) {
      this.invalid = true;
      this.loading = false;
      this.message = 'No reset key provided.';
      return;
    }

    // Validate key
    try {
      const res = await fetch(`http://localhost/RoomMate/backend/pages/validate_reset.php?key=${encodeURIComponent(this.key)}`);
      const data = await res.json();
      if (data.status !== 'success') {
        this.invalid = true;
        this.message = data.message || 'Invalid or expired reset link.';
      }
    } catch (e) {
      this.invalid = true;
      this.message = 'Server error. Try again later.';
      console.error(e);
    } finally {
      this.loading = false;
    }
  },
  methods: {
    async submit() {
      this.message = '';
      if (this.password.length < 8) {
        this.message = 'Password must be at least 8 characters.';
        this.msgClass = 'text-danger';
        return;
      }
      if (this.password !== this.confirm) {
        this.message = 'Passwords do not match.';
        this.msgClass = 'text-danger';
        return;
      }

      this.submitting = true;
      try {
        const body = new URLSearchParams({ key: this.key, password: this.password });
        const res = await fetch('http://localhost/RoomMate/backend/pages/updatepassword.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body
        });
        const data = await res.json();
        if (data.status === 'success') {
          this.message = data.message || 'Password updated.';
          this.msgClass = 'text-success';
          // redirect if backend includes redirect
          const redirect = data.redirect || '/login';
          setTimeout(() => this.$router.push(redirect), 1500);
        } else {
          this.message = data.message || 'Could not update password.';
          this.msgClass = 'text-danger';
        }
      } catch (e) {
        console.error(e);
        this.message = 'Server error. Try again later.';
        this.msgClass = 'text-danger';
      } finally {
        this.submitting = false;
      }
    }
  }
}
</script>

<style scoped>
.reset-form {
  max-width: 400px;
  margin: auto;
  padding: 2rem;
  border: 1px solid #ddd;
  border-radius: 10px;
}
input {
  width: 100%;
  padding: 10px;
  margin: 10px 0;
}
button {
  width: 100%;
  padding: 10px;
  background: #4caf50;
  color: white;
  border: none;
  cursor: pointer;
}
p.success {
  color: green;
}
p.error {
  color: red;
}
</style>
