// Gesichter auf dem LED-Display: 9 × 6 Punkte, # leuchtet. Zeile 0–2 sind die Augen (blinzeln), 3–5 der Mund.
// Nie traurig, besorgt oder kaputt (E-033).
import { SCREEN } from './skeleton.js'

export const FACES = {
  curious: ['.........', '.##...##.', '.##...##.', '.........', '..#...#..', '...###...'],
  satisfied: ['.........', '..#...#..', '.#.#.#.#.', '.........', '..#...#..', '...###...'],
  proud: ['..#...#..', '.###.###.', '..#...#..', '.........', '.#.....#.', '..#####..'],
  happy: ['.##...##.', '.##...##.', '.##...##.', '.........', '.#.....#.', '..#####..'],
  sleepy: ['.........', '.........', '.##...##.', '.........', '....#....', '.........'],
  think: ['..##...##', '..##...##', '.........', '.........', '...#.#.#.', '.........'],
}

const COLS = 9
const ROWS = 6

/** Punkte eines Gesichts mit Lage auf dem Display, getrennt in Augen und Mund */
export function faceDots(rows) {
  const dx = SCREEN.width / COLS
  const dy = SCREEN.height / ROWS
  const dots = rows.flatMap((row, r) =>
    [...row].map((cell, c) => ({ r, x: SCREEN.x + dx * (c + 0.5), y: SCREEN.y + dy * (r + 0.5), on: cell === '#' })),
  )
  return { eyes: dots.filter((d) => d.r < 3), mouth: dots.filter((d) => d.r >= 3) }
}
