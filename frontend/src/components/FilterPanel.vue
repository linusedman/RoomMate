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

    <div class="mt-3">
  <label>Instrument Types</label>
  <multiselect
      ref="multiSelectRef"
    v-model="instrumentId"
    :options="instrumentTypes"
    :multiple="true"
    :close-on-select="false"
    :clear-on-select="false"
    :preserve-search="true"
    placeholder="Select Instrument Types"
    label="typename"
    track-by="id"
    :preselect-first="false"
    :searchable="true"
    @search-change="searchInstruments"
  >
    <template #noResult>
      <span>No instruments found.</span>
    </template>
  </multiselect>
  
  <!-- Display selected instruments count -->
  <small v-if="instrumentId.length > 0" class="text-muted mt-1 d-block">
    {{ instrumentId.length }} instrument(s) selected
  </small>
</div>

    <div class="d-flex gap-2 mt-2">
      <button class="btn btn-primary" @click="applyFilter">Apply</button>
      <button class="btn btn-outline-secondary" @click="clearFilter">Clear</button>
    </div>
  </div>
</template>

<script setup>

import Multiselect from 'vue-multiselect'
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
const instrumentId = ref([])
const instrumentSearch = ref('')
const instrumentTypes = ref([])
const allInstrumentTypes = ref([])
const selectedInstrumentName = ref('')
const dropdownOpen = ref(false)

const multiSelectRef = ref(null)

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

function searchInstruments(query) {
  if (query.trim() === '') {
    instrumentTypes.value = [...allInstrumentTypes.value]
  } else {
    fetchInstruments(query)
  }
}

onMounted(async () => {
  await fetchInstruments()
  allInstrumentTypes.value = [...instrumentTypes.value]

  window.addEventListener('clearInstrument', () => {
    instrumentId.value = []
    instrumentSearch.value = ''
  })
})

function resetInstrument() {
  instrumentId.value = []
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

  const selectedIds = instrumentId.value
    .map(instrument => parseInt(instrument.id))
    .filter(id => !isNaN(id) && id > 0)

  emit('filter', { 
    day: day.value, 
    start, 
    end, 
    instrumentId: selectedIds  
  })
}

function clearFilter() {
  day.value = new Date().toISOString().substr(0, 10)
  startTime.value = '08:00'
  endTime.value = '17:00'
  instrumentId.value = []
  multiSelectRef.value.search = ''
  instrumentTypes.value = [...allInstrumentTypes.value]
  applyFilter()
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>

<style scoped>
::v-deep(.multiselect__input) {
  color: black !important;
}
</style>


