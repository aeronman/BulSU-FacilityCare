#!/bin/bash
# ============================================================
# BulSU FacilityCare - Integration Test Script
# ============================================================
# Prerequisites:
#   - PHP 8.1+ with curl extension
#   - MySQL server running
#   - Database 'bulsu_facilitycare' created
#   - Run setup_database.php first
#   - Start PHP server: php -S localhost:8000 -t public/
#
# Usage: bash tests/integration_test.sh [base_url]
#   Default base_url: http://localhost:8000
# ============================================================

set -e

BASE_URL="${1:-http://localhost:8000}"
PASS=0
FAIL=0
TOTAL=0

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS_FILE=$(mktemp)

pass() {
    TOTAL=$((TOTAL + 1))
    PASS=$((PASS + 1))
    echo -e "  ${GREEN}✓ PASS${NC}: $1"
}

fail() {
    TOTAL=$((TOTAL + 1))
    FAIL=$((FAIL + 1))
    echo -e "  ${RED}✗ FAIL${NC}: $1"
    echo "     Expected: $2"
    echo "     Got: $3"
}

section() {
    echo ""
    echo -e "${YELLOW}=== $1 ===${NC}"
}

# Extract CSRF token from HTML
get_csrf() {
    local html="$1"
    echo "$html" | grep -oP 'csrf_token.*?value="\K[^"]+'
}

# Login and save cookies
do_login() {
    local username="$1"
    local password="$2"
    local cookie_file="$3"

    local page=$(curl -s -c "$cookie_file" "$BASE_URL/login")
    local csrf=$(get_csrf "$page")

    curl -s -c "$cookie_file" -b "$cookie_file" -o /dev/null -w '%{http_code}' \
        -X POST "$BASE_URL/login" \
        -d "csrf_token=${csrf}&username=${username}&password=${password}"
}

echo "============================================"
echo " BulSU FacilityCare - Integration Tests"
echo " Base URL: $BASE_URL"
echo "============================================"

# ============================================================
# 1. ROUTING TESTS
# ============================================================
section "1. Routing Tests"

echo "  Testing route accessibility..."

routes=("/")
for route in "/" "/login" "/register" "/dashboard" "/submit-report" "/my-reports" \
           "/notifications" "/profile" "/all-reports" "/assigned-reports" \
           "/admin/dashboard" "/admin/reports" "/admin/validate" "/admin/duplicates" \
           "/admin/priority" "/admin/monitoring" "/admin/analytics" \
           "/admin/facilities" "/admin/users" "/admin/categories" "/admin/settings"
do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL$route")
    if [ "$code" = "200" ] || [ "$code" = "302" ]; then
        pass "GET $route returns $code"
    else
        fail "GET $route" "200 or 302" "$code"
    fi
done

echo "  Testing static assets..."
for asset in "/assets/css/style.css" "/assets/js/main.js" "/assets/img/logo.png"; do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL$asset")
    if [ "$code" = "200" ]; then
        pass "GET $asset returns $code"
    else
        fail "GET $asset" "200" "$code"
    fi
done

# ============================================================
# 2. AUTHENTICATION TESTS
# ============================================================
section "2. Authentication Tests"

# Login as admin
cookie_admin=$(mktemp)
code=$(do_login "admin" "admin123" "$cookie_admin")
if [ "$code" = "302" ]; then
    pass "Admin login redirects (302)"
else
    fail "Admin login" "302" "$code"
fi

# Test wrong password
cookie_fail=$(mktemp)
code=$(do_login "admin" "wrongpassword" "$cookie_fail")
if [ "$code" = "200" ]; then
    pass "Wrong password stays on login page (200)"
else
    fail "Wrong password" "200" "$code"
fi

# Login as maintenance
cookie_main=$(mktemp)
code=$(do_login "maintenance" "maintenance123" "$cookie_main")
if [ "$code" = "302" ]; then
    pass "Maintenance login redirects (302)"
else
    fail "Maintenance login" "302" "$code"
fi

# Login as student
cookie_student=$(mktemp)
code=$(do_login "student1" "user123" "$cookie_student")
if [ "$code" = "302" ]; then
    pass "Student login redirects (302)"
else
    fail "Student login" "302" "$code"
fi

# ============================================================
# 3. DASHBOARD TESTS
# ============================================================
section "3. Dashboard Tests"

# Admin dashboard
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/dashboard")
if [ "$code" = "200" ]; then
    pass "Admin dashboard accessible (200)"
else
    fail "Admin dashboard" "200" "$code"
fi

# Student dashboard
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" "$BASE_URL/dashboard")
if [ "$code" = "200" ]; then
    pass "Student dashboard accessible (200)"
else
    fail "Student dashboard" "200" "$code"
fi

# Maintenance dashboard
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_main" "$BASE_URL/dashboard")
if [ "$code" = "200" ]; then
    pass "Maintenance dashboard accessible (200)"
else
    fail "Maintenance dashboard" "200" "$code"
fi

# ============================================================
# 4. REPORT SUBMISSION TESTS
# ============================================================
section "4. Report Submission Tests"

# Get submit report page
submit_page=$(curl -s -b "$cookie_student" "$BASE_URL/submit-report")
csrf=$(get_csrf "$submit_page")

if [ -n "$csrf" ]; then
    pass "Submit report page loads with CSRF token"
else
    fail "Submit report CSRF" "non-empty" "empty"
fi

# Submit a report
report_response=$(curl -s -b "$cookie_student" -c "$cookie_student" -w '\n%{http_code}' \
    -X POST "$BASE_URL/submit-report" \
    -F "csrf_token=${csrf}" \
    -F "title=Test Facility Issue - Integration Test" \
    -F "description=A pipe is leaking in the main building hallway near room 101. Water is causing flooding and potential safety hazard." \
    -F "category_id=1" \
    -F "facility_id=1" \
    -F "urgency=high" \
    -F "safety_risk=moderate" \
    -F "severity=major" \
    -F "affected_users=30")

report_code=$(echo "$report_response" | tail -1)
if [ "$report_code" = "302" ]; then
    pass "Report submission redirects (302)"
else
    fail "Report submission" "302" "$report_code"
fi

# Get the report ID from the my reports page
reports_page=$(curl -s -b "$cookie_student" "$BASE_URL/my-reports")
# Extract first report ID from links
REPORT_ID=$(echo "$reports_page" | grep -oP '/report/\K[0-9]+' | head -1)

if [ -n "$REPORT_ID" ]; then
    pass "Report ID found: $REPORT_ID"
    echo "TEST_REPORT_ID=$REPORT_ID" > "$PASS_FILE"
else
    fail "Report ID extraction" "non-empty" "empty"
    REPORT_ID="1"
fi

# ============================================================
# 5. REPORT DETAILS TESTS
# ============================================================
section "5. Report Details Tests"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" "$BASE_URL/report/$REPORT_ID")
if [ "$code" = "200" ]; then
    pass "Report details page accessible (200)"
else
    fail "Report details" "200" "$code"
fi

# ============================================================
# 6. MY REPORTS TESTS
# ============================================================
section "6. My Reports Tests"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" "$BASE_URL/my-reports")
if [ "$code" = "200" ]; then
    pass "My Reports page accessible (200)"
else
    fail "My Reports" "200" "$code"
fi

# ============================================================
# 7. NOTIFICATIONS TESTS
# ============================================================
section "7. Notifications Tests"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" "$BASE_URL/notifications")
if [ "$code" = "200" ]; then
    pass "Notifications page accessible (200)"
else
    fail "Notifications" "200" "$code"
fi

# ============================================================
# 8. ADMIN - REPORT VALIDATION TESTS
# ============================================================
section "8. Admin Report Validation"

validate_page=$(curl -s -b "$cookie_admin" "$BASE_URL/admin/validate")
csrf_val=$(get_csrf "$validate_page")

# Validate the report
validate_code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" \
    -X POST "$BASE_URL/admin/validate/$REPORT_ID" \
    -d "csrf_token=${csrf_val}&status=validated&notes=Validated by test script&priority_level=high&priority_score=23.5")

if [ "$validate_code" = "302" ]; then
    pass "Report validation redirects (302)"
else
    fail "Report validation" "302" "$validate_code"
fi

# ============================================================
# 9. ADMIN - DUPLICATE DETECTION TESTS
# ============================================================
section "9. Duplicate Detection"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/duplicates")
if [ "$code" = "200" ]; then
    pass "Duplicate reports page accessible (200)"
else
    fail "Duplicate reports page" "200" "$code"
fi

# ============================================================
# 10. ADMIN - PRIORITY ASSESSMENT TESTS
# ============================================================
section "10. Priority Assessment"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/priority")
if [ "$code" = "200" ]; then
    pass "Priority assessment page accessible (200)"
else
    fail "Priority assessment page" "200" "$code"
fi

# ============================================================
# 11. ADMIN - MAINTENANCE MONITORING TESTS
# ============================================================
section "11. Maintenance Monitoring"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/monitoring")
if [ "$code" = "200" ]; then
    pass "Maintenance monitoring page accessible (200)"
else
    fail "Maintenance monitoring page" "200" "$code"
fi

# ============================================================
# 12. ADMIN - ANALYTICS TESTS
# ============================================================
section "12. Analytics"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/analytics")
if [ "$code" = "200" ]; then
    pass "Analytics page accessible (200)"
else
    fail "Analytics page" "200" "$code"
fi

# ============================================================
# 13. ADMIN - FACILITIES TESTS
# ============================================================
section "13. Admin Facilities Management"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/facilities")
if [ "$code" = "200" ]; then
    pass "Facilities page accessible (200)"
else
    fail "Facilities page" "200" "$code"
fi

fac_page=$(curl -s -b "$cookie_admin" "$BASE_URL/admin/facilities")
csrf_fac=$(get_csrf "$fac_page")

# Add facility
fac_code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" \
    -X POST "$BASE_URL/facilities/store" \
    -d "csrf_token=${csrf_fac}&name=Test Facility&building=Main Building&criticality_weight=1.5&description=Added by test")

if [ "$fac_code" = "302" ]; then
    pass "Facility creation redirects (302)"
else
    fail "Facility creation" "302" "$fac_code"
fi

# ============================================================
# 14. ADMIN - USERS TESTS
# ============================================================
section "14. Admin Users Management"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/users")
if [ "$code" = "200" ]; then
    pass "Users page accessible (200)"
else
    fail "Users page" "200" "$code"
fi

# ============================================================
# 15. ADMIN - CATEGORIES TESTS
# ============================================================
section "15. Admin Categories Management"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/categories")
if [ "$code" = "200" ]; then
    pass "Categories page accessible (200)"
else
    fail "Categories page" "200" "$code"
fi

cat_page=$(curl -s -b "$cookie_admin" "$BASE_URL/admin/categories")
csrf_cat=$(get_csrf "$cat_page")

# Add category
cat_code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" \
    -X POST "$BASE_URL/admin/categories/store" \
    -d "csrf_token=${csrf_cat}&name=Test Category&criticality_weight=1.5&description=Added by test")

if [ "$cat_code" = "302" ]; then
    pass "Category creation redirects (302)"
else
    fail "Category creation" "302" "$cat_code"
fi

# ============================================================
# 16. ADMIN - SETTINGS TESTS
# ============================================================
section "16. Admin Settings"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/admin/settings")
if [ "$code" = "200" ]; then
    pass "Settings page accessible (200)"
else
    fail "Settings page" "200" "$code"
fi

# ============================================================
# 17. ROLE PERMISSION TESTS
# ============================================================
section "17. Role Permission Tests"

