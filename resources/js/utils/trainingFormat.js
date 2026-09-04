export function shortDay(label) {
  return String(label || '').slice(0, 3)
}

export function hasMuscleIcon(name) {
  return Boolean(name)
    && !['Sin grupo', 'Otro'].includes(name)
}

export function formatLoad(item) {
  if (!item)
    return ''
  if (item.load_type === 'km')
    return item.load_value != null ? `${item.load_value} km` : 'Sin km'
  if (item.load_type === 'bodyweight')
    return 'Peso corporal'
  if (item.load_type === 'level')
    return item.load_value != null ? `Niv ${item.load_value}` : 'Sin nivel'
  if (item.load_value == null)
    return 'Sin peso'

  return `${item.load_value} kg`
}

export function formatDuration(totalMinutes) {
  if (totalMinutes == null || totalMinutes === '')
    return ''

  const total = Math.max(0, Number(totalMinutes) || 0)
  const hours = Math.floor(total / 60)
  const mins = total % 60

  if (hours && mins)
    return `${hours} h ${mins} min`
  if (hours)
    return `${hours} h`

  return `${mins} min`
}

export function formatSessionDate(value) {
  if (!value)
    return ''

  const date = new Date(`${value}T00:00:00`)

  return date.toLocaleDateString('es-CR', {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
  })
}

export function muscleSummary(session) {
  const groups = [...new Set((session.exercises || []).map(item => item.muscle_group).filter(Boolean))]

  return groups.length ? groups.join(' + ') : `${(session.exercises || []).length} ejercicios`
}

export function groupExercises(exercises) {
  const groups = []
  const map = {}

  ;(exercises || []).forEach(item => {
    const name = item.muscle_group || 'Sin grupo'
    if (!map[name]) {
      map[name] = { name, items: [] }
      groups.push(map[name])
    }
    map[name].items.push(item)
  })

  return groups
}

export function emptyExercise() {
  return {
    id: null,
    library_exercise_id: null,
    name: '',
    muscle_group: null,
    sets: 4,
    reps: 11,
    load_type: 'level',
    load_value: null,
    notes: '',
  }
}

export function todayIso() {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')

  return `${now.getFullYear()}-${month}-${day}`
}

export function emptySession() {
  return {
    id: null,
    workout_day_id: null,
    date: todayIso(),
    duration_hours: null,
    duration_mins: null,
    calories: null,
    notes: '',
    exercises: [],
  }
}

export function splitDuration(totalMinutes) {
  if (totalMinutes == null || totalMinutes === '')
    return { hours: null, mins: null }

  const total = Math.max(0, Number(totalMinutes) || 0)

  return {
    hours: Math.floor(total / 60) || null,
    mins: (total % 60) || null,
  }
}

export function joinDuration(hours, mins) {
  const h = Number(hours) || 0
  const m = Number(mins) || 0
  const total = (h * 60) + m

  return total > 0 ? total : null
}

export function focusFromGroups(groups) {
  return (groups || [])
    .map(group => group.name)
    .filter(name => name && name !== 'Sin grupo')
    .join(' + ') || null
}

export function groupsFromDay(day) {
  const map = {}
  const groups = []

  ;(day?.exercises || []).forEach(item => {
    const name = item.muscle_group || 'Sin grupo'
    const key = name

    if (!map[key]) {
      map[key] = {
        key,
        name,
        source_day_id: day.id,
        muscle_group: item.muscle_group || null,
        count: 0,
      }
      groups.push(map[key])
    }

    map[key].count += 1
  })

  return groups
}

export const MUSCLE_OPTIONS = [
  'Pecho', 'Hombros', 'Tríceps', 'Espalda', 'Bíceps', 'Antebrazo',
  'Piernas', 'Abdomen', 'Cardio',
]
