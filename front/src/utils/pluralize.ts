/**
 * Russian plural forms: one / few / many
 * e.g. очко / очка / очков
 */
export function pluralizeRu(n: number, one: string, few: string, many: string): string {
  const abs = Math.abs(n)
  if (!Number.isInteger(abs)) {
    return many
  }

  const mod10 = abs % 10
  const mod100 = abs % 100

  if (mod10 === 1 && mod100 !== 11) {
    return one
  }
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
    return few
  }
  return many
}

export function formatPointsLabel(value: number): string {
  const formatted = Number.isInteger(value) ? String(value) : value.toFixed(1)
  const word = pluralizeRu(value, 'очко', 'очка', 'очков')
  return `${formatted} ${word}`
}
