# Quick Start - React Frontend

## 🚀 Get Started in 3 Steps

### 1. Install Frontend Dependencies
```bash
npm install
```

### 2. Start Backend (Terminal 1)
```bash
make dev
```
Wait for "Development environment is ready!"

### 3. Start Frontend (Terminal 2)
```bash
npm run dev
```

### 4. Open Browser
Visit: http://localhost:3000

**Default credentials:**
- Username: `john.smith` (employee) or any customer username
- Password: `password123`

---

## 📁 Project Structure

```
frontend/src/
├── pages/           # All page components
│   ├── customer/    # Customer pages
│   └── employee/    # Employee pages
├── components/      # Reusable components
├── contexts/        # Global state (Auth, Locale)
└── api/            # HTTP client

src/
├── **/Presentation/Api/  # Backend API controllers
└── **/Presentation/Controller/  # Old Twig controllers (can be removed later)
```

---

## 🔧 Common Commands

```bash
# Frontend
npm run dev          # Start dev server (port 3000)
npm run build        # Build for production
npm run preview      # Preview production build

# Or using Make
make frontend-dev
make frontend-build
make frontend-install

# Backend
make dev            # Start development environment
make fixtures       # Load test data
make test           # Run tests
```

---

## 🎯 What's Working

✅ React app structure
✅ Routing (React Router)
✅ Authentication (session-based)
✅ Protected routes
✅ All UI pages created
✅ API structure

## ⚠️ What Needs Work

- [ ] Implement API endpoint logic (marked with @TODO)
- [ ] Remove old Twig templates (optional)

---

## 💡 Development Tips

1. **Hot Reload**: Changes to React files auto-reload browser
2. **API Proxy**: Vite proxies `/api/*` to backend automatically
3. **Check Console**: Browser console shows errors
4. **Network Tab**: Check API requests/responses

---

## 🐛 Troubleshooting

**Backend not responding?**
- Check `make dev` is running
- Verify http://localhost:8080/api/auth/me works

**Frontend not loading?**
- Check `npm run dev` is running
- Clear browser cache
- Check for console errors

**CORS errors?**
- Should not happen (proxy configured)
- Check Vite config if issues persist

---

## 📚 Learn More

- Full guide: `REACT_MIGRATION.md`
- Migration summary: `MIGRATION_SUMMARY.md`
- Main README: `README.md`

---

## Next Steps for Development

1. Pick an API endpoint to implement (start with simple ones)
2. Look at corresponding Twig controller
3. Copy logic to API controller
4. Test in browser
5. Update tests
6. Repeat!

**Recommended starting points:**
- `GET /api/customer/accounts` - Already implemented! ✅
- `GET /api/customer/accounts/{id}/transactions` - Simple query
- `POST /api/customer/change-password` - Basic form handling
