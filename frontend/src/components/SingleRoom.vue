<script setup>
import { ref } from 'vue'
const props = defineProps({
  room_number: {
    type: String,
    required: true,
  },
})

const start_time = ref('')
const end_time = ref('')


const submitForm = async () => {
  const formData = new FormData()
  formData.append("roomnumber", props.room_number)
  formData.append("start_time", start_time.value)
  formData.append("end_time", end_time.value)

  try{
    const response = await fetch("book.php", {
      method: "POST",
          body: formData
        })

        const result = await response.json()
        console.log(result)
  } catch (err) {
    console.error("Error submitting form:", err)
  }

}


</script>

<template>
  <h2>Book room {{room_number}}</h2>

    <form @submit.prevent="submitForm" >

        <div class="mb-3">
            <label for="start_time" class="form-label">Start Time</label>
            <input type="datetime-local" v-model="start_time" id="start_time" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="end_time" class="form-label">End Time</label>
            <input type="datetime-local" v-model="end_time" id="end_time" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success">Book Room</button>
    </form>
</template>

<style scoped>

</style>