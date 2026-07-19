/**
 * Global route helper that resolves named routes to URLs.
 * Works with Laravel's named routes for simple parameter substitution.
 */

const routeDefinitions: Record<string, string> = {};

// Register routes from Wayfinder-generated route files
function registerRoute(name: string, url: string) {
    routeDefinitions[name] = url;
}

// Load all routes from the routes directory
const routeModules = import.meta.glob('../routes/**/*.ts', { eager: true });

for (const [path, mod] of Object.entries(routeModules)) {
    const exports = (mod as Record<string, unknown>);
    for (const [name, value] of Object.entries(exports)) {
        if (name === 'default' || name.startsWith('_')) continue;
        if (typeof value === 'function' && value.definition) {
            const def = value.definition as { url: string };
            if (def.url) {
                registerRoute(name, def.url);
            }
        }
    }
}

export function route(name: string, params?: string | string[] | Record<string, unknown>, options?: Record<string, unknown>): string {
    let url = routeDefinitions[name];

    if (!url) {
        console.warn(`Route "${name}" not found. Available:`, Object.keys(routeDefinitions).join(', '));
        return '#';
    }

    // Handle params as array or string
    if (typeof params === 'string' || Array.isArray(params)) {
        const paramValues = Array.isArray(params) ? params : [params];
        // Replace {param} placeholders in order
        url = url.replace(/\{[^}]+\}/g, () => {
            return String(paramValues.shift() ?? '');
        });
    } else if (params && typeof params === 'object') {
        // Replace {param} with named values
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(`{${key}}`, String(value));
        }
    }

    return url;
}
