# Handoff - Dashboard Eksekutif Pertanahan

## Status
Fase 3B MVP selesai pada 2026-09-06. Importer workbook Jawa Barat tersedia dengan dry-run, staging seluruh 54 kolom, lineage formula/cache, mapping fail-closed, QC, idempotensi, dan revision draft. Development telah menerima satu import sah. Tidak ada production, UI, dependency baru, perubahan workbook, commit atau push.

- Corrective migration `2026_09_06_000005_reconcile_observation_identity_and_definition.php` was added to restore the canonical `observations` unique identity (including `data_source_id`) without editing `000004`. It checks for duplicate identities before changing the index and aborts safely if duplicates exist. The migration was validated on the testing DB.

- The `IndicatorDefinitionSeeder` was adjusted so `XIII.4 (Roya %)` remains in the catalogue but without a placeholder `formula_key`/`formula_metadata`; it is marked `definition_pending` in master data until a validated mapping is provided. `MAP.1` remains a `feature` and is not seeded with definitions.

## Bukti Fase 2B (historis)
- Effective target: `dashboard_pertanahan_test` dan `dashboard_pertanahan_dev`, driver mysql, MariaDB 10.4.27. Credential tetap privat.
- Testing: `migrate:fresh --seed` sukses untuk 8 migration; seeder kedua idempotent.
- Full suite: 19 test/244 assertions PASS. Auth Fase 2A: 7/107 PASS. Domain: 9/127 PASS. Legacy normalization: 1/8 PASS. Tidak ada FAIL/SKIPPED.
- Development: awal tanpa tabel; `migrate --seed --force` sukses, seluruh 8 migration Ran. Tidak memakai fresh/wipe.
- Master kedua DB: 5 roles, 26 regions, 5 data owners, 6 sources, 3 categories, 7 submenus, 58 catalogue records; 0 users/snapshots/observations/revisions.

## Bukti akhir Fase 3A

- Baseline Git tetap `c35006eacecaa84fc3fba667d90dd023ffa583cb`; partial work dilanjutkan tanpa menimpa perubahan pengguna.
- Testing: mysql `dashboard_pertanahan_test`, MariaDB 10.4.27. `migrate:fresh --seed --env=testing --force` sukses untuk 10 migration; seeder kedua idempotent. Master: 5 role, 26 region, 5 owner, 6 source, 3 category, 7 submenu, 58 katalog dan 57 definition; 0 observation/revision setelah rebuild.
- Full suite dengan opt-in DB: **29 PASS / 376 assertions**, 0 FAIL, 0 SKIPPED. Fase 2A terpisah tetap **7 PASS / 107 assertions**. ManualObservation terarah **10 PASS / 128 assertions**.
- Development: mysql `dashboard_pertanahan_dev`; preflight menunjukkan `000005` pending dan 0 observation/revision. `php artisan migrate --seed --force` menerapkan migration secara non-destruktif. Sesudahnya semua 10 migration Ran; identity index benar; XIII.4 formula NULL/`definition_pending`; MAP.1 definition 0; observation/revision tetap 0.
- Quality gate: 26 file PHP lint PASS, Composer platform PASS, route middleware inspection PASS, dan `git diff --check` PASS dengan peringatan line-ending saja. Frontend tidak berubah, jadi npm build tidak dijalankan.

## Implementasi Fase 3A

- Migration `000004` menambah `indicator_definitions`, revision definition/source/note, `revision_status_events`, dan `published_values`. Migration `000005` memulihkan unique identity observation dengan `data_source_id`, berhenti bila ada duplicate, dan membersihkan formula placeholder XIII.4. Jangan edit migration yang sudah diterapkan; perubahan berikutnya wajib migration baru.
- Definition menyimpan versi, masa berlaku, tipe, unit, validation JSON, formula metadata bila sah, dan status aktif. Overlap definition aktif ditolak model. Historical revision menunjuk definition yang digunakan.
- API akhir: `GET /api/domain/indicators`; `GET /api/domain/observations`; `POST /api/domain/manual-observations`; `POST /api/domain/observations/{observation}/revisions`; `POST /api/domain/revisions/{revision}/submit`; `POST .../publish`; `POST .../reject`; `GET /api/domain/observations/{observation}/history`.
- Semua endpoint domain memakai `auth:sanctum` dan `account.active`. Policy memisahkan role, scope wilayah, dan ownership. Source manual tidak diterima dari client; server memilih `manual_pemda` atau `manual_kantah` dari owner indikator. Derived, feature, deferred, KKP/Excel, admin BPN dan viewer ditolak pada endpoint manual.
- Revision typed value append-only dibuat dalam transaksi dan row lock. `expected_revision` memberi 409 pada konflik; duplicate checksum ditolak. Operator submit; super admin publish/reject; viewer hanya membaca published. Audit menyimpan metadata ID/status yang aman tanpa value, note, password, token, credential atau headers.
- File utama Fase 3A: migration `000004`/`000005`; `IndicatorDefinitionSeeder`; model definition/published/status-event dan relasi observation; `DefinitionValueValidator`; `ManualObservationService`; dua controller domain; request strict; route domain; `ManualObservationApiTest`; pembaruan `DomainFoundationTest`; tool inspeksi DB; database plan/progress/handoff.

