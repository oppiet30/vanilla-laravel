-- Existing installations only. Resolve duplicate names before running this once.
-- Inspect duplicates:
-- SELECT name, COUNT(*) FROM demo_users GROUP BY name HAVING COUNT(*) > 1;
-- Run with an account permitted to ALTER this table. No records are changed.
ALTER TABLE demo_users ADD CONSTRAINT demo_users_name_unique UNIQUE (name);
