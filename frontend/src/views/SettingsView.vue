<template>
    <h1>Settings</h1>
    <div class="settings-view d-flex">
        <div class="left-panel p-3">

        </div>
        <div class="right-panel p-3">
            <div class="danger-card">
            <button class="delete-btn" @click="deleteAccount">
                Delete My Account
            </button>
            <div class="warning-text">
                This action is irreversible.
            </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from "vue-router"
const router = useRouter()

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
</style>