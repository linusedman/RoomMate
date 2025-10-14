<template>
  <div v-if="favorites.length > 0" class="favorite-rooms" :class="{ 'has-scroll': favorites.length > 2 }">
    <h4 class="title">My Favorites</h4>

    <div class="room-list">
      <div
        v-for="room in sortedFavorites"
        :key="room.room_id"
        :class="['room-card', { active: room.room_id === props.selectedFavoriteId }]"

        @click="selectRoom(room.room_id)"
        >
        {{ room.roomname }}
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const props = defineProps({
  selectedFavoriteId: Number,
  favorites:  Array
})

const emit = defineEmits(['selectFavorite'])

const sortedFavorites = computed(() => {
  return props.favorites.sort((a,b) => a.id - b.id)
})

function selectRoom(id) {
  console.log("FavoriteRooms → clicked room ID:", id)
  const newId = props.selectedFavoriteId === id ? null : id
  emit('selectFavorite', newId)
}
</script>

<style scoped>
.favorite-rooms {
  background: #f9fafb;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 16px;
}

.favorite-rooms.has-scroll {
  max-height: 400px;
  overflow-y: auto;
}

.title {
  font-weight: 600;
  margin-bottom: 10px;
}

.room-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.room-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 10px 12px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  cursor: pointer;
}

.room-card:hover {
  background-color: #e6f9fc;
}

.room-card.active {
  background-color: rgba(33, 214, 224, 0.1); 
  box-shadow: 0 0 0 2px rgba(33, 214, 224, 0.4);
}

</style>
