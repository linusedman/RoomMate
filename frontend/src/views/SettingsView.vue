<template>
    <h1>Settings</h1>
    <div class="settings-view d-flex">
        <div class="left-panel p-3">
            <h2>Account information</h2>
            <div v-if="loading">Loading...</div>
            <div v-else-if="error" class="text-danger">{{ error }}</div>
            <div v-else>
                <p><strong>Username:</strong> {{ user.username }}</p>
                <p><strong>Email:</strong> {{ user.email }}</p>
            </div>
        </div>
        <div class="right-panel p-3">
            <h2>Handle account</h2>
            <div class="change-password-card">
                <button class="change-password-btn" @click="$router.push({ name: 'ChangePassword' })">
                Change password
                </button>
            </div>
            <div class="delete-account-card">
            <button class="delete-btn" @click="deleteAccount">
                Delete account
            </button>
            <div class="warning-text">
                This action is irreversible.
            </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useRouter } from "vue-router"
const router = useRouter()
const user = ref({ username: "", email: "" })
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  try {
    const response = await fetch("http://localhost/RoomMate/backend/pages/view_user_info.php", {credentials: "include" })
    const data = await response.json()
    if (data.status === "success") {
      user.value.username = data.username
      user.value.email = data.email
    } else {
      error.value = data.message || "Failed to load user info."
    }
  } catch (err) {
        console.error(err)
        error.value = "Error connecting to server."
  } finally {
        loading.value = false
  }
})

async function deleteAccount() {
    // ask for user confirmation
    if (!confirm("Are you sure you want to delete your account?")) {
        return
    }
    try {
        const response = await fetch("http://localhost/RoomMate/backend/pages/delete_user.php", { credentials: "include" })
        const data = await response.json()
        if (data.status === "success") {
            alert("Your account has been deleted.")
            router.push("/logout")
        } else {
            alert("Failed to delete account.")
        }
    } catch (e) {
        console.error(e)
    }
}
</script>

<style scoped>
.settings-view {
  display: flex; 
  gap: 1rem;
  min-height: 70vh;
  align-items: flex-start;
}

.left-panel,
.right-panel {
  flex: 1 1 0%;
  min-width: 0;
}

.left-panel {
  text-align: left;
}

.right-panel {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
</style>