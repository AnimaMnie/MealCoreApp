// Polskie nazwy dla kodów z API (meal_type, typ grupy, kategorie).
export const MEAL_TYPES = [
  { value: 'breakfast', label: 'Śniadanie', icon: 'fa-mug-hot' },
  { value: 'second_breakfast', label: 'Drugie śniadanie', icon: 'fa-apple-whole' },
  { value: 'lunch', label: 'Obiad', icon: 'fa-bowl-food' },
  { value: 'dinner', label: 'Kolacja', icon: 'fa-drumstick-bite' },
  { value: 'snack', label: 'Przekąska', icon: 'fa-cookie' },
  { value: 'supper', label: 'Późna kolacja', icon: 'fa-moon' },
]

export const GROUP_TYPES = {
  family: { label: 'Rodzina', icon: 'fa-house' },
  roommates: { label: 'Współlokatorzy', icon: 'fa-couch' },
  office: { label: 'Biuro', icon: 'fa-briefcase' },
  other: { label: 'Inna', icon: 'fa-users' },
}

export const CATEGORIES = ['Warzywa i owoce', 'Nabiał', 'Pieczywo', 'Białko', 'Inne']

// Ikony Font Awesome dla kategorii (emoji nie wszędzie się wyświetlają).
export const CATEGORY_ICONS = {
  'Warzywa i owoce': 'fa-carrot',
  Nabiał: 'fa-cheese',
  Pieczywo: 'fa-bread-slice',
  Białko: 'fa-drumstick-bite',
  Inne: 'fa-bowl-food',
}
export const categoryIcon = (category) => CATEGORY_ICONS[category] || 'fa-bowl-food'

export const today = () => new Date().toLocaleDateString('sv-SE') // YYYY-MM-DD w strefie lokalnej

export const shiftDate = (date, days) => {
  const d = new Date(`${date}T12:00:00`)
  d.setDate(d.getDate() + days)
  return d.toLocaleDateString('sv-SE')
}

export const formatDate = (date, options = { weekday: 'long', day: 'numeric', month: 'long' }) =>
  new Date(`${date}T12:00:00`).toLocaleDateString('pl-PL', options)

export const fmt = (n) => Math.round(Number(n) || 0).toLocaleString('pl-PL')
