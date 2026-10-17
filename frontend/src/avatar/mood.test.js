import { describe, expect, it } from 'vitest'
import { pickMood } from './mood.js'

const at = (hour) => new Date(2026, 9, 17, hour, 30)

describe('pickMood', () => {
  it('ist tagsüber neugierig und nachts müde', () => {
    expect(pickMood({ now: at(10) })).toBe('curious')
    expect(pickMood({ now: at(21) })).toBe('curious')
    expect(pickMood({ now: at(22) })).toBe('sleepy')
    expect(pickMood({ now: at(4) })).toBe('sleepy')
    expect(pickMood({ now: at(5) })).toBe('curious')
  })

  it('wählt die oberste Stimmung, die zutrifft', () => {
    expect(pickMood({ now: at(10), learnedToday: true })).toBe('satisfied')
    expect(pickMood({ now: at(23), learnedToday: true })).toBe('sleepy')
    expect(pickMood({ now: at(23), daysAway: 3 })).toBe('happy')
    expect(pickMood({ now: at(10), daysAway: 2 })).toBe('curious')
    expect(pickMood({ now: at(23), daysAway: 5, unseenMilestone: true })).toBe('proud')
  })
})
