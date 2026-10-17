<script setup>
// Avatar-Roboter (Konzept Spielwelt §7, E-031): ein Skelett, die Teile als SVG-Ebenen aus src/assets/avatar/.
// Display (Stimmung), Akku und Bewegung zeichnet die Komponente selbst. Animiert werden nur transform und opacity.
// Außerhalb des Bildes pausiert alles, bei prefers-reduced-motion steht der Roboter still.
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { BASIC_KIT, partSvg } from './catalog.js'
import { FACES, faceDots } from './faces.js'
import { PAINTS } from './paints.js'
import { POSES } from './poses.js'
import { BATTERY, SCREEN, SOCKETS, VIEWBOX } from './skeleton.js'

const props = defineProps({
  kit: { type: Object, default: () => BASIC_KIT },
  pose: { type: String, default: 'relaxed' },
  mood: { type: String, default: 'curious' },
  // Einmalige Bewegung beim Erscheinen: wave, celebrate oder think
  action: { type: String, default: null },
  // Brainpower als Anteil 0–1. Ohne Wert kein Akku, z. B. wenn andere den Roboter sehen (E-034)
  battery: { type: Number, default: null },
  label: { type: String, default: 'Roboter' },
})

const part = (slot) => (props.kit[slot] ? partSvg(slot, props.kit[slot]) : '')

const paintStyle = computed(() => {
  const paint = PAINTS[props.kit.paint] ?? PAINTS[BASIC_KIT.paint]
  return {
    '--paint-main': paint.main,
    '--paint-trim': paint.trim,
    '--paint-accent': paint.accent,
    '--paint-ink': paint.ink,
    '--paint-dot': paint.dot,
  }
})

const pose = computed(() => POSES[props.pose] ?? POSES.relaxed)
const face = computed(() => faceDots(FACES[props.action === 'think' ? 'think' : props.mood] ?? FACES.curious))
const charge = computed(() => (props.battery === null ? null : Math.min(1, Math.max(0, props.battery))))

const pivot = ([x, y]) => `${x}px ${y}px`
const arm = (socket, deg) => ({ transformOrigin: pivot(socket), '--pose': `${deg}deg` })

const root = ref(null)
const paused = ref(false)
let observer = null

onMounted(() => {
  if (typeof IntersectionObserver === 'undefined') return
  observer = new IntersectionObserver(([entry]) => {
    paused.value = !entry.isIntersecting
  })
  observer.observe(root.value)
})
onBeforeUnmount(() => observer?.disconnect())
</script>

<template>
  <svg
    ref="root"
    class="robot"
    :class="[`robot--${mood}`, action && `robot--${action}`, { 'robot--paused': paused }]"
    :viewBox="VIEWBOX"
    role="img"
    :aria-label="label"
    :style="paintStyle"
  >
    <g class="robot__legs" v-html="part('locomotion')" />
    <g class="robot__body">
      <g class="robot__arm robot__arm--left" :style="arm(SOCKETS.shoulderLeft, pose.left)" v-html="part('arms')" />
      <g class="robot__arm robot__arm--right" :style="arm(SOCKETS.shoulderRight, pose.right)">
        <!-- Arme sind einmal gezeichnet (links) und werden für rechts gespiegelt. Das Werkzeug dreht mit -->
        <g transform="translate(200 0) scale(-1 1)" v-html="part('arms')" />
        <g class="robot__tool" v-html="part('tool')" />
      </g>
      <g v-html="part('chassis')" />
      <g v-html="part('badge')" />
      <g v-if="charge !== null" class="robot__battery">
        <rect class="robot__cell" v-bind="BATTERY" rx="3" />
        <rect class="robot__nub" :x="BATTERY.x + BATTERY.width" :y="BATTERY.y + 3" width="3" height="6" />
        <rect
          class="robot__charge"
          :x="BATTERY.x + 2"
          :y="BATTERY.y + 2"
          :width="(BATTERY.width - 4) * charge"
          :height="BATTERY.height - 4"
          rx="1"
        />
        <!-- Wenig Brainpower: ein Blitz zeigt, dass er lädt. Keine rote Warnung -->
        <path v-if="charge < 0.2" class="robot__bolt" d="M102 157 L96 163 H100 L98 167 L104 161 H100 Z" />
      </g>
      <g class="robot__head" :style="{ transformOrigin: pivot(SOCKETS.neck) }">
        <g class="robot__topper" :style="{ transformOrigin: pivot(SOCKETS.top) }" v-html="part('topper')" />
        <g v-html="part('head')" />
        <rect class="robot__screen" v-bind="SCREEN" />
        <g class="robot__glance">
          <g class="robot__eyes">
            <circle
              v-for="(dot, i) in face.eyes"
              :key="i"
              class="robot__dot"
              :class="{ 'robot__dot--on': dot.on }"
              :cx="dot.x"
              :cy="dot.y"
              r="3.2"
            />
          </g>
        </g>
        <g class="robot__mouth">
          <circle
            v-for="(dot, i) in face.mouth"
            :key="i"
            class="robot__dot"
            :class="{ 'robot__dot--on': dot.on }"
            :cx="dot.x"
            :cy="dot.y"
            r="3.2"
          />
        </g>
      </g>
    </g>
  </svg>
