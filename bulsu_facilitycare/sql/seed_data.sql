-- ============================================================
-- Seed Data for BulSU FacilityCare
-- ============================================================

USE bulsu_facilitycare;

-- -------------------------------------------------
-- Roles
-- -------------------------------------------------
INSERT INTO roles (name, display_name, description) VALUES
('student_staff', 'Student / Faculty / Staff', 'Can submit reports and track their own reports'),
('maintenance', 'Maintenance Personnel', 'Can view assigned reports, update progress, and resolve issues'),
('admin', 'Administrator', 'Full system access including validation, prioritization, monitoring, and management');

-- -------------------------------------------------
-- Departments
-- -------------------------------------------------
INSERT INTO departments (code, name, description) VALUES
('IT', 'Information Technology', 'IT Department'),
('ENG', 'Engineering', 'College of Engineering'),
('EDU', 'Education', 'College of Education'),
('BUS', 'Business', 'College of Business'),
('NURSING', 'Nursing', 'College of Nursing'),
('ADMIN', 'Administration', 'Administrative Office'),
('PHYSICAL', 'Physical Plant', 'Physical Plant and Maintenance'),
('HOUSING', 'Student Housing', 'Student Dormitories and Housing');

-- -------------------------------------------------
-- Issue Categories (with criticality weights)
-- -------------------------------------------------
INSERT INTO categories (name, description, criticality_weight) VALUES
('Electrical', 'Electrical issues (lights, outlets, wiring)', 1.2),
('Plumbing', 'Plumbing issues (leaks, clogs, water pressure)', 1.1),
('HVAC', 'Heating, ventilation, and air conditioning', 1.15),
('Structural', 'Building structure issues (walls, floors, ceilings)', 1.5),
('Safety', 'Safety hazards (fire, security, emergency equipment)', 2.0),
('Furniture & Fixtures', 'Furniture and fixture issues', 1.0),
('IT/Technology', 'IT and technology infrastructure', 1.25),
('Cleaning & Sanitation', 'Cleaning and sanitation needs', 0.9),
('Landscaping', 'Outdoor and landscaping issues', 0.8),
('Elevator', 'Elevator and escalator issues', 1.8),
('Water System', 'Water supply and treatment systems', 1.3),
('Other', 'Other facility issues', 1.0);

-- -------------------------------------------------
-- Facilities / Locations
-- -------------------------------------------------
INSERT INTO facilities (building, floor, room_number, location_name, description, criticality_weight) VALUES
('Main Building', 'Ground Floor', 'R01', 'Main Building - Lobby', 'Main entrance lobby area', 1.5),
('Main Building', 'Ground Floor', 'R02', 'Main Building - Registrar', 'Office of the Registrar', 1.8),
('Main Building', '1st Floor', 'R101', 'Main Building - Classroom 101', 'Standard classroom', 1.3),
('Main Building', '1st Floor', 'R102', 'Main Building - Classroom 102', 'Standard classroom', 1.3),
('Main Building', '2nd Floor', 'R201', 'Main Building - AV Room', 'Audio-visual room', 1.4),
('Main Building', '2nd Floor', 'R202', 'Main Building - Guidance Office', 'Guidance and counseling office', 1.5),
('Main Building', '2nd Floor', 'R203', 'Main Building - Library', 'University library - high traffic', 1.6),
('Main Building', '3rd Floor', 'R301', 'Main Building - Conference Room', 'Administrative conference room', 1.4),
('Engineering Building', 'Ground Floor', 'E01', 'Engineering - Lab 1', 'Chemistry lab', 1.7),
('Engineering Building', '1st Floor', 'E101', 'Engineering - Computer Lab', 'Computer laboratory', 1.5),
('Engineering Building', '2nd Floor', 'E201', 'Engineering - Lab 2', 'Physics lab', 1.6),
('Education Building', '1st Floor', 'ED101', 'Education - Lecture Hall', 'Large lecture hall', 1.3),
('Business Building', 'Ground Floor', 'B01', 'Business - Hallway', 'Building hallway', 1.1),
('Business Building', '1st Floor', 'B101', 'Business - Classroom', 'Standard classroom', 1.2),
('Nursing Building', 'Ground Floor', 'N01', 'Nursing - Skills Lab', 'Nursing skills laboratory', 1.6),
('Nursing Building', '1st Floor', 'N101', 'Nursing - Lecture Room', 'Nursing lecture room', 1.4),
('Administration Building', 'Ground Floor', 'A01', 'Admin - HR Office', 'Human resources office', 1.4),
('Administration Building', '1st Floor', 'A101', 'Admin - Finance', 'Finance office', 1.4),
('Dormitory Building', 'Ground Floor', 'D01', 'Dormitory - Common Area', 'Student common area', 1.2),
('Dormitory Building', '1st Floor', 'D101', 'Dormitory - Restroom', 'Student restroom facility', 1.3),
('Canteen', 'Ground Floor', 'C01', 'Canteen - Kitchen', 'Kitchen area', 1.3),
('Canteen', 'Ground Floor', 'C02', 'Canteen - Dining Area', 'Student dining area', 1.2),
('Football Field', 'Outdoor', 'F01', 'Football Field', 'University football field', 1.0),
('Gymnasium', 'Ground Floor', 'G01', 'Gymnasium', 'University gymnasium', 1.2),
('Parking Area', 'Outdoor', 'P01', 'Parking Area', 'Vehicle parking area', 1.1);

