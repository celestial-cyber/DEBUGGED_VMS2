# VMS Application - URL Testing Guide

## Application URLs

### Primary Entry Point (NEW)
- **URL**: `http://localhost:8000/landing.php`
- **Description**: Public-facing landing page with Alumni Registration (default) and Staff Login tabs
- **Access**: Public (no authentication required)
- **Features**:
  - Alumni Registration Form (TAB 1 - Default)
  - Staff Login (TAB 2)
  - Responsive design for mobile/tablet/desktop

### Admin Dashboard (Protected)
- **URL**: `http://localhost:8000/admin_dashboard.php`
- **Description**: Admin control panel
- **Access**: Admin login required via landing.php
- **Note**: Redirects to landing.php if not authenticated

### Member Dashboard (Protected)
- **URL**: `http://localhost:8000/member_dashboard.php`
- **Description**: Member control panel
- **Access**: Member login required via landing.php
- **Note**: Redirects to landing.php if not authenticated

### Alumni Registration Form (Alternative)
- **URL**: `http://localhost:8000/alumni_registration.php`
- **Description**: Standalone alumni registration page (for direct access)
- **Access**: Public (no authentication required)
- **Note**: Also accessible via landing.php tab

### Other Admin Dashboards
- **Admin Manage Visitors**: `http://localhost:8000/Admin_dashbaord/manage-visitors.php`
- **Manage Inventory**: `http://localhost:8000/manage-inventory.php`
- **Manage Participation**: `http://localhost:8000/manage-participation.php`
- **Manage Goodies**: `http://localhost:8000/manage-goodies.php`
- **Manage Notes**: `http://localhost:8000/manage-notes.php`

## Testing Workflow

### Test 1: Alumni Self-Registration
```
1. Open http://localhost:8000/landing.php
2. Verify "Alumni Registration" tab is active (default)
3. Fill out form:
   - Full Name: John Doe
   - Roll Number: 2021-EC-001
   - Graduation Year: 2021
   - Department: Electronics
   - Email: john.doe@example.com
   - Phone: 9876543210
   - Organization: Tech Corp
   - Designation: Software Engineer
4. Click "Register Now"
5. Verify success message displays
6. Check database:
   - Record should appear in alumni_registrations table
<<<<<<< HEAD
   - Record should appear in vms_visitors table (synced)
=======
   - Record should appear in tbl_visitors table (synced)
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337
```

### Test 2: Staff Login (Admin)
```
1. Open http://localhost:8000/landing.php
2. Click "Staff Login" tab
3. Click "Admin Login" button
4. Enter credentials:
   - Email: (admin email from database)
   - Password: (admin password from database)
5. Click "Login"
6. Verify redirect to admin_dashboard.php
7. Verify you can see alumni records in visitor management
```

### Test 3: Staff Login (Member)
```
1. Open http://localhost:8000/landing.php
2. Click "Staff Login" tab
3. Click "Member Login" button
4. Enter credentials:
   - Email: (member email from database)
   - Password: (member password from database)
5. Click "Login"
6. Verify redirect to member_dashboard.php
7. Verify you can access member features
```

### Test 4: Tab Navigation
```
1. Open http://localhost:8000/landing.php
2. Verify alumni form is visible (default)
3. Click "Staff Login" tab
4. Verify staff login form appears
5. Click "Alumni Registration" tab
6. Verify alumni form reappears
7. Test on mobile (responsive design should adapt)
```

### Test 5: Form Validation
```
1. Open http://localhost:8000/landing.php
2. Try to submit empty form (all fields should be required)
3. Try invalid email (should reject)
4. Try phone number < 10 digits (should reject)
5. Fill valid data and submit
6. Verify success message
```

## Database Verification Commands

### Check Alumni Registrations
```sql
SELECT id, name, email, call_number, status, created_at 
FROM alumni_registrations 
ORDER BY created_at DESC 
LIMIT 10;
```

