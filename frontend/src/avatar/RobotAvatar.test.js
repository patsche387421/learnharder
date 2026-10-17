import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { BASIC_KIT } from './catalog.js'
import { FACES } from './faces.js'
import RobotAvatar from './RobotAvatar.vue'

const lit = (rows) => rows.join('').split('#').length - 1

describe('RobotAvatar', () => {
  it('setzt die Grundausstattung aus den Teilen zusammen', () => {
    const wrapper = mount(RobotAvatar, { props: { label: 'Dein Roboter' } })

    expect(wrapper.attributes('role')).toBe('img')
    expect(wrapper.attributes('aria-label')).toBe('Dein Roboter')
    expect(wrapper.find('.robot__legs rect').exists()).toBe(true)
    expect(wrapper.find('.robot__topper polygon').exists()).toBe(true)
    expect(wrapper.findAll('.robot__arm--right g')[0].attributes('transform')).toBe('translate(200 0) scale(-1 1)')
    expect(wrapper.find('.robot__tool').element.children).toHaveLength(0)
    expect(wrapper.element.style.getPropertyValue('--paint-main')).toBe('#F6F3EE')
  })

  it('nimmt Lackierung, Werkzeug und Pose', () => {
    const wrapper = mount(RobotAvatar, {
      props: { kit: { ...BASIC_KIT, paint: 'graphite', tool: 'soldering-iron-1' }, pose: 'cheer' },
    })

    expect(wrapper.element.style.getPropertyValue('--paint-main')).toBe('#5F5A52')
    expect(wrapper.find('.robot__tool rect').exists()).toBe(true)
    expect(wrapper.find('.robot__arm--left').element.style.getPropertyValue('--pose')).toBe('140deg')
    expect(wrapper.find('.robot__arm--right').element.style.getPropertyValue('--pose')).toBe('-140deg')
  })

  it('zeigt die Stimmung auf dem Display, think geht vor', async () => {
    const wrapper = mount(RobotAvatar, { props: { mood: 'sleepy' } })
    expect(wrapper.classes()).toContain('robot--sleepy')
    expect(wrapper.findAll('.robot__dot')).toHaveLength(54)
    expect(wrapper.findAll('.robot__dot--on')).toHaveLength(lit(FACES.sleepy))

    await wrapper.setProps({ action: 'think' })
    expect(wrapper.classes()).toContain('robot--think')
    expect(wrapper.findAll('.robot__dot--on')).toHaveLength(lit(FACES.think))
  })

  it('zeigt den Akku nur mit Wert, bei wenig Ladung mit Blitz', async () => {
    const wrapper = mount(RobotAvatar)
    expect(wrapper.find('.robot__battery').exists()).toBe(false)

    await wrapper.setProps({ battery: 0.5 })
    expect(wrapper.find('.robot__charge').attributes('width')).toBe('14')
    expect(wrapper.find('.robot__bolt').exists()).toBe(false)

    await wrapper.setProps({ battery: 0.1 })
    expect(wrapper.find('.robot__bolt').exists()).toBe(true)
  })
})
