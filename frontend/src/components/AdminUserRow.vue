<template>
  <tr>
    <td v-if="!isEditing">{{ user }}</td>
    <td v-else><input v-model="editedUser" /></td>

    <td v-if="!isEditing">{{ email }}</td>
    <td v-else><input v-model="editedEmail" /></td>

    <td v-if="!isEditing">{{ Number(admin) ? "Yes" : "No" }}</td>
    <td v-else>
      <select v-model="editedAdmin">
        <option :value="true">Yes</option>
        <option :value="false">No</option>
      </select>
    </td>

    <td>
      <button v-if="!isEditing" @click="startEditing">Edit</button>
      <button v-if="isEditing" @click="saveEdit">Save</button>
      <button v-if="isEditing" @click="cancelEdit">Cancel</button>
    </td>

    <td>
      <button @click="deleteUser">Delete</button>
    </td>
  </tr>
</template>




<script setup>
import { ref } from 'vue'
const props = defineProps({
  id: Number,
  user: String,
  email: String,
  admin: String,
})

const isEditing = ref(false);
const editedUser = ref(props.user);
const editedEmail = ref(props.email);
const editedAdmin = ref(Number(props.admin) ? "Yes" : "No");

const emit = defineEmits(["userDeleted", "userUpdated"])

async function deleteUser() {
  const confirmed = confirm(`Are you sure you want to delete ${props.user}?`)
  if (!confirmed) return
  try {
    const response = await fetch(`http://localhost/RoomMate/backend/admin/users.php?action=delete`, {
      method: "POST",
      credentials: "include",
      body: new URLSearchParams({ id: props.id }),
    })

    const result = await response.json()

    if (result.status === "success") {
      alert("User deleted successfully!")
      emit("userDeleted", props.id) // tell parent to refresh list
    } else {
      alert(result.message || "Failed to delete user")
    }
  } catch (error) {
    console.error("Error deleting user:", error)
    alert("Server error while deleting user")
  }
}

function startEditing() {
  isEditing.value = true;
}

function cancelEdit() {
  isEditing.value = false;
  editedUser.value = props.user;
  editedEmail.value = props.email;
  editedAdmin.value = props.admin ? "Yes" : "No";
}

async function saveEdit() {
  const formData = new FormData();
  formData.append('id', props.id)
  formData.append('username', editedUser.value);
  formData.append('email', editedEmail.value);
  formData.append('admin', editedAdmin.value ? 1 : 0);
  try {
    const res = await fetch(`http://localhost/RoomMate/backend/admin/users.php?action=update`, {
      method: "POST",
      credentials: "include",
      body: formData
    });
    const result = await res.json()

    if (result.status === "success") {
      isEditing.value = false;
      alert("User updated successfully!")
      emit('userUpdated');
    } else {
      alert(result.message || "Failed to update user")
    }
  } catch (error) {
    console.error("Error updating user:", error)
    alert("Server error while updating user")
  }
}

</script>

<style scoped>
td {
      border: 1px solid #ccc;
      padding: 12px 16px;
    }
tr:nth-child(even) {
      background-color: #fafafa;
    }
button {
  background-color: #2ecc71;
}
</style>