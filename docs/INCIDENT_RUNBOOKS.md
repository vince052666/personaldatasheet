# Incident Runbooks

## System Down
1. Check PHP-FPM: `systemctl status php8.2-fpm`
2. Check Nginx: `systemctl status nginx`
3. Restart services if needed
4. Check logs: `tail -f storage/logs/laravel.log`

## Database Issues
1. Check MySQL: `systemctl status mysql`
2. Check connections: `SHOW PROCESSLIST;`
3. Kill long queries if needed
4. Restart MySQL if necessary

## High Failed Jobs
1. Check: `php artisan queue:failed`
2. Review error patterns
3. Fix underlying issue
4. Retry: `php artisan queue:retry all`

## Slow Performance
1. Clear cache: `php artisan cache:clear`
2. Rebuild: `php artisan config:cache`
3. Check slow queries
4. Optimize database

## Security Incident
1. Isolate: `php artisan down`
2. Review audit logs
3. Revoke tokens: `php artisan sanctum:purge`
4. Contact security officer

## Escalation
- Critical: 15 min → IT Director
- High: 1 hour → System Admin
- Medium: 4 hours
