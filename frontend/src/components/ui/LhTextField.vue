<script setup>
// Eingabefeld mit sichtbarem Label. Weitere Attribute (autocomplete, placeholder, name …) gehen ans <input>.
// error: Text, der sagt, wie es richtig geht. Erst beim Verlassen des Felds oder beim Absenden setzen.
import { useId } from 'vue'
import LhIcon from './LhIcon.vue'

defineOptions({ inheritAttrs: false })

const model = defineModel({ type: String, default: '' })
defineProps({
  label: { type: String, required: true },
  hint: String,
  error: String,
  type: { type: String, default: 'text' },
  disabled: Boolean,
})

const hintId = useId()
</script>

<template>
  <label class="lh-field" :class="{ 'lh-field--error': error }">
    <span class="lh-field__label">{{ label }}</span>
    <input
      v-model="model"
      v-bind="$attrs"
      class="lh-field__input"
      :type="type"
      :disabled="disabled"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="error || hint ? hintId : undefined"
    />
    <span v-if="error || hint" :id="hintId" class="lh-field__hint">
      <LhIcon v-if="error" name="ui-wrong" />{{ error || hint }}
    </span>
  </label>
</template>
