-- ============================================================================
-- AdHub demo data — safe to re-run (removes and re-creates the demo accounts).
--
-- Demo client logins (password for all three: demo1234):
--   bloom@gmail.com     — Bloom Cosmetics
--   urbanfit@gmail.com  — UrbanFit Gym
--   brewbean@gmail.com  — BrewBean Coffee
--
-- Apply with seed-demo.ps1 (recommended — it also creates the sample
-- deliverable files). Importing this SQL directly is fine too, but seeded
-- file downloads return 404 until the sample files exist in
-- uploads/deliverables.
-- ============================================================================

-- Reset previous demo data (cascades into projects/deliverables/retainers/logs).
DELETE FROM tbl_users WHERE email IN ('bloom@gmail.com', 'urbanfit@gmail.com', 'brewbean@gmail.com');

-- ------------------------------------------------------------------ Clients --
INSERT INTO tbl_users (full_name, email, password, role, company_name) VALUES
('Maria Santos', 'bloom@gmail.com', '$2y$10$Ce5Fgs1iJDCfkfv3j2VrkuP.PubVY2psZlPFL7Ymhda06QJIkCu4q', 'client', 'Bloom Cosmetics');
SET @bloom := LAST_INSERT_ID();

INSERT INTO tbl_users (full_name, email, password, role, company_name) VALUES
('Miguel Reyes', 'urbanfit@gmail.com', '$2y$10$Ce5Fgs1iJDCfkfv3j2VrkuP.PubVY2psZlPFL7Ymhda06QJIkCu4q', 'client', 'UrbanFit Gym');
SET @urbanfit := LAST_INSERT_ID();

INSERT INTO tbl_users (full_name, email, password, role, company_name) VALUES
('Anna Cruz', 'brewbean@gmail.com', '$2y$10$Ce5Fgs1iJDCfkfv3j2VrkuP.PubVY2psZlPFL7Ymhda06QJIkCu4q', 'client', 'BrewBean Coffee');
SET @brewbean := LAST_INSERT_ID();

SET @admin := (SELECT id FROM tbl_users WHERE email = 'admin@gmail.com');

-- ----------------------------------------------------------------- Projects --
INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent, created_at) VALUES
(@bloom, 'Summer Glow Campaign', 'Graphic Design', 1200.00, 'Hero banners, social teasers and landing page copy for the summer glow product line.', 'in-progress', 40, NOW() - INTERVAL 21 DAY);
SET @p_summer := LAST_INSERT_ID();

INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent, created_at) VALUES
(@bloom, 'Holiday Greetings', 'Poster', 300.00, 'Festive social graphics for the December greeting series.', 'pending', 0, NOW() - INTERVAL 3 DAY);
SET @p_holiday := LAST_INSERT_ID();

INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent, created_at) VALUES
(@urbanfit, 'New Year Membership Push', 'Poster', 800.00, 'Gym promo poster for the January membership drive.', 'revision', 60, NOW() - INTERVAL 30 DAY);
SET @p_newyear := LAST_INSERT_ID();

INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent, created_at) VALUES
(@urbanfit, 'App Launch Teaser', 'Graphic Design', 450.00, 'Teaser visuals for the UrbanFit app launch.', 'pending', 0, NOW() - INTERVAL 5 DAY);
SET @p_applaunch := LAST_INSERT_ID();

INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent, created_at) VALUES
(@brewbean, 'Grand Opening Assets', 'Graphic Design', 650.00, 'Opening banner, signage layout and window graphics.', 'completed', 100, NOW() - INTERVAL 45 DAY);
SET @p_grand := LAST_INSERT_ID();

INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent, created_at) VALUES
(@brewbean, 'Tagline Sprint', 'Slogan', 250.00, 'Short tagline set for cups and signage.', 'completed', 100, NOW() - INTERVAL 40 DAY);
SET @p_tagline := LAST_INSERT_ID();

INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent, created_at) VALUES
(@brewbean, 'Contest Graphics', 'Poster', 200.00, 'Poster concepts for the latte art contest — client went another direction.', 'rejected', 0, NOW() - INTERVAL 14 DAY);
SET @p_contest := LAST_INSERT_ID();

