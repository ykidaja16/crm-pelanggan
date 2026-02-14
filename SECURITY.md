# Dokumentasi Keamanan Sistem CRM Pelanggan

## 🛡️ Ringkasan Keamanan

Sistem CRM ini telah dilengkapi dengan berbagai lapisan keamanan untuk melindungi data pelanggan dan integritas sistem.

## 🔐 Fitur Keamanan yang Diimplementasikan

### 1. Autentikasi & Autorisasi
- **Password Hashing**: Menggunakan bcrypt dengan cost factor default Laravel
- **Role-Based Access Control (RBAC)**: 
  - `Admin`: Mengelola data pelanggan (CRUD)
  - `Super Admin`: Mengelola users dan password reset
- **Session Management**: Regenerasi session ID setelah login

### 2. Rate Limiting & Brute Force Protection
- **Login Rate Limiting**: Maksimal 5 percobaan per menit per IP
- **Import Rate Limiting**: Maksimal 3 import per menit per user
- **Account Lockout**: Lock 15 menit setelah 5x failed login attempts

### 3. Session Security
- **Session Timeout**: Auto logout setelah 30 menit tidak aktif
- **Session Invalidation**: Saat logout atau timeout
- **CSRF Protection**: Laravel default (pastikan @csrf di form)

### 4. Security Headers
- `X-Frame-Options: DENY` - Mencegah clickjacking
- `X-Content-Type-Options: nosniff` - Mencegah MIME sniffing
- `X-XSS-Protection: 1; mode=block` - XSS protection
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy` - ⚠️ **DINONAKTIFKAN** sementara untuk kompatibilitas tampilan
- `Strict-Transport-Security` (HSTS) - Hanya di production

> **Catatan**: CSP dinonaktifkan karena menyebabkan masalah tampilan dengan Bootstrap dan Chart.js. Untuk mengaktifkan kembali, konfigurasi perlu disesuaikan dengan resource CDN yang digunakan.


### 5. Audit Logging
- **Log File**: `storage/logs/audit-YYYY-MM-DD.log`
- **Retention**: 30 hari (auto cleanup dengan command)
- **Logged Activities**:
  - Login/logout
  - CRUD operations pada pelanggan
  - User management
  - Import/Export data
  - Password reset activities

## 📋 Checklist Keamanan untuk Developer

### Saat Mengembangkan Fitur Baru:
- [ ] Validasi semua input (server-side)
- [ ] Gunakan parameterized queries (Eloquent/Query Builder)
- [ ] Implementasi authorization check
- [ ] Sanitasi output untuk mencegah XSS
- [ ] Tambahkan logging untuk actions penting
- [ ] Test dengan berbagai user roles

### Form Handling:
- [ ] Selalu gunakan `@csrf` di form Blade
- [ ] Validasi file upload (type, size, extension)
- [ ] Gunakan `validated()` untuk mengambil input yang sudah tervalidasi

### Database:
- [ ] Gunakan migrations untuk schema changes
- [ ] Encrypt sensitive data jika diperlukan
- [ ] Backup database secara regular

## 🚨 Security Incident Response

### Jika Terjadi Breach:
1. **Immediate Actions**:
   ```bash
   # Disable semua user accounts
   php artisan tinker
   >>> \App\Models\User::query()->update(['is_active' => false]);
   
   # Clear all sessions
   php artisan session:clear
   ```

2. **Investigation**:
   ```bash
   # Check audit logs
   tail -f storage/logs/audit-$(date +%Y-%m-%d).log
   
   # Check failed login attempts
   grep "Failed login" storage/logs/laravel.log
   ```

3. **Recovery**:
   - Reset passwords untuk affected users
   - Review dan patch vulnerability
   - Update security measures

## 🔧 Maintenance Rutin

### Command yang Tersedia:
```bash
# Cleanup old audit logs (default: 30 hari)
php artisan audit:cleanup

# Cleanup dengan custom days
php artisan audit:cleanup --days=7

# Clear application cache
php artisan cache:clear

# Clear session files
php artisan session:clear
```

### Schedule (Tambahkan di app/Console/Kernel.php):
```php
// Cleanup audit logs setiap minggu
$schedule->command('audit:cleanup')->weekly();

// Clear old sessions setiap hari
$schedule->command('session:clear')->daily();
```

## 📞 Contact Security Team

Jika menemukan vulnerability atau security issue:
1. Jangan disclose publicly
2. Email ke: [security@yourcompany.com]
3. Sertakan detail dan steps to reproduce

## 🔄 Update & Patch Management

- Monitor Laravel security advisories: https://laravel.com/docs/security
- Update dependencies secara regular: `composer update`
- Review security headers dan middleware settings setiap quarter

---

**Last Updated**: 2025
**Version**: 1.0
