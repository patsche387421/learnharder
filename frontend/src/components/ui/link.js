// Gemeinsam für Bausteine, die als Router-Link (to) oder externer Link (href) gerendert werden können.
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

export const linkProps = {
  to: [String, Object],
  href: String,
}

// Liefert { tag, attrs } für <component :is="tag" v-bind="attrs">, oder null, wenn es kein Link ist.
export function useLink(props) {
  return computed(() => {
    if (props.to) return { tag: RouterLink, attrs: { to: props.to } }
    if (props.href) return { tag: 'a', attrs: { href: props.href } }
    return null
  })
}
