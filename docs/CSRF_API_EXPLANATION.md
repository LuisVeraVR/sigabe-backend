# CSRF en API REST con Sanctum

## ¿Qué es CSRF?
Cross-Site Request Forgery: ataque donde un sitio malicioso hace requests a tu API en nombre del usuario.

## ¿Cuándo aplica en SIGABE?

### ✅ SÍ requiere protección CSRF:
- **Angular SPA** en el mismo dominio usando cookies de sesión
- Sanctum con `supports_credentials: true`
- Requests desde `FRONTEND_URL` configurada

### ❌ NO requiere protección CSRF:
- **API externa** con API Keys (sin cookies)
- **Mobile apps** con tokens Bearer
- Requests que NO usan cookies de sesión

## Configuración actual:

1. **Angular SPA**: 
   - Obtener CSRF token: `GET /sanctum/csrf-cookie`
   - Incluir token en header: `X-CSRF-TOKEN`

2. **API Externa**:
   - Solo API Key en header: `X-API-Key: {key}`
   - Sin cookies = sin CSRF

## Laravel maneja automáticamente:
- Sanctum valida origen de requests (CORS)
- VerifyCsrfToken middleware (solo para web, no API)
- EnsureFrontendRequestsAreStateful verifica dominio
