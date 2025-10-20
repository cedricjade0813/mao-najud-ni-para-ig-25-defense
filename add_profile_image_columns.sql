-- SQL statements to add profile image columns for admin profile modal
-- Run these statements to add image support to all relevant tables

-- Add profile_image column to users table (for admin users)
ALTER TABLE `users` 
ADD COLUMN `profile_image` VARCHAR(255) NULL DEFAULT NULL 
COMMENT 'Profile image file path/URL for admin users';

-- Add profile_image column to faculty table (for faculty admin users)
ALTER TABLE `faculty` 
ADD COLUMN `profile_image` VARCHAR(255) NULL DEFAULT NULL 
COMMENT 'Profile image file path/URL for faculty admin users';

-- Add profile_image column to imported_patients table (for student admin users)
ALTER TABLE `imported_patients` 
ADD COLUMN `profile_image` VARCHAR(255) NULL DEFAULT NULL 
COMMENT 'Profile image file path/URL for student admin users';

-- Optional: Add index for better performance when querying by profile_image
-- CREATE INDEX idx_users_profile_image ON users(profile_image);
-- CREATE INDEX idx_faculty_profile_image ON faculty(profile_image);
-- CREATE INDEX idx_imported_patients_profile_image ON imported_patients(profile_image);

-- Example of how to update a user's profile image:
-- UPDATE users SET profile_image = 'uploads/profiles/admin_123.jpg' WHERE id = 1;
-- UPDATE faculty SET profile_image = 'uploads/profiles/faculty_456.jpg' WHERE faculty_id = 'FAC-001';
-- UPDATE imported_patients SET profile_image = 'uploads/profiles/student_789.jpg' WHERE id = 1;
