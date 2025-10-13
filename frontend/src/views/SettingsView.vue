<template>
    <h1>Settings</h1>
    <div class="danger-card">
      <button class="delete-btn" @click="deleteAccount">
        Delete My Account
      </button>
      <div class="warning-text">
        This action is irreversible.
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

