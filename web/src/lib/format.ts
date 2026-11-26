function parseDate(value: string): Date {
  const [year = 1970, month = 1, day = 1] = value.split('-').map(Number)

  return new Date(year, month - 1, day)
}

export function formatDates(startsOn: string, endsOn: string | null, locale: string): string {
  const format = new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', year: 'numeric' })

  const start = format.format(parseDate(startsOn))

  return endsOn && endsOn !== startsOn ? `${start} – ${format.format(parseDate(endsOn))}` : start
}

export function formatDistance(kilometres: number, locale: string): string {
  return new Intl.NumberFormat(locale, { style: 'unit', unit: 'kilometer', maximumFractionDigits: 0 }).format(
    kilometres,
  )
}

export function countryName(code: string, locale: string): string {
  return new Intl.DisplayNames([locale], { type: 'region' }).of(code) ?? code
}