## Schema dan keamanan
Migration baru berurutan: `2026_09_06_000001_create_domain_master_tables`, `000002_create_reporting_tables`, `000003_normalize_legacy_users`. User memakai `password` dan `is_active`; roles melalui `user_roles`, wilayah melalui `user_region_scopes`. Hash legacy valid disalin; credential invalid menonaktifkan akun; known role dipivotkan; unknown role tidak diberi akses; kolom legacy dibuang.

Schema domain menyediakan roles, hierarchical regions (province/regency_city), owners/sources, category/submenu/indicator catalogue, `as_of_date` snapshots, observation identity, revisions dengan raw/source checksum lineage, quality flags, dan audit events. `MAP.1` adalah feature dengan `source_code=XIX.1` row 62; `XIX.1` layanan row 15 tetap ada. Canonical code unik, source code boleh berulang. Tidak ada kode resmi wilayah yang dikarang.

`IndicatorPolicy` fail-closed. Akun/role/owner/region harus aktif dan dikenal. Non-super user perlu scope exact atau parent province. Operator Pemda/Kantah hanya update manual milik owner masing-masing; admin BPN hanya import owner ATR/BPN; viewer read-only; super admin lintas wilayah secara eksplisit. Derived, BHUMI, deferred dan unknown tidak dapat ditulis manual. Protected auth routes juga memakai `EnsureAccountIsActive`; token akun nonaktif dicabut.

## File utama
- Migration: `api/database/migrations/0001_01_01_000000_create_users_table.php`, `2026_09_03_133822_add_role_to_users_table.php`, dan tiga migration `2026_09_06_*`.
- Seeder: `api/database/seeders/DatabaseSeeder.php`, `DomainMasterDataSeeder.php`.
- Models: User plus Role, Region, DataOwner, DataSource, Category, Submenu, Indicator, ReportingSnapshot, Observation, ObservationRevision.
- Authorization: `api/app/Policies/IndicatorPolicy.php`, `api/app/Http/Middleware/EnsureAccountIsActive.php`, bootstrap/routes/AuthController compatibility edits.
- Tests: `DomainFoundationTest.php`, `LegacyUserNormalizationTest.php`; semua test auth lama dipertahankan.
- Evidence/docs: `api/tools/domain-database.php`, `docs/02b-baseline-sha256.json`, `docs/04-database-plan.md`, `docs/2b-preflight.md`, `docs/progress.md`.

## Catatan untuk fase berikut
Baca AGENTS, PROJECT_CONTEXT, progress ini, database plan, reconciliation dan mapping final. Import development final adalah batch ID 2 untuk `2026-08-04`: 25 row, 1.350 values, 446 QC, 225 observation/revision draft, 9 indikator, 25 wilayah, tanpa published value. Pengulangan command harus tetap idempotent. Formula XIII.4, kolom tanpa kode, XII.1/AN ambigu, dan 48 source period/header belum jelas tetap fail-closed. Jangan membuat UI atau mempromosikan exclusion tanpa keputusan pengguna.

## Handoff Fase 3B MVP

- Command: `php artisan dashboard:import-jabar --file="data/source/Input Data Jawa Barat.xlsx" --as-of-date=2026-08-04 --dry-run`; hilangkan `--dry-run` untuk commit.
- Schema: migration `000006` membuat import batches/rows/values/quality flags dan revision lineage FK. Kedua database memiliki 11 migration Ran.
- Testing dibangun ulang pada `dashboard_pertanahan_test`; test importer 6/90 dan full suite 35/471 PASS. Setelah rollback test: 0 batch/observation/revision.
- Verifikasi akhir: 15 file PHP lint PASS; Composer platform requirements, command discovery, route list, dan `git diff --check` PASS. Tidak ada frontend/dependency change sehingga npm build tidak dijalankan.
- Development `dashboard_pertanahan_dev`: satu batch completed, satu audit, satu snapshot, 25 row, 1.350 values, 292 formula, 446 QC, 225 observations/revisions draft. 0 non-ATR observation, 0 missing lineage, 0 published.
- QC summary: period unclear 48, unmapped 31, derived excluded 125, ownership mismatch 197, mapping not approved 25, APL delta 10, NIB–NOP mismatch 2, RDTR zero denominator 8. Workbook aktual tidak memiliki formula-cache missing, region mismatch, unknown master indicator, atau invalid promotable value.
- Workbook hash diverifikasi sebelum/sesudah; file sumber tetap ignored/untracked dan tidak diubah. Tidak ada commit/push/remote access.

## Checkpoint Git lokal 2026-09-06
- Repo yang digunakan: `C:/xampp/htdocs/dashboard-pertanahan`.
- Identitas Git lokal aktif: `Joshua Paskah <supremiedrive@gmail.com>`.
- Pemeriksaan read-only dan dry-run aman. Tidak ada `.env`, `.env.testing`, `vendor/`, `node_modules/`, database lokal, workbook sumber, log/cache, atau file sesi yang masuk kandidat staging.
- Commit lokal akan dibuat dengan pesan: `chore: checkpoint through phase 2b`.
- Tidak ada push, tidak ada akses GitHub, dan tidak ada remote fetch/push yang dilakukan.
- Dokumentasi dan metadata proyek disimpan bersama artefak code tanpa menampilkan nilai rahasia.