-- ------------------------------------------------------------- Deliverables --
INSERT INTO tbl_deliverables (project_id, file_path, display_name, version, approval_status, review_note, reviewed_at, uploaded_at) VALUES
(@p_summer, 'uploads/deliverables/demo-hero-banner.png', 'Hero Banner', 1, 'approved', NULL, NOW() - INTERVAL 16 DAY, NOW() - INTERVAL 18 DAY),
(@p_summer, 'uploads/deliverables/demo-social-teaser.png', 'Social Teaser', 1, 'pending', NULL, NULL, NOW() - INTERVAL 6 DAY),
(@p_newyear, 'uploads/deliverables/demo-promo-poster.png', 'Promo Poster', 1, 'revision', 'Make the headline bolder and fix the gym logo placement.', NOW() - INTERVAL 18 DAY, NOW() - INTERVAL 20 DAY),
(@p_newyear, 'uploads/deliverables/demo-promo-poster.png', 'Promo Poster', 2, 'pending', NULL, NULL, NOW() - INTERVAL 4 DAY),
(@p_grand, 'uploads/deliverables/demo-grand-opening.png', 'Grand Opening Banner', 1, 'approved', NULL, NOW() - INTERVAL 39 DAY, NOW() - INTERVAL 41 DAY),
(@p_tagline, 'uploads/deliverables/demo-tagline-lines.txt', 'Tagline Lines', 1, 'approved', NULL, NOW() - INTERVAL 34 DAY, NOW() - INTERVAL 36 DAY);

-- ---------------------------------------------------------------- Retainers --
INSERT INTO tbl_retainers (client_id, amount, note, created_at) VALUES
(@bloom, 1000.00, 'Initial retainer', NOW() - INTERVAL 35 DAY),
(@bloom, 500.00, 'June retainer', NOW() - INTERVAL 10 DAY),
(@urbanfit, 600.00, 'Initial retainer', NOW() - INTERVAL 32 DAY),
(@brewbean, 800.00, 'Launch package retainer', NOW() - INTERVAL 50 DAY);

-- --------------------------------------------------------------- Time logs --
INSERT INTO tbl_time_logs (project_id, admin_id, hours, hourly_rate, note, logged_at) VALUES
(@p_summer, @admin, 12.50, 40.00, 'Hero banner concepts and revisions', NOW() - INTERVAL 15 DAY),
(@p_summer, @admin, 6.00, 40.00, 'Social teaser and copy drafting', NOW() - INTERVAL 5 DAY),
(@p_newyear, @admin, 9.00, 45.00, 'Poster design and rework after feedback', NOW() - INTERVAL 10 DAY),
(@p_applaunch, @admin, 3.00, 45.00, 'Concept exploration', NOW() - INTERVAL 3 DAY),
(@p_grand, @admin, 5.00, 35.00, 'Banner design', NOW() - INTERVAL 40 DAY),
(@p_grand, @admin, 8.00, 35.00, 'Signage and layout adaptations', NOW() - INTERVAL 38 DAY),
(@p_tagline, @admin, 3.00, 35.00, 'Tagline ideation rounds', NOW() - INTERVAL 36 DAY),
(@p_contest, @admin, 2.50, 35.00, 'Contest key visual draft', NOW() - INTERVAL 12 DAY);

-- --------------------------------------------------------- Service requests --
INSERT INTO tbl_requests (client_id, title, message, status, admin_reply, replied_at, created_at) VALUES
(@urbanfit, 'Spring promo video', 'We want a short promo video for the spring membership drive. Can you take this on?', 'open', NULL, NULL, NOW() - INTERVAL 2 DAY),
(@bloom, 'Loyalty card redesign', 'The loyalty card feels outdated — can you redesign it to match the new packaging?', 'accepted', 'Great timing — first concepts will be with you early next week.', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 4 DAY),
(@brewbean, 'Menu board refresh', 'Please refresh the in-store menu board with the new latte line.', 'done', 'Menu board delivered — enjoy the new look!', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 9 DAY);

-- ------------------------------------------------------------- Chat samples --
-- General threads (project_id NULL) …
INSERT INTO tbl_chat_messages (client_id, sender_id, body, created_at, read_at) VALUES
(@bloom, @bloom, 'Hi! Quick question about the loyalty card redesign — is a QR code on the back still on the table?', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 2 DAY),
(@bloom, @admin, 'Definitely — we will include room for a QR code on the back.', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(@bloom, @bloom, 'Perfect. When can we expect the first concepts?', NOW() - INTERVAL 4 HOUR, NULL),
(@urbanfit, @urbanfit, 'The promo video request — can it be ready before the spring drive starts?', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(@urbanfit, @admin, 'Yes — we booked it in. First cut should reach you in about two weeks.', NOW() - INTERVAL 1 DAY, NULL),
(@brewbean, @brewbean, 'The menu board looks fantastic — thank you!', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY),
(@brewbean, @admin, 'Glad you like it — enjoy the new latte line!', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 7 DAY);

-- … and project threads.
INSERT INTO tbl_chat_messages (client_id, project_id, sender_id, body, created_at, read_at) VALUES
(@bloom, @p_summer, @admin, 'Hero banner v2 is uploaded — let us know what you think!', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
(@bloom, @p_summer, @bloom, 'Looks great! Could the headline be a touch lighter for the summer feel?', NOW() - INTERVAL 5 DAY, NULL),
(@urbanfit, @p_newyear, @admin, 'Promo poster v2 is up — bolder headline and the logo has been repositioned.', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(@urbanfit, @p_newyear, @urbanfit, 'Nice — checking it with the team tomorrow.', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 2 DAY);
