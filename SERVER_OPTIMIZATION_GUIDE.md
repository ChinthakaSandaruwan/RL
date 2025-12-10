# PHP & Server Configuration Optimization

This guide covers server-side optimizations to improve website performance.

## 1. PHP OPcache Configuration

OPcache stores precompiled PHP bytecode in memory, dramatically improving PHP performance.

### Enable OPcache in php.ini

Location: `C:\xampp\php\php.ini`

Add or modify the following settings:

```ini
[opcache]
; Enable OPcache
opcache.enable=1
opcache.enable_cli=1

; Memory allocation (increase for larger applications)
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000

; Revalidation
opcache.revalidate_freq=2
opcache.validate_timestamps=1

; Performance
opcache.fast_shutdown=1
opcache.save_comments=1
```

### Restart Apache
```powershell
# In XAMPP Control Panel, click "Stop" then "Start" for Apache
# Or via command line:
C:\xampp\apache\bin\httpstop.bat
C:\xampp\apache\bin\httpstart.bat
```

### Verify OPcache is Working

Create a file `c:\xampp\htdocs\RL\phpinfo.php`:
```php
<?php
phpinfo();
?>
```

Visit: `http://localhost/RL/phpinfo.php`
Search for "opcache" - you should see OPcache enabled.

**⚠️ Delete phpinfo.php after checking (security risk)**

## 2. Apache Configuration (.htaccess)

Already implemented in `.htaccess`:
- ✅ Gzip compression
- ✅ Browser caching
- ✅ Cache-Control headers

### Additional Apache Optimizations

Add to `.htaccess` if not present:

```apache
# Disable ETags (caching relies on Expires/Cache-Control)
<IfModule mod_headers.c>
    Header unset ETag
</IfModule>
FileETag None

# Set Connection Keep-Alive
<IfModule mod_headers.c>
    Header set Connection keep-alive
</IfModule>
```

## 3. Database Optimization (MySQL/MariaDB)

### Apply Performance Indexes

Run the SQL script we created:
```sql
-- Execute in phpMyAdmin
source c:\xampp\htdocs\RL\database_performance_indexes.sql;
```

Or manually run the queries from `database_performance_indexes.sql`.

### MySQL Configuration Tuning

Edit: `C:\xampp\mysql\bin\my.ini`

```ini
[mysqld]
# Query Cache (if MySQL < 8.0)
query_cache_type=1
query_cache_size=32M
query_cache_limit=2M

# Buffer Pool Size (set to 50-70% of RAM if dedicated server)
innodb_buffer_pool_size=256M

# Log file size
innodb_log_file_size=64M

# Connections
max_connections=100
```

**Restart MySQL** after changes.

## 4. Session Optimization

### Use Database Sessions (Recommended for Production)

Modify `c:\xampp\htdocs\RL\config\db.php` session handling:

```php
// Instead of file-based sessions, use database sessions
// This improves performance on shared hosting

function ensure_session_started() {
    if (session_status() === PHP_SESSION_NONE) {
        // Use database for session storage
        session_save_path('');
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}
```

## 5. Content Delivery Network (CDN)

### Option A: Use Cloudflare (Free)

1. Sign up at https://cloudflare.com
2. Add your domain
3. Update DNS nameservers
4. Cloudflare will automatically:
   - Cache static files
   - Compress content
   - Protect against DDoS
   - Provide free SSL

### Option B: Local CDN Emulation

Create a subdomain for static assets:
- Move static files (CSS, JS, images) to `static.yourdomain.com`
- This enables parallel downloads (browsers can download from multiple domains simultaneously)

## 6. Enable HTTP/2 (Requires SSL)

### Benefits:
- Multiplexing (multiple requests over single connection)
- Server push
- Header compression

### Enable in Apache:

1. Install SSL certificate (Let's Encrypt or self-signed for testing)
2. Enable SSL module in XAMPP
3. Add to Apache config:

```apache
# In httpd.conf or httpd-ssl.conf
LoadModule http2_module modules/mod_http2.so

<VirtualHost *:443>
    Protocols h2 http/1.1
    # ... rest of SSL config
</VirtualHost>
```

## 7. PHP Performance Settings

### php.ini Optimizations

```ini
; Disable unnecessary features in production
expose_php=Off
display_errors=Off
log_errors=On
error_log=C:\xampp\php\logs\php_error.log

; Memory limit (adjust based on needs)
memory_limit=256M

; Max execution time
max_execution_time=30

; Upload limits (if needed)
upload_max_filesize=10M
post_max_size=10M

; Realpath cache (improves file access)
realpath_cache_size=4096k
realpath_cache_ttl=600
```

## 8. Monitoring & Profiling

### Enable Slow Query Log (MySQL)

```ini
[mysqld]
slow_query_log=1
slow_query_log_file=C:\xampp\mysql\slow_queries.log
long_query_time=2
```

Queries taking > 2 seconds will be logged.

### PHP Execution Time Monitoring

Add to `config/db.php`:

```php
// Debug mode - measure page load time
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    $start_time = microtime(true);
    
    register_shutdown_function(function() use ($start_time) {
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000;
        error_log("Page execution time: " . number_format($execution_time, 2) . "ms");
    });
}
```

## 9. Production Checklist

Before deploying to production:

### Security & Performance:
- [ ] Enable OPcache
- [ ] Disable `display_errors` in php.ini
- [ ] Enable error logging
- [ ] Set `expose_php=Off`
- [ ] Apply database indexes
- [ ] Enable Gzip compression
- [ ] Set appropriate cache headers
- [ ] Convert images to WebP
- [ ] Implement lazy loading
- [ ] Enable HTTPS
- [ ] Configure firewall rules
- [ ] Set up regular backups

### Files to Remove:
- [ ] Delete `phpinfo.php` (if created)
- [ ] Remove test/debug scripts
- [ ] Clear temporary files

## 10. Performance Testing Tools

After implementing optimizations, test with:

1. **Google PageSpeed Insights**: https://pagespeed.web.dev/
   - Target: 90+ score

2. **GTmetrix**: https://gtmetrix.com/
   - Target: Grade A

3. **WebPageTest**: https://www.webpagetest.org/
   - Test from multiple locations

4. **Chrome DevTools**:
   - Network tab (check file sizes, load times)
   - Performance tab (analyze rendering)
   - Lighthouse (comprehensive audit)

## Expected Results

### Before Optimization:
- LCP: 18.8s
- FCP: 3.0s
- Page Load: 20+ seconds

### After All Optimizations:
- **LCP: < 2.5s** ✅
- **FCP: < 1.8s** ✅
- **Page Load: 3-5 seconds** ✅
- **Speed Index: < 3.4s** ✅

## Maintenance

### Weekly:
- Check error logs
- Review slow query log
- Monitor disk space

### Monthly:
- Update dependencies
- Review access logs
- Optimize database tables: `OPTIMIZE TABLE table_name;`
- Check and update indexes

### Quarterly:
- Re-run performance audits
- Update PHP/MySQL versions
- Review security configurations
