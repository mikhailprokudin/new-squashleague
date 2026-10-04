<script setup lang="ts">
import { computed, ref, watch } from 'vue'

export interface ComboboxOption {
  id: number
  label: string
}

const props = withDefaults(
  defineProps<{
    modelValue: number | null
    options: ComboboxOption[]
    placeholder?: string
    disabled?: boolean
  }>(),
  {
    placeholder: 'Начните вводить имя…',
    disabled: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
}>()

const query = ref('')
const open = ref(false)
const root = ref<HTMLElement | null>(null)

const selected = computed(() => props.options.find((o) => o.id === props.modelValue) ?? null)

watch(
  () => props.modelValue,
  (id) => {
    if (id === null) {
      query.value = ''
      return
    }
    const option = props.options.find((o) => o.id === id)
    if (option) {
      query.value = option.label
    }
  },
  { immediate: true },
)

watch(
  () => props.options,
  () => {
    if (props.modelValue !== null && !props.options.some((o) => o.id === props.modelValue)) {
      emit('update:modelValue', null)
      query.value = ''
    } else if (selected.value) {
      query.value = selected.value.label
    }
  },
)

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) {
    return props.options
  }
  return props.options.filter((o) => o.label.toLowerCase().includes(q))
})

function onFocus() {
  if (props.disabled) {
    return
  }
  open.value = true
}

function onInput() {
  open.value = true
  if (selected.value && query.value !== selected.value.label) {
    emit('update:modelValue', null)
  }
}

function pick(option: ComboboxOption) {
  emit('update:modelValue', option.id)
  query.value = option.label
  open.value = false
}

function onBlur(event: FocusEvent) {
  const related = event.relatedTarget as Node | null
  if (root.value?.contains(related)) {
    return
  }
  open.value = false
  if (selected.value) {
    query.value = selected.value.label
  } else if (props.modelValue === null) {
    // keep typed query for continued search, or clear if nothing selected
  }
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    open.value = false
  }
}
</script>

<template>
  <div ref="root" class="relative w-full" @keydown="onKeydown">
    <label class="input w-full">
      <input
        v-model="query"
        type="text"
        class="grow"
        :placeholder="placeholder"
        :disabled="disabled"
        autocomplete="off"
        @focus="onFocus"
        @input="onInput"
        @blur="onBlur"
      />
    </label>

    <ul
      v-if="open && !disabled"
      class="menu bg-base-100 text-base-content rounded-box border-base-300 absolute z-50 mt-1 max-h-60 w-full overflow-auto border p-1 shadow-lg"
      tabindex="-1"
    >
      <li v-if="filtered.length === 0" class="disabled">
        <span class="text-base-content/60">Ничего не найдено</span>
      </li>
      <li v-for="option in filtered" :key="option.id">
        <button type="button" class="text-left" @mousedown.prevent="pick(option)">
          {{ option.label }}
        </button>
      </li>
    </ul>
  </div>
</template>
