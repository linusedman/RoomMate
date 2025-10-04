<template>
  <tr>
    <td> {{ type }}</td>
    <td> {{ room }}</td>
    <td>
      <button @click="deleteInstrument">
        Delete
      </button>
    </td>
  </tr>
</template>




<script setup>
import { ref } from 'vue'
const props = defineProps({
  id: Number,
  type: String,
  room: String,
})

const emit = defineEmits(["instrumentDeleted"])

async function deleteInstrument() {
  const confirmed = confirm(`Are you sure you want to delete ${props.user}?`)
  if (!confirmed) return
  try {
    const response = await fetch(`http://localhost/RoomMate/backend/admin/instruments.php?action=delete`, {
      method: "POST",
      credentials: "include",
      body: new URLSearchParams({ id: props.id }),
    })

    const result = await response.json()

    if (result.status === "success") {
      alert("Instrument deleted successfully!")
      emit("instrumentDeleted", props.id) // tell parent to refresh list
    } else {
      alert(result.message || "Failed to delete instrument")
    }
  } catch (error) {
    console.error("Error deleting instrument:", error)
    alert("Server error while deleting instrument")
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