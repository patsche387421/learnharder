<script setup>
// Häkchen für eine Zustimmung oder Bestätigung. Der Text kommt als Slot, damit Links darin stehen können.
// error: sagt, warum das Häkchen nötig ist. Erst beim Absenden setzen. Bei Einwilligungen nie vorab anhaken.
import { useId } from 'vue'
import LhIcon from './LhIcon.vue'

defineOptions({ inheritAttrs: false })

const model = defineModel({ type: Boolean, default: false })
defineProps({
  error: String,
  disabled: Boolean,
})

const hintId = useId()
</script>

<template>
  <div class="lh-check" :class="{ 'lh-check--error': error }">
    <label class="lh-check__row">
      <input
        v-model="model"
        v-bind="$attrs"
        class="lh-check__input"
        type="checkbox"
        :disabled="disabled"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="error ? hintId : undefined"
      />
      <span class="lh-check__label"><slot /></span>
    </label>
    <span v-if="error" :id="hintId" class="lh-check__hint"><LhIcon name="ui-wrong" />{{ error }}</span>
  </div>
</template>
