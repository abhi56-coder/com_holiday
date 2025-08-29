-- Uninstall script for Enhanced Holiday Packages Component
-- This will remove all tables and data created by the component

-- Drop tables in reverse order to respect foreign key constraints
DROP TABLE IF EXISTS `#__hp_offer_usage`;
DROP TABLE IF EXISTS `#__hp_wishlist`;
DROP TABLE IF EXISTS `#__hp_email_templates`;
DROP TABLE IF EXISTS `#__hp_settings`;
DROP TABLE IF EXISTS `#__hp_inquiries`;
DROP TABLE IF EXISTS `#__hp_offers`;
DROP TABLE IF EXISTS `#__hp_reviews`;
DROP TABLE IF EXISTS `#__hp_payments`;
DROP TABLE IF EXISTS `#__hp_booking_travelers`;
DROP TABLE IF EXISTS `#__hp_bookings`;
DROP TABLE IF EXISTS `#__hp_customers`;
DROP TABLE IF EXISTS `#__hp_itinerary`;
DROP TABLE IF EXISTS `#__hp_packages`;
DROP TABLE IF EXISTS `#__hp_categories`;
DROP TABLE IF EXISTS `#__hp_destinations`;