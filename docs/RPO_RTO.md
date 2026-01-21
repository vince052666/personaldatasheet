# RPO/RTO Targets

## Recovery Objectives

### RPO (Data Loss Tolerance)
- Database: 1 hour
- Documents: 24 hours
- Audit Logs: 0 (zero data loss)

### RTO (Recovery Time)
- Critical Services: 4 hours
- Full System: 8 hours
- Read-only Mode: 30 minutes

## Backup Schedule
- Database Full: Daily at 2AM
- Database Incremental: Every 4 hours
- Documents: Daily at 3AM
- Audit Logs: Hourly

## Retention
- Daily backups: 30 days
- Monthly backups: 12 months
- Audit logs: 7 years (COA requirement)