</template>

<style scoped>
.robot { display: block; width: 100%; height: 100%; overflow: visible; }
.robot :deep(.p-main) { fill: var(--paint-main); }
.robot :deep(.p-trim) { fill: var(--paint-trim); }
.robot :deep(.p-accent) { fill: var(--paint-accent); }
.robot :deep(.p-ink) { fill: var(--paint-ink); }
.robot :deep(:is(.p-main, .p-trim, .p-accent)) { stroke: var(--paint-ink); stroke-width: 3; stroke-linejoin: round; }
.robot :deep(.p-line) { fill: none; stroke: var(--paint-ink); stroke-width: 3; stroke-linecap: round; }
.robot__screen, .robot__cell, .robot__nub { fill: var(--paint-ink); }
.robot__charge { fill: var(--paint-accent); }
.robot__bolt { fill: var(--paint-dot); }
.robot__dot { fill: var(--paint-dot); opacity: 0.14; }
.robot__dot--on { opacity: 1; }
.robot__arm, .robot__head, .robot__topper { transform-box: view-box; }
.robot__arm { transform: rotate(var(--pose)); }
.robot__eyes { transform-box: fill-box; transform-origin: center; }
.robot--think .robot__head { transform: rotate(-6deg); }

@media (prefers-reduced-motion: no-preference) {
  .robot__arm, .robot__head { transition: transform 0.3s ease-out; }
  .robot__dot { transition: opacity 0.2s; }
  .robot__body { animation: robot-breathe 3.2s ease-in-out infinite; }
  .robot__topper { animation: robot-wiggle 2.6s ease-in-out infinite; }
  .robot__eyes { animation: robot-blink 5s infinite; }
  .robot--curious .robot__glance { animation: robot-glance 7s ease-in-out infinite; }
  .robot--sleepy .robot__body { animation-duration: 5s; }
  .robot--wave .robot__arm--right { animation: robot-wave 0.9s ease-in-out; }
  .robot--celebrate .robot__body { animation: robot-jump 0.8s ease-out, robot-breathe 3.2s ease-in-out 0.8s infinite; }
  .robot--celebrate .robot__arm--left { animation: robot-cheer-left 0.8s ease-out; }
  .robot--celebrate .robot__arm--right { animation: robot-cheer-right 0.8s ease-out; }
  .robot--paused, .robot--paused * { animation-play-state: paused; }
}

@keyframes robot-breathe { 50% { transform: translateY(1.5px); } }
@keyframes robot-wiggle { 50% { transform: rotate(5deg); } }
@keyframes robot-blink { 0%, 94%, 100% { transform: scaleY(1); } 97% { transform: scaleY(0.1); } }
@keyframes robot-glance { 0%, 70%, 100% { transform: translateX(0); } 76%, 92% { transform: translateX(4px); } }
@keyframes robot-wave {
  25%, 75% { transform: rotate(-140deg); }
  50% { transform: rotate(-110deg); }
}
@keyframes robot-jump { 40% { transform: translateY(-10px); } }
@keyframes robot-cheer-left { 30%, 70% { transform: rotate(140deg); } }
@keyframes robot-cheer-right { 30%, 70% { transform: rotate(-140deg); } }
</style>
