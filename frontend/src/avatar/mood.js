// Stimmung des Roboters (Konzept Spielwelt §7, E-033): nie negativ und nur für die Person selbst.
// Die App wählt sie aus Fakten des Servers und der Gerätezeit. Was der Server noch nicht liefert, fehlt einfach.
export function pickMood({ now = new Date(), unseenMilestone = false, daysAway = 0, learnedToday = false } = {}) {
  if (unseenMilestone) return 'proud'
  if (daysAway >= 3) return 'happy'
  const hour = now.getHours()
  if (hour >= 22 || hour < 5) return 'sleepy'
  if (learnedToday) return 'satisfied'
  return 'curious'
}
