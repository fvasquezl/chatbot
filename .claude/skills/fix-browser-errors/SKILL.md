---
name: fix-browser-errors
description: >-
  Detects and fixes browser errors using Laravel Boost browser logs. Activates when debugging
  frontend issues, JS errors, console errors, network failures, Vite errors, or when the user
  mentions browser errors, console logs, frontend bugs, or JavaScript exceptions.
---

# Fix Browser Errors

## When to Apply

Activate this skill when:

- The user reports frontend or browser errors
- Debugging JavaScript exceptions or console errors
- Investigating network request failures
- Troubleshooting Vite manifest or asset loading issues
- The user says "something is broken in the browser" or similar

## Workflow

### Step 1: Read Browser Logs

Use the `browser-logs` tool from Laravel Boost to read the last 20 entries:

```
browser-logs(entries: 20)
```

### Step 2: Read Backend Logs

Check if the errors originate from the backend using the `last-error` tool:

```
last-error()
```

Also check application logs with `read-log-entries` for related server-side errors.

### Step 3: Classify Errors

Categorize each error found:

| Category | Examples | Typical Fix |
|----------|----------|-------------|
| JS Runtime | `TypeError`, `ReferenceError` | Fix source code in Blade/Livewire/Alpine |
| Network | `404`, `500`, `CORS` | Fix routes, controllers, or middleware |
| Vite/Assets | `ViteException`, manifest errors | Run `npm run build` or fix asset paths |
| Livewire | `Component not found`, hydration errors | Fix component registration or wire: bindings |
| Alpine | `x-data`, `$wire` errors | Fix Alpine expressions or Livewire integration |

### Step 4: Search Documentation

Use `search-docs` to find relevant fixes based on the error category:

- For Livewire errors: search with `packages: ["livewire/livewire"]`
- For Vite errors: search with `packages: ["laravel/framework"]` and queries like `["vite manifest", "asset bundling"]`
- For Filament errors: search with `packages: ["filament/filament"]`

### Step 5: Apply the Fix

1. Locate the source file causing the error
2. Read the file to understand the context
3. Apply the minimal fix needed
4. If frontend assets were modified, remind the user to run `vendor/bin/sail npm run build`

### Step 6: Verify

1. Read browser logs again to confirm the error is resolved
2. Check backend logs for any new errors introduced
3. Run relevant tests if they exist

## Common Browser Error Patterns

### Vite Manifest Error

```
Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest
```

**Fix:** Run `vendor/bin/sail npm run build` or `vendor/bin/sail npm run dev`.

### Livewire Component Not Found

```
Component [component-name] not found
```

**Fix:** Verify the component exists, is properly named, and the namespace matches.

### Alpine.js Undefined Property

```
Alpine Expression Error: Cannot read properties of undefined
```

**Fix:** Check `x-data` bindings and ensure `$wire` properties exist on the Livewire component.

### 419 CSRF Token Mismatch

```
419 (unknown status)
```

**Fix:** Ensure `@csrf` is included in forms or the CSRF token is sent with AJAX requests.

### 422 Validation Error

```
422 Unprocessable Content
```

**Fix:** Check Form Request validation rules and ensure the request payload matches expectations.

## Common Pitfalls

- Not checking backend logs alongside browser logs — many frontend errors originate server-side
- Fixing symptoms instead of root causes (e.g., suppressing JS errors instead of fixing the data)
- Forgetting to rebuild assets after modifying JS/CSS files
- Not verifying the fix with a second browser-logs read
