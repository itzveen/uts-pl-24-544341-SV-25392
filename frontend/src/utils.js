export function titleCase(value) {
  return String(value ?? '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())
}

export function formatDate(iso) {
  if (!iso) return '-'
  const [year, month, day] = iso.split('-')
  return `${day}/${month}/${year}`
}