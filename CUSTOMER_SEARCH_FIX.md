# Customer Search Fix

## Issue
The customer search URL `https://app.andcorpautos.com/public/admin/customers.php?search=admin` is not working and redirects to the login page.

## Root Cause
When a user accesses the search URL while not logged in:
1. They get redirected to the login page (expected behavior)
2. The redirect URL should be stored with the query string preserved
3. After login, they should be redirected back to the search URL with the query string

The code should already handle this correctly, but there might be an issue with:
- Session not persisting the redirect URL
- The redirect URL not being used correctly after login
- The form action URL generation

## Fixes Applied

### 1. Improved Redirect URL Storage (`app/Auth.php`)
- Enhanced comments to clarify that `REQUEST_URI` includes the query string
- Ensured the redirect URL is stored as an absolute URL with query string preserved
- Added better fallback handling

### 2. Form Action (`public/admin/customers.php`)
- Changed form action from `$_SERVER['PHP_SELF']` to `url('admin/customers.php')`
- This ensures the form submits to the correct URL on cPanel servers

## Testing

### Test Case 1: Access Search URL While Not Logged In
1. Log out (or clear session)
2. Access: `https://app.andcorpautos.com/public/admin/customers.php?search=admin`
3. Should redirect to login page
4. Log in
5. Should redirect back to: `https://app.andcorpautos.com/public/admin/customers.php?search=admin`
6. Search results should be displayed

### Test Case 2: Search While Logged In
1. Log in as admin/staff
2. Go to customers page
3. Enter search term in the search box
4. Click "Search"
5. Should display search results

### Test Case 3: Direct URL Access While Logged In
1. Log in as admin/staff
2. Access: `https://app.andcorpautos.com/public/admin/customers.php?search=admin`
3. Should display search results immediately (no redirect)

## Files Modified
- `app/Auth.php` - Improved redirect URL storage with query string preservation
- `public/admin/customers.php` - Fixed form action to use `url()` helper

## Notes
- The `REQUEST_URI` server variable includes the query string automatically
- The redirect function handles absolute URLs correctly
- The session should persist the redirect URL across the login process