# Student cannot access admin pages
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" "$BASE_URL/admin/dashboard")
if [ "$code" = "302" ]; then
    pass "Student blocked from admin dashboard (302 redirect)"
else
    fail "Student admin access" "302" "$code"
fi

# Maintenance cannot access admin-only pages (users, settings, facilities)
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_main" "$BASE_URL/admin/users")
if [ "$code" = "302" ]; then
    pass "Maintenance blocked from users page (302 redirect)"
else
    fail "Maintenance admin access" "302" "$code"
fi

# ============================================================
# 18. API TESTS
# ============================================================
section "18. API Tests"

# API facility (logged in as admin)
api_fac_code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/api/facility/1")
if [ "$api_fac_code" = "200" ]; then
    api_response=$(curl -s -b "$cookie_admin" "$BASE_URL/api/facility/1")
    if echo "$api_response" | grep -q '"name"'; then
        pass "API facility returns JSON with facility data"
    else
        fail "API facility JSON" "contains name field" "$api_response"
    fi
else
    fail "API facility endpoint" "200" "$api_fac_code"
fi

# API facility all
api_fac_all_code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/api/facility")
if [ "$api_fac_all_code" = "200" ]; then
    pass "API facility list returns 200"
else
    fail "API facility list" "200" "$api_fac_all_code"
fi

# API report
api_report_code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_admin" "$BASE_URL/api/report/$REPORT_ID")
if [ "$api_report_code" = "200" ]; then
    api_report_response=$(curl -s -b "$cookie_admin" "$BASE_URL/api/report/$REPORT_ID")
    if echo "$api_report_response" | grep -q '"report"'; then
        pass "API report returns JSON with report data"
    else
        fail "API report JSON" "contains report field" "$api_report_response"
    fi
else
    fail "API report endpoint" "200" "$api_report_code"
fi

# ============================================================
# 19. STATUS UPDATE TESTS
# ============================================================
section "19. Report Status Flow"

report_details=$(curl -s -b "$cookie_student" "$BASE_URL/report/$REPORT_ID")
csrf_status=$(get_csrf "$report_details")

# Update status
status_code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" \
    -X POST "$BASE_URL/report/$REPORT_ID/status" \
    -d "csrf_token=${csrf_status}&status_code=ongoing&notes=Work started")

if [ "$status_code" = "302" ]; then
    pass "Status update redirects (302)"
else
    fail "Status update" "302" "$status_code"
fi

# ============================================================
# 20. COMMENT TESTS
# ============================================================
section "20. Comments"

comment_code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" \
    -X POST "$BASE_URL/report/$REPORT_ID/comment" \
    -d "csrf_token=${csrf_status}&comment=This is a test comment")

if [ "$comment_code" = "302" ]; then
    pass "Comment submission redirects (302)"
else
    fail "Comment submission" "302" "$comment_code"
fi

# ============================================================
# 21. LOGOUT TESTS
# ============================================================
section "21. Logout"

code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" -c "$cookie_student" "$BASE_URL/logout")
if [ "$code" = "302" ]; then
    pass "Logout redirects (302)"
else
    fail "Logout" "302" "$code"
fi

# Verify logged out
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$cookie_student" "$BASE_URL/dashboard")
if [ "$code" = "302" ]; then
    pass "Logged out user redirected from dashboard (302)"
else
    fail "Post-logout dashboard access" "302" "$code"
fi

# ============================================================
# SUMMARY
# ============================================================
section "Test Summary"
echo ""
echo "  Total:  $TOTAL"
echo -e "  ${GREEN}Passed:  $PASS${NC}"
echo -e "  ${RED}Failed:  $FAIL${NC}"
echo ""

# Cleanup
rm -f "$PASS_FILE" "$cookie_admin" "$cookie_main" "$cookie_student" "$cookie_fail"

if [ "$FAIL" -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed. Review the output above.${NC}"
    exit 1
fi
