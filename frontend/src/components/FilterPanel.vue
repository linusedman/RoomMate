<template>
  <div class="filter-panel mb-3">
    <h4>Filter</h4>
    <label>Day</label>
    <input type="date" v-model="day" />

    <div class="d-flex gap-2 mt-2">
      <div>
        <label>Start</label>
        <input type="time" v-model="startTime" />
      </div>
      <div>
        <label>End</label>
        <input type="time" v-model="endTime" />
      </div>
    </div>

        <div class="mt-3 position-relative">
          <div class="dropdown-wrapper">
            <input
              type="text"
              v-model="instrumentSearch"
              @input="searchInstruments"
              @focus="dropdownOpen = true"
              placeholder="Search for instruments..."
              class="form-control mb-2"
            />

            <ul v-if="dropdownOpen" class="dropdown-list">
              <li
                v-for="type in instrumentTypes"
                :key="type.id"
                @click="selectInstrument(type)"
                class="dropdown-item"
              >
                {{ type.typename }}
              </li>
              <li v-if="instrumentTypes.length === 0" class="dropdown-item disabled">
                No instruments matched your search.
              </li>
            </ul>
          </div>
        </div>

    <div class="d-flex gap-2 mt-2">
      <button class="btn btn-primary" @click="applyFilter">Apply</button>
      <button class="btn btn-outline-secondary" @click="clearFilter">Clear</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
defineExpose({ resetInstrument })

const props = defineProps({
  initialStart: { type: String, default: '' },
  initialEnd: { type: String, default: '' }
})
const emit = defineEmits(['filter'])

const day = ref(new Date().toISOString().substr(0, 10))
const startTime = ref('08:00')
const endTime = ref('17:00')
const instrumentId = ref('')
const instrumentSearch = ref('')
const instrumentTypes = ref([])
const allInstrumentTypes = ref([])
const selectedInstrumentName = ref('')
const dropdownOpen = ref(false)

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value
}

function selectInstrument(type) {
  instrumentId.value = type.id
  instrumentSearch.value = type.typename
  dropdownOpen.value = false
}

async function fetchInstruments(search = '') {
  try {
    const url = search
      ? `http://localhost/RoomMate/backend/pages/get_instrument_types.php?search=${encodeURIComponent(search)}`
      : `http://localhost/RoomMate/backend/pages/get_instrument_types.php`
    const res = await fetch(url)
    const data = await res.json()
    instrumentTypes.value = data.length > 0 ? data : []
  } catch (e) {
    console.error('Instrument fetch failed', e)
    instrumentTypes.value = []
  }
}

function searchInstruments() {
  if (instrumentSearch.value.trim() === '') {
    instrumentTypes.value = [...allInstrumentTypes.value]
  } else {
    fetchInstruments(instrumentSearch.value)
  }
}

onMounted(async () => {
  await fetchInstruments()
  allInstrumentTypes.value = [...instrumentTypes.value]

  window.addEventListener('clearInstrument', () => {
    instrumentId.value = ''
    instrumentSearch.value = ''
  })
})

function resetInstrument() {
  instrumentId.value = ''
  instrumentSearch.value = ''
  instrumentTypes.value = [...allInstrumentTypes.value]
}

function handleClickOutside(event) {
  const wrapper = document.querySelector('.dropdown-wrapper')
  if (wrapper && !wrapper.contains(event.target)) {
    dropdownOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  applyFilter()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

function applyFilter() {
  if (!day.value) return
  const start = `${day.value}T${startTime.value}`
  const end = `${day.value}T${endTime.value}`
  emit('filter', { day: day.value, start, end, instrumentId: instrumentId.value })
}


function clearFilter() {
  day.value = ''
  startTime.value = '08:00'
  endTime.value = '17:00'
  instrumentId.value = ''
  instrumentSearch.value = ''
  instrumentTypes.value = [...allInstrumentTypes.value]
  emit('filter', { day: '', start: '', end: '', instrumentId: '' })
}
</script>

<style scoped>
.dropdown-wrapper {
  position: relative;
}

.dropdown-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 160px;
  overflow-y: auto;
  background: white;
  border: 1px solid #ccc;
  z-index: 10;
  list-style: none;
  padding: 0;
  margin: 0;
}

.dropdown-item {
  padding: 8px 12px;
  cursor: pointer;
}

.dropdown-item:hover {
  background-color: #f0f0f0;
}

.dropdown-item.disabled {
  color: #999;
  cursor: default;
}
</style>
