/**
 * Global route helper that resolves named routes to URLs.
 * Works with Laravel's named routes for simple parameter substitution.
 */

const routeDefinitions: Record<string, string> = {};

// Register routes from Wayfinder-generated route files
function registerRoute(name: string, url: string) {
    if (url && url !== '#') {
        routeDefinitions[name] = url;
    }
}

// Load all routes from the routes directory (including subdirectories)
const routeModules = import.meta.glob('../routes/**/*.ts', { eager: true });

for (const [path, mod] of Object.entries(routeModules)) {
    // Extract the route group name from the path
    // e.g., "../routes/todos/index.ts" -> "todos"
    // e.g., "../routes/index.ts" -> "" (root)
    const parts = path.replace('../routes/', '').replace('.ts', '').split('/');
    const group = parts.length > 1 ? parts[0] : '';
    const funcName = parts.length > 1 ? parts[1] : parts[0];

    const exports = (mod as Record<string, unknown>);
    for (const [name, value] of Object.entries(exports)) {
        if (name === 'default' || name.startsWith('_') || name === 'queryParams' || name === 'RouteQueryOptions') continue;
        if (typeof value === 'function' && value.definition) {
            const def = value.definition as { url: string };
            if (def.url) {
                // Build the route name: "todos.index", "projects.store", etc.
                const routeName = group ? `${group}.${name}` : name;
                registerRoute(routeName, def.url);
            }
        }
    }
}

export function route(name: string, params?: string | string[] | Record<string, unknown>, options?: Record<string, unknown>): string {
    let url = routeDefinitions[name];

    if (!url) {
        // Try fallback: just use the name as a URL path
        console.warn(`Route "${name}" not found in Wayfinder. Available:`, Object.keys(routeDefinitions).join(', '));
        return `/${name.replace('.', '/')}`;
    }

    // Handle params as array or string
    if (typeof params === 'string' || Array.isArray(params)) {
        const paramValues = Array.isArray(params) ? params : [params];
        url = url.replace(/\{[^}]+\}/g, () => {
            return String(paramValues.shift() ?? '');
        });
    } else if (params && typeof params === 'object') {
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(`{${key}}`, String(value));
        }
    }

    return url;
}
