<template>
  <table>
    <thead>
      <tr>
        <th>User</th>
        <th>Email</th>
        <th>Admin</th>
        <th></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <AdminUserRow
      v-for="user in users"
      :id = "Number(user.id)"
      :user = "user.username"
      :email = "user.email"
      :admin = "user.admin"
      @userDeleted = "getUsers"
      @userUpdated = "getUsers"
      />
    </tbody>
  </table>
</template>




<script setup>
import { ref, onMounted } from 'vue'
import AdminUserRow from "./AdminUserRow.vue";
const users = ref([])


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
</style>