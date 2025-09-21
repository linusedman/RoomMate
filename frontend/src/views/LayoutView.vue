<script setup>
import Book from './components/Book.vue'

import { ref, onMounted } from 'vue'

const activePopover = ref(null)
const popoverPosition = ref({ x: 0, y: 0 })
const triggerRef = ref(null)

function togglePopover(id) {
  activePopover.value = activePopover.value === id ? null : id
  if (triggerRef.value) {
    const rect = triggerRef.value.getBoundingClientRect()
    popoverPosition.value = {
      x: rect.left + window.scrollX,
      y: rect.bottom + window.scrollY + 5, // 5px offset
    }
  }
}

onMounted(() => {
  document.addEventListener('click', (e) => {
    const triggers = document.querySelectorAll('.trigger')
    const popoversEls = document.querySelectorAll('.popover')
    if (
      ![...triggers].some(t => t.contains(e.target)) &&
      ![...popoversEls].some(p => p.contains(e.target))
    ) {
      activePopover.value = null
    }
  })
})
</script>



<template>
  <header>

  </header>

  <main>
    <div class="trigger"
         @click="togglePopover(1)"
         ref="triggerRef">
      Room 1
    </div>
    <div class="popover"
         v-show="activePopover === 1"
         :style="{ top: popoverPosition.y + 'px', left: popoverPosition.x + 'px' }">
       <Book roomnumber=1 />
    </div>
    <div class="trigger"
         @click="togglePopover(2)"
         ref="triggerRef">
      Room 2
    </div>
    <div class="popover"
         v-show="activePopover === 2"
         :style="{ top: popoverPosition.y + 'px', left: popoverPosition.x + 'px' }">
       <Book roomnumber=2 />
    </div>

  </main>
</template>

<style scoped>
.popover {
  position: absolute;
  top: 50px;
  left: 0;
  padding: 10px;
  background: #333;
  color: #fff;
  border-radius: 5px;
  z-index: 10
}

.trigger {
  display: inline-block;
  padding: 10px 20px;
  background: #007BFF;
  color: white;
  cursor: pointer;
  border-radius: 5px;
}
</style>