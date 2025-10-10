<template>
  <div
  style="
  display: flex;
  flex-direction: column;
  justify-content: center;
  width: 100%;
  gap: 1rem"
  >
    <div style="display: flex; justify-content: center; gap: 1rem;">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search user"
        style="padding: 6px 12px; border-radius: 10px; border: 1px solid #000;"
      />
      <button @click="clearSearch">
        Clear search
      </button>
    </div>
    <div>
      <form @submit.prevent="addUser">
        <input
          v-model="newUsername"
          type="text"
          placeholder="Username"
          required
          maxlength="20"
          autocomplete="username"
        />
        <input v-model="newEmail"
               type="email"
               placeholder="Email"
               required
               autocomplete="email"
        />
        <input v-model="newPassword"
               type="password"
               placeholder="Password"
               required
               minlength="8"
               autocomplete="new-password"
        />
        <select v-model="newAdmin" required>
          <option value="true">Admin</option>
          <option value="false">User</option>
        </select>

        <button type="submit">Add User</button>
      </form>
    </div>
    <table>
      <thead>
        <tr>
          <th @click="sortBy('username')" style="cursor: pointer;">
            User
            <span v-if="sortKey === 'username'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th @click="sortBy('email')" style="cursor: pointer;">
            Email
            <span v-if="sortKey === 'email'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th @click="sortBy('admin')" style="cursor: pointer;">
            Admin
            <span v-if="sortKey === 'admin'">
              {{ sortOrder === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <AdminUserRow
        v-for="user in filteredAndSortedUsers"
        :key="user.id"
        :id = "Number(user.id)"
        :user = "user.username"
        :email = "user.email"
        :admin = "user.admin"
        @userDeleted = "getUsers"
        @userUpdated = "getUsers"
        />
      </tbody>
    </table>
  </div>
</template>




<script setup>
import { ref, onMounted, computed } from 'vue'
import AdminUserRow from "./AdminUserRow.vue";
const users = ref([])

const newUsername = ref('')
const newEmail = ref('')
const newPassword = ref('')
const newAdmin = ref('')

const searchQuery = ref('')
const sortKey = ref('username')
const sortOrder = ref('asc')

async function getUsers() {
  const res = await fetch("http://localhost/RoomMate/backend/admin/users.php?action=list", {
    method: "GET",
    credentials: "include"
  });
  users.value = await res.json();
}

onMounted( () => {
  getUsers()
})

async function addUser() {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(newEmail.value)) {
    alert("Please enter a valid email address.");
    return;
  }
  const formData = new FormData();
  formData.append('username', newUsername.value);
  formData.append('email', newEmail.value);
  formData.append('password', newPassword.value);
  formData.append('admin', newAdmin.value ? 1 : 0);
  try {
    const res = await fetch(`http://localhost/RoomMate/backend/admin/users.php?action=create`, {
      method: "POST",
      credentials: "include",
      body: formData
    });
    const result = await res.json()

    if (result.status === "success") {
      alert("User added successfully!")
      await getUsers()
    } else {
      alert(result.message || "Failed to add user")
    }
  } catch (error) {
    console.error("Error adding user:", error)
    alert("Server error while adding user")
  }
}
function clearSearch() {
  searchQuery.value=''
}

function sortBy(key) {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
}

const filteredAndSortedUsers = computed(() => {
  let filtered = users.value.filter(u =>
    u.username.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    u.email.toLowerCase().includes(searchQuery.value.toLowerCase())
  );

  return filtered.sort((a, b) => {
    let aVal = a[sortKey.value];
    let bVal = b[sortKey.value];
    aVal = aVal?.toString().toLowerCase();
    bVal = bVal?.toString().toLowerCase();
    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});
</script>

<style scoped>
table {
  text-align: center;
  border-collapse: collapse;
  width: 100%;
}

 th{
      border: 1px solid #ccc;
      padding: 12px 16px;
      background-color: #f5f5f5;
    }

 form {
  margin-bottom: 16px;
  display: flex;
  width: 100%;
  justify-content: center;
  gap: 8px;
}

 input, select{
  padding: 6px 12px;
  font-size: 14px;
  background-color: #ffffff;
  color: #000000;
    border-radius: 10px;
  border-color: #000000;
  border-style: solid;
}

button {
  background-color: #2ecc71;
}
</style>