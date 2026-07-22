import assert from 'node:assert/strict';
import test from 'node:test';
import {
    formatDateValue,
    formatNumberValue,
    formatRelativeValue,
} from './formatters.ts';

test('date-only values never shift across user timezones', () => {
    for (const timezone of ['Pacific/Kiritimati', 'America/Adak']) {
        assert.equal(
            formatDateValue(
                '2026-01-02',
                { dateStyle: 'medium' },
                {
                    language: 'en',
                    timezone,
                    date_format: 'Y-m-d',
                },
            ),
            '2026-01-02',
        );
    }
});

test('saved date and time formats are applied in the user timezone', () => {
    assert.equal(
        formatDateValue(
            '2026-07-22T20:05:00Z',
            { dateStyle: 'medium', timeStyle: 'short' },
            {
                language: 'lt',
                timezone: 'Europe/Vilnius',
                date_format: 'd.m.Y',
                time_format: 'H:i',
            },
        ),
        '22.07.2026, 23:05',
    );
});

test('number and relative formatting follow the selected language', () => {
    assert.equal(formatNumberValue(1234.5, {}, { language: 'lt' }), '1 234,5');
    assert.match(
        formatRelativeValue('2026-07-21T12:00:00Z', '2026-07-22T12:00:00Z', {
            language: 'ru',
        }),
        /вчера|день назад/,
    );
});
