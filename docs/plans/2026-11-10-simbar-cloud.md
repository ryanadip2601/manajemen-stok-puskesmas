# SIMBAR Cloud Implementation Plan

**Goal**  
Prepare a private Supabase staging import, a Cloudinary attachment importer, and an authenticated Vercel preview.

**Architecture**  
Keep the new React application in `simbar-web`. Import the SQLite backup into a private PostgreSQL schema. Use Supabase Auth for sessions and enforce the legacy SIMBAR role and approval on the server.

**Tech stack**  
React, TypeScript, Vite, Vercel Functions, Supabase PostgreSQL and Auth, Cloudinary, and Python SQLite.

1. In `simbar-web/scripts/verify-migration.mjs`, verify that PostgreSQL accepts the backup and preserves counts, links, and stock totals. Run the check against the preserved SQLite file.
2. Add `simbar-web/db/schema.sql` and `simbar-web/scripts/export-sqlite.py`. Preserve source IDs and use inactive placeholders for the seven missing rooms. Keep legacy sessions and credential material in the backup.
3. Add `simbar-web/scripts/check-cloud.mjs` and `simbar-web/scripts/import-attachments.mjs`. Test service access, import restricted Cloudinary objects, and retain each original path in PostgreSQL.
4. Build the authenticated read-only dashboard in `simbar-web/src/main.tsx` and `simbar-web/api/summary.js`. Verify the production build and stage it in the existing SIMBAR Vercel project after access is available.
5. Verify counts, application sign-in, access restrictions, and attachment availability in staging before starting the operational modules or changing DNS.
