<template>
  <div class="filter-panel mb-3">
    <h4>Filter</h4>
    <div class="d-flex gap-2 mt-2 centered">
      <label>Day: </label>
      <input type="date" v-model="day" />
    </div>

    <div class="d-flex gap-2 mt-2 centered">
      <div class="d-flex gap-2 mt-2">
        <label>Start: </label>
        <input type="time" v-model="startTimeEdit" />
      </div>
      <div class="d-flex gap-2 mt-2" >
        <label>End: </label>
        <input type="time" v-model="endTimeEdit" />
      </div>
    </div>

    <p v-if="timeError" :class="['mt-2 fw-bold custom-error']">
        {{ timeError }}
      </p>

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

    <div class="d-flex gap-2 mt-2 centered">
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

const day = ref(new Date().toLocaleDateString('swe'))
const startTime = ref('08:00')
const endTime = ref('17:00')
const startTimeEdit = ref('08:00')
const endTimeEdit = ref('17:00')
const instrumentId = ref([])
const instrumentSearch = ref('')
const instrumentTypes = ref([])
const allInstrumentTypes = ref([])
const timeError = ref('')
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

watch([day, startTimeEdit, endTimeEdit], () => {
  applyFilter()
})

function checkTimeValidity(){
  const start = `${day.value}T${startTimeEdit.value}`
  const end = `${day.value}T${endTimeEdit.value}`

  const startMs = new Date(start)
  const endMs = new Date(end)

  const durationMinutes = (endMs - startMs) / 60000

  if (startMs >= endMs) {
    startTimeEdit.value = startTime.value
    endTimeEdit.value = endTime.value
    timeError.value = "Start time must be before end time"
    setTimeout(() => {
    timeError.value = ''
    }, 5000)
  }

  else if (durationMinutes < 60) {
    startTimeEdit.value = startTime.value
    endTimeEdit.value = endTime.value
    timeError.value = "Must filter an interval of at least an hour"
    setTimeout(() => {
    timeError.value = ''
    }, 5000)
  }

  else {
    startTime.value = startTimeEdit.value
    endTime.value = endTimeEdit.value
  }
}

function applyFilter() {
  if (!day.value) return

  checkTimeValidity()

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
  day.value = new Date().toLocaleDateString('swe')
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

.custom-error {
  color: #dc3545;
}

.btn-primary {
  background-color: #0087e6;
  border-color: #0087e6;
}

.btn-primary:hover {
  background-color: #0029aa;
  border-color: #0029aa;
}

.centered {
  justify-content: center;
}
</style>