### Check Visitor Sync
```sql
SELECT id, name, email, phone, added_by, status, event_id 
<<<<<<< HEAD
FROM vms_visitors 
=======
FROM tbl_visitors 
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337
WHERE added_by IS NULL 
ORDER BY registration_date DESC 
LIMIT 10;
```

### Count Self-Registered Alumni
```sql
SELECT COUNT(*) as total_self_registered 
<<<<<<< HEAD
FROM vms_visitors 
=======
FROM tbl_visitors 
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337
WHERE added_by IS NULL;
```

### Verify Field Mapping
```sql
SELECT 
  a.name as alumni_name,
  v.name as visitor_name,
  a.email as alumni_email,
  v.email as visitor_email,
  a.call_number as alumni_phone,
  v.phone as visitor_phone,
  a.roll_number,
  v.roll_number as visitor_roll
FROM alumni_registrations a
<<<<<<< HEAD
LEFT JOIN vms_visitors v ON a.email = v.email AND a.name = v.name
=======
LEFT JOIN tbl_visitors v ON a.email = v.email AND a.name = v.name
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337
ORDER BY a.created_at DESC
LIMIT 5;
```

## Test Credentials (Example)

### Admin User
- **Email**: admin@example.com
<<<<<<< HEAD
- **Password**: admin123 (or check vms_admin table)

### Member User
- **Email**: member@example.com
- **Password**: member123 (or check vms_members table)
=======
- **Password**: admin123 (or check tbl_admin table)

### Member User
- **Email**: member@example.com
- **Password**: member123 (or check tbl_members table)
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337

**Note**: MD5 hashing is used, so compare with `MD5('password')` in database.

## Browser Console Testing

### Tab Switching (Open DevTools → Console)
```javascript
// Test alumni tab
switchTab('alumni');
console.log(document.getElementById('alumni-tab').classList.contains('active')); // true

// Test login tab
switchTab('login');
console.log(document.getElementById('login-tab').classList.contains('active')); // true
```

### Role Selection
```javascript
// Set admin role
setRole('admin');
console.log(document.getElementById('role').value); // 'admin'

// Set member role
setRole('member');
console.log(document.getElementById('role').value); // 'member'
```

## Performance Metrics

- **Page Load Time**: < 500ms (static assets only)
- **Form Submission**: < 1s (DB insert with transaction)
- **Database Query**: < 100ms (indexed on email, roll_number)

## Security Headers Recommendations

Consider adding to server configuration:
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'
```

## Troubleshooting

### Issue: Form not submitting
- **Check**: Browser console for JavaScript errors
- **Check**: Network tab to see POST request
- **Check**: error_log for PHP errors
- **Solution**: Verify connection.php is accessible

### Issue: Alumni not appearing in dashboard
- **Check**: alumni_registrations table for new records
<<<<<<< HEAD
- **Check**: vms_visitors table for synced records
=======
- **Check**: tbl_visitors table for synced records
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337
- **Check**: Event ID (should be 1)
- **Solution**: Verify transaction committed successfully

### Issue: Tab not switching
- **Check**: Browser console for switchTab() errors
- **Check**: JavaScript is not disabled
- **Solution**: Clear browser cache and reload

### Issue: Invalid credentials on login
- **Check**: Email/password match exactly in database
- **Check**: Password is MD5 hashed in database
- **Check**: Role selection is correct (admin vs member)
<<<<<<< HEAD
- **Solution**: Query vms_admin/vms_members directly to verify credentials
=======
- **Solution**: Query tbl_admin/tbl_members directly to verify credentials
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337

## Next Steps for Production

1. ✅ Test all URLs in browser
2. ✅ Verify alumni records sync correctly
3. ✅ Test staff login for both admin and member
4. ✅ Verify responsive design on mobile devices
5. ✅ Check error handling and edge cases
6. ✅ Monitor error logs for any issues
7. ✅ Backup database before deploying
8. ✅ Update event_id if not using "Annual Alumni Meet"
