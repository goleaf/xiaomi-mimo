export interface FormattingPreferences {
    language?: string | null;
    timezone?: string | null;
    date_format?: string | null;
    time_format?: string | null;
}

const localeMap: Record<string, string> = {
    en: 'en-US',
    lt: 'lt-LT',
    ru: 'ru-RU',
};

const dateOnlyPattern = /^(\d{4})-(\d{2})-(\d{2})$/;

function validTimezone(timezone: string): string {
    try {
        new Intl.DateTimeFormat('en-US', { timeZone: timezone }).format();

        return timezone;
    } catch {
        return 'UTC';
    }
}

function dateParts(
    date: Date,
    locale: string,
    timezone: string,
): Record<'day' | 'month' | 'year', string> {
    const parts = new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: '2-digit',
        timeZone: timezone,
        year: 'numeric',
    }).formatToParts(date);

    return {
        day: parts.find((part) => part.type === 'day')?.value ?? '',
        month: parts.find((part) => part.type === 'month')?.value ?? '',
        year: parts.find((part) => part.type === 'year')?.value ?? '',
    };
}

function preferredDate(
    date: Date,
    locale: string,
    timezone: string,
    format: string,
): string {
    const { day, month, year } = dateParts(date, locale, timezone);

    switch (format) {
        case 'd/m/Y':
            return `${day}/${month}/${year}`;
        case 'm/d/Y':
            return `${month}/${day}/${year}`;
        case 'd.m.Y':
            return `${day}.${month}.${year}`;
        default:
            return `${year}-${month}-${day}`;
    }
}

function preferredTime(
    date: Date,
    locale: string,
    timezone: string,
    format: string,
): string {
    return new Intl.DateTimeFormat(locale, {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: timezone,
        ...(format === 'h:i A'
            ? { hour12: true }
            : { hourCycle: 'h23' as const }),
    }).format(date);
}

export function resolveIntlLocale(language?: string | null): string {
    return localeMap[language ?? 'en'] ?? 'en-US';
}

export function formatDateValue(
    value: Date | number | string,
    options: Intl.DateTimeFormatOptions,
    preferences: FormattingPreferences = {},
): string {
    const dateOnly =
        typeof value === 'string' ? value.match(dateOnlyPattern) : null;
    const date = dateOnly
        ? new Date(
              Date.UTC(
                  Number(dateOnly[1]),
                  Number(dateOnly[2]) - 1,
                  Number(dateOnly[3]),
                  12,
              ),
          )
        : value instanceof Date
          ? value
          : new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const locale = resolveIntlLocale(preferences.language);
    const timezone = dateOnly
        ? 'UTC'
        : validTimezone(preferences.timezone ?? 'UTC');

    try {
        if (options.dateStyle || options.timeStyle) {
            const sections: string[] = [];

            if (options.dateStyle) {
                sections.push(
                    preferredDate(
                        date,
                        locale,
                        timezone,
                        preferences.date_format ?? 'Y-m-d',
                    ),
                );
            }

            if (options.timeStyle && !dateOnly) {
                sections.push(
                    preferredTime(
                        date,
                        locale,
                        timezone,
                        preferences.time_format ?? 'H:i',
                    ),
                );
            }

            return sections.join(', ');
        }

        return new Intl.DateTimeFormat(locale, {
            ...options,
            timeZone: timezone,
        }).format(date);
    } catch {
        return new Intl.DateTimeFormat('en-US', {
            ...options,
            timeZone: 'UTC',
        }).format(date);
    }
}

export function formatNumberValue(
    value: number,
    options: Intl.NumberFormatOptions = {},
    preferences: FormattingPreferences = {},
): string {
    try {
        return new Intl.NumberFormat(
            resolveIntlLocale(preferences.language),
            options,
        ).format(value);
    } catch {
        return new Intl.NumberFormat('en-US', options).format(value);
    }
}

export function formatRelativeValue(
    value: Date | number | string,
    base: Date | number | string = new Date(),
    preferences: FormattingPreferences = {},
): string {
    const target = value instanceof Date ? value : new Date(value);
    const reference = base instanceof Date ? base : new Date(base);

    if (Number.isNaN(target.getTime()) || Number.isNaN(reference.getTime())) {
        return '';
    }

    const seconds = (target.getTime() - reference.getTime()) / 1000;
    const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
        ['year', 31_536_000],
        ['month', 2_592_000],
        ['week', 604_800],
        ['day', 86_400],
        ['hour', 3_600],
        ['minute', 60],
        ['second', 1],
    ];
    const [unit, divisor] = units.find(
        ([, candidate]) => Math.abs(seconds) >= candidate,
    ) ?? ['second', 1];

    try {
        return new Intl.RelativeTimeFormat(
            resolveIntlLocale(preferences.language),
            { numeric: 'auto' },
        ).format(Math.round(seconds / divisor), unit);
    } catch {
        return new Intl.RelativeTimeFormat('en-US', { numeric: 'auto' }).format(
            Math.round(seconds / divisor),
            unit,
        );
    }
}
