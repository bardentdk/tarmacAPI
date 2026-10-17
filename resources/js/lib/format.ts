const locale = 'fr-FR';

/**
 * Format a number the French way (6,24 · 1 013).
 */
export function formatNumber(
    value: number | null | undefined,
    decimals = 0,
): string {
    if (value === null || value === undefined || Number.isNaN(value)) {
        return '—';
    }

    return new Intl.NumberFormat(locale, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(value);
}

/**
 * A time in seconds, e.g. "6,24 s".
 */
export function formatSeconds(value: number | null | undefined): string {
    return value === null || value === undefined
        ? '—'
        : `${formatNumber(value, 2)} s`;
}

export function formatDate(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(iso));
}

export function formatDateTime(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale, {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

export function formatShortDate(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: '2-digit',
    }).format(new Date(iso));
}

/**
 * "il y a 4 min", "il y a 2 h", "il y a 3 j".
 */
export function formatRelative(iso: string | null | undefined): string {
    if (!iso) {
        return 'jamais';
    }

    const seconds = Math.round((Date.now() - new Date(iso).getTime()) / 1000);
    const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

    if (Math.abs(seconds) < 60) {
        return "à l'instant";
    }

    const minutes = Math.round(seconds / 60);

    if (Math.abs(minutes) < 60) {
        return rtf.format(-minutes, 'minute');
    }

    const hours = Math.round(minutes / 60);

    if (Math.abs(hours) < 24) {
        return rtf.format(-hours, 'hour');
    }

    return rtf.format(-Math.round(hours / 24), 'day');
}

export function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} o`;
    }

    if (bytes < 1024 * 1024) {
        return `${formatNumber(bytes / 1024, 1)} Ko`;
    }

    return `${formatNumber(bytes / (1024 * 1024), 1)} Mo`;
}

export function formatPrice(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: Number.isInteger(value) ? 0 : 2,
    }).format(value);
}

/**
 * Text color class for a health score (one color = one meaning).
 */
export function scoreTone(score: number | null | undefined): string {
    if (score === null || score === undefined) {
        return 'text-muted-foreground';
    }

    if (score >= 80) {
        return 'text-ok';
    }

    if (score >= 60) {
        return 'text-warn-text';
    }

    return 'text-danger';
}

export const componentLabels: Record<string, string> = {
    dtc: 'Codes défauts',
    misfire: 'Ratés',
    trims: 'Mélange',
    cooling: 'Refroidissement',
    electric: 'Électricité',
    readiness: 'Moniteurs',
    trends: 'Tendances',
};