-- -------------------------------------------------
-- Report Statuses
-- -------------------------------------------------
INSERT INTO report_statuses (code, label, display_order, color_class) VALUES
('submitted', 'Submitted', 0, 'info'),
('under_review', 'Under Review', 1, 'warning'),
('validated', 'Validated', 2, 'primary'),
('assigned', 'Assigned', 3, 'primary'),
('ongoing', 'Ongoing', 4, 'warning'),
('resolved', 'Resolved', 5, 'success'),
('closed', 'Closed', 6, 'secondary'),
('rejected', 'Rejected', 7, 'danger');

-- -------------------------------------------------
-- Duplicate Detection Thresholds
-- -------------------------------------------------
INSERT INTO duplicate_thresholds (field_name, weight, min_similarity) VALUES
('category_location', 0.40, 0.70),
('description', 0.30, 0.75),
('safety_risk', 0.15, 0.80),
('severity', 0.15, 0.80);

-- -------------------------------------------------
-- Priority Assessment Criteria Configuration
-- Safety Risk weights
-- -------------------------------------------------
SET @safety_risk_weights = 'no:0|minor:2|moderate:5|severe:8';
SET @severity_weights = 'minor:1|moderate:3|major:5|critical:8';
SET @urgency_weights = 'low:1|medium:3|high:5';
SET @location_base = 5; -- max location score

-- -------------------------------------------------
-- Default Settings
-- -------------------------------------------------
INSERT INTO settings (setting_key, setting_value, description, setting_type, is_public) VALUES
('app_name', 'BulSU FacilityCare', 'Application name', 'string', 1),
('app_tagline', 'Facility Maintenance Reporting & Risk Prioritization System', 'Application tagline', 'string', 1),
('admin_email', 'admin@bulsu.edu.ph', 'System administrator email', 'string', 0),
('items_per_page', '15', 'Items per page in listings', 'number', 1),
('duplicate_detection_threshold', '0.70', 'Minimum similarity score for duplicate detection', 'number', 0),
('priority_high_threshold', '7.5', 'Score threshold for HIGH priority', 'number', 0),
('priority_medium_threshold', '4.0', 'Score threshold for MEDIUM priority', 'number', 0),
('maintenance_auto_assign', '1', 'Auto-assign reports to maintenance by category', 'boolean', 0),
('allow_registration', '1', 'Allow new user registration', 'boolean', 0),
('report_reminder_days', '3', 'Days before overdue reminder notification', 'number', 0),
('working_hours_start', '08:00:00', 'Working hours start', 'time', 0),
('working_hours_end', '17:00:00', 'Working hours end', 'time', 0);

-- -------------------------------------------------
-- Default Users (passwords: admin123, maintenance123, user123)
-- -------------------------------------------------
INSERT INTO users (role_id, department_id, employee_id, username, email, password, full_name, phone) VALUES
(3, 7, 'EMP-ADMIN-001', 'admin', 'admin@bulsu.edu.ph', '$2y$10$N9qo8uLOickgx2ZMRZoMy.Mrq7M8bGz8rMaWxCxY1y6xQqJ0uJ5uW', 'Admin User', '09123456789'),
(2, 7, 'EMP-MNT-001', 'maintenance', 'maintenance@bulsu.edu.ph', '$2y$10$N9qo8uLOickgx2ZMRZoMy.Mrq7M8bGz8rMaWxCxY1y6xQqJ0uJ5uW', 'Maintenance Supervisor', '09123456790'),
(2, 7, 'EMP-MNT-002', 'john_maint', 'john.maint@bulsu.edu.ph', '$2y$10$N9qo8uLOickgx2ZMRZoMy.Mrq7M8bGz8rMaWxCxY1y6xQqJ0uJ5uW', 'John Smith', '09123456791'),
(1, 3, 'STU-2024-0001', 'student1', 'student1@bulsu.edu.ph', '$2y$10$N9qo8uLOickgx2ZMRZoMy.Mrq7M8bGz8rMaWxCxY1y6xQqJ0uJ5uW', 'Alice Ramos', '09123456792'),
(1, 3, 'STU-2024-0002', 'faculty1', 'faculty1@bulsu.edu.ph', '$2y$10$N9qo8uLOickgx2ZMRZoMy.Mrq7M8bGz8rMaWxCxY1y6xQqJ0uJ5uW', 'Dr. Benicio Santos', '09123456793'),
(1, 7, 'EMP-STAFF-001', 'staff1', 'staff1@bulsu.edu.ph', '$2y$10$N9qo8uLOickgx2ZMRZoMy.Mrq7M8bGz8rMaWxCxY1y6xQqJ0uJ5uW', 'Maria Clara', '09123456794');
