# CORS Configuration

## Overview

The backend uses the `nelmio/cors-bundle` to handle Cross-Origin Resource Sharing (CORS) for API requests from the frontend during development.

## Why CORS is Needed

In development:
- **Frontend**: Runs on `http://localhost:3000` (Vite dev server)
- **Backend API**: Runs on `http://localhost:8080` (Nginx/Symfony)

Since these are different origins (different ports), browsers enforce the Same-Origin Policy and block requests unless the backend explicitly allows them via CORS headers.

In production, CORS is not needed because:
- Frontend is served by the backend (same origin)
- All requests are relative (e.g., `/api/auth/login`)

## Configuration

### Package
- **Bundle**: `nelmio/cors-bundle` v2.6
- **Installation**: Installed via Composer
- **Config**: `backend/config/packages/nelmio_cors.yaml`

### Settings

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization', 'Accept-Language', 'X-Requested-With']
        expose_headers: ['Link']
        max_age: 3600
        allow_credentials: true
    paths:
        '^/api':
            allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
            allow_headers: ['Content-Type', 'Authorization', 'Accept-Language', 'X-Requested-With']
            allow_methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
            allow_credentials: true
            max_age: 3600
```

### Environment Variable

**`.env.dev`**:
```dotenv
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

This regex pattern allows:
- `http://localhost:3000` (frontend dev server)
- `http://localhost:8080` (backend)
- `http://127.0.0.1:*` (any port on localhost)
- Both HTTP and HTTPS

### Key Features

1. **Allow Credentials**: `true` - Allows cookies/authentication headers
2. **Preflight Caching**: `max_age: 3600` - Caches OPTIONS requests for 1 hour
3. **Custom Headers**: Includes `Accept-Language` for i18n support
4. **API-Specific**: Only applies to `/api/*` routes

## Testing CORS

### Test with curl

```bash
# Test OPTIONS preflight request
curl -X OPTIONS http://localhost:8080/api/auth/me \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -v
```

**Expected Headers**:
```
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: content-type, authorization, accept-language, x-requested-with
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 3600
```

### Test with Frontend

```javascript
// Should work without CORS errors
fetch('http://localhost:8080/api/auth/me', {
  method: 'GET',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
  },
})
```

## How It Works

### 1. Preflight Request (OPTIONS)

When browser detects a cross-origin request with custom headers or methods, it first sends an OPTIONS request:

```
OPTIONS /api/auth/login HTTP/1.1
Origin: http://localhost:3000
Access-Control-Request-Method: POST
Access-Control-Request-Headers: Content-Type, Authorization
```

### 2. Server Response

Backend responds with allowed origins, methods, and headers:

```
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: content-type, authorization, accept-language
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 3600
```

### 3. Actual Request

Browser proceeds with the actual request:

```
POST /api/auth/login HTTP/1.1
Origin: http://localhost:3000
Content-Type: application/json
```

### 4. Response with CORS Headers

Backend includes CORS headers in the response:

```
HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Credentials: true
Content-Type: application/json
```

## Common Issues

### CORS Error Still Appears

**Symptoms**: Browser console shows CORS error despite configuration

**Solutions**:
1. Clear Symfony cache: `docker compose -f docker-compose.dev.yaml exec ebs bin/console cache:clear`
2. Restart containers: `docker compose -f docker-compose.dev.yaml restart ebs nginx`
3. Check origin regex pattern matches your frontend URL
4. Verify frontend sends correct `Origin` header

### Credentials Not Sent

**Symptoms**: Cookies or auth headers not included in requests

**Solutions**:
1. Ensure `allow_credentials: true` in CORS config
2. Set `credentials: 'include'` in frontend fetch/axios
3. Use `withCredentials: true` in axios client

### Headers Not Allowed

**Symptoms**: Error about specific header not allowed

**Solution**: Add the header to `allow_headers` list in config:
```yaml
allow_headers: ['Content-Type', 'Authorization', 'Accept-Language', 'X-Requested-With', 'Your-Custom-Header']
```

## Production Configuration

In production, CORS is typically not needed because:
- Frontend is built and served by backend
- All requests are same-origin
- No cross-origin requests occur

If you need CORS in production (e.g., separate frontend domain):

```dotenv
# .env.production
CORS_ALLOW_ORIGIN='^https?://(app\.example\.com|www\.example\.com)$'
```

## Security Notes

1. **Never use `*` for `allow_origin` with `allow_credentials: true`**
   - Browsers will reject this for security reasons
   - Always specify exact origins

2. **Use strict origin patterns**
   - Current pattern allows any localhost port
   - In production, use exact domain names

3. **Limit allowed methods**
   - Only include methods your API actually uses
   - Don't expose unnecessary HTTP methods

4. **Minimize exposed headers**
   - Only expose headers the frontend needs
   - Don't expose sensitive server headers

## Debugging

### Enable Symfony debug mode

```bash
# Check if CORS bundle is loaded
docker compose -f docker-compose.dev.yaml exec ebs bin/console debug:config nelmio_cors

# Check registered routes
docker compose -f docker-compose.dev.yaml exec ebs bin/console debug:router | grep api
```

### Check CORS headers in browser

1. Open browser DevTools (F12)
2. Go to Network tab
3. Make API request
4. Check Response Headers for `Access-Control-*` headers
5. Check Request Headers for `Origin` header

### Verbose curl output

```bash
# Show full request/response including headers
curl -X GET http://localhost:8080/api/auth/me \
  -H "Origin: http://localhost:3000" \
  --verbose
```

## References

- [nelmio/cors-bundle Documentation](https://github.com/nelmio/NelmioCorsBundle)
- [MDN CORS Guide](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [Symfony Configuration Reference](https://symfony.com/doc/current/configuration.html)
