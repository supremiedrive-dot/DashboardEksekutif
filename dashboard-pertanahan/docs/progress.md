# Progress

## Tanggal
2026-09-06

## Tugas
- [x] Fase 3B MVP selesai: reader XLSX, command dry-run/commit, staging 54 kolom, mapping terbatas, QC, idempotensi, dan import development.
- [x] Fase 3A selesai: definition version, API manual Pemda/Kantah, typed validation, revision immutable, workflow, audit, dan authorization domain.
- [x] Tahap 0, Fase 1A, 1B, dan 2A selesai.
- [x] Baseline/status Git serta integritas perubahan lama dicatat.
- [x] `.env`/`.env.testing` ignored dan untracked; `phpunit.xml` tanpa secret.
- [x] Koneksi efektif development dan testing dibuktikan terpisah.
- [x] Migration schema domain dan jalur normalisasi user legacy dibuat.
- [x] Seeder master deterministik dijalankan dua kali tanpa duplikasi.
- [x] Model/relasi, middleware akun aktif, dan policy role/wilayah/ownership dibuat.
- [x] Database testing dibangun ulang dari migration kosong.
- [x] Test Fase 2A dan 2B lulus pada MySQL testing.
- [x] Development kosong dimigrasikan non-destruktif dan master data di-seed.
- [x] Dokumentasi dan handoff diperbarui.
- [x] Corrective migration `2026_09_06_000005_reconcile_observation_identity_and_definition.php` added and tested on `testing`.
- [x] `IndicatorDefinitionSeeder` and `DomainMasterDataSeeder` adjusted to avoid placeholder formula for `XIII.4` and keep `MAP.1` as feature.

## File yang diperiksa
`AGENTS.md`; seluruh `PROJECT_CONTEXT_DASHBOARD_PERTANAHAN.md`; `docs/02-data-dictionary-mapping.md`; `docs/04-database-plan.md`; `docs/2b-preflight.md`; `docs/implementation-plan.md`; progress/HANDOFF sebelumnya; `docs/02a-baseline-sha256.json`; seluruh migration, seeder, model, policy, middleware, provider, route dan test terkait di `api/`. Workbook tidak dibaca ulang dan tidak diubah.

## Perubahan kecil yang dibuat
- Migration user starter: tambah `is_active`; migration role string lama menjadi no-op agar authority tunggal memakai pivot.
- Migration `2026_09_06_000001_create_domain_master_tables`: roles/scope wilayah/owner/source/navigasi/katalog.
- Migration `2026_09_06_000002_create_reporting_tables`: snapshot, observation, immutable revision lineage, QC dan audit.
- Migration `2026_09_06_000003_normalize_legacy_users`: valid hash dan known role dimigrasikan; invalid/unknown deny; kolom legacy dibuang.
- `DomainMasterDataSeeder` dan `DatabaseSeeder`: master deterministik tanpa user atau nilai observation.
- Model baru: Role, Region, DataOwner, DataSource, Category, Submenu, Indicator, ReportingSnapshot, Observation, ObservationRevision; User mendapat relasi role/scope.
- `IndicatorPolicy`, `EnsureAccountIsActive`, alias middleware dan route protected; login menolak akun nonaktif secara generik dan profil memberi role hasil relasi.
- Test `DomainFoundationTest` dan `LegacyUserNormalizationTest`.
- `api/tools/domain-database.php`: inspeksi target/kondisi DB yang fail-closed tanpa menampilkan credential.
- `docs/02b-baseline-sha256.json`, database plan, preflight, progress dan HANDOFF.

## Verifikasi yang dijalankan
- Git ignore/tracked check dan scan secret pada `phpunit.xml`.
- Guarded connection inspection pada local/testing, termasuk `SELECT DATABASE()` aktual.
- `php artisan migrate:fresh --seed --env=testing --force` setelah isolasi terbukti; dilakukan ulang setelah test legacy menemukan dan memperbaiki bug callback.
- `php artisan db:seed --env=testing --force` kedua kali.
- `php artisan test --env=testing` dengan opt-in DB auth.
- `php artisan migrate --seed --force` pada development kosong; bukan fresh/wipe.
- `php artisan migrate:status`, `route:list -v --path=api`, `composer check-platform-reqs`.
- `php artisan migrate:fresh --seed --env=testing --force` executed successfully to validate migration `000005` and seeder fixes; targeted ManualObservation tests passed.
- `php -l` seluruh 26 file PHP berubah/baru dan `git diff --check`.
- Perbandingan SHA256 terhadap baseline awal Fase 2B.

## Hasil verifikasi
- Testing dan development: mysql pada database yang tepat, MariaDB 10.4.27. Tidak ada SQLite atau production.
- Rebuild testing: 8 migration sukses; seeder dua kali tetap 5 role, 26 region, 5 owner, 6 source, 3 category, 7 submenu, 58 catalogue record. Users/snapshot/observation/revision tetap 0.
- Test final: **19 PASS, 244 assertions**. Fase 2A tetap **7 PASS, 107 assertions**; DomainFoundation **9 PASS, 127 assertions**; legacy normalization **1 PASS, 8 assertions**; 2 starter test juga PASS. Tidak ada FAIL/SKIPPED.
- Targeted `ManualObservationApiTest`: **10 PASS (128 assertions)** setelah koreksi identity, definition, dan source server-side; seluruh assertion lulus pada testing.
- Development: sebelum migration kosong; sesudah migration non-destruktif, seluruh 8 migration Ran and jumlah master sama dengan testing. Tidak ada user atau observation dibuat.
- API route protected memuat `auth:sanctum` lalu `EnsureAccountIsActive`; login tetap throttle 5/menit.
- Semua lint/platform/diff check lulus. Frontend tidak berubah, jadi npm build tidak dijalankan.
- Verifikasi integritas baseline: 11 file awal berubah sesuai scope, 20 file baru, 0 file hilang. File lain identik SHA256; `.env`, UI, workbook, dan dependency lock tidak berubah.

## Risiko / catatan
- `indicators` berfungsi sebagai catalogue record minimal: 57 indikator data + 1 feature MAP.1 bertanda `is_feature`; observations tetap dapat dicegah untuk feature lewat policy/service Fase 3. Pemisahan catalogue_entries/definition version yang lebih kaya dari desain konseptual ditunda sampai diperlukan tanpa menduplikasi tabel.
- Scope provinsi mewarisi satu tingkat kabupaten/kota. Kecamatan/desa belum ada sesuai keputusan.
- Formula tidak diubah/diimplementasikan. Validation typed-value lintas kolom dan publication workflow akan dilengkapi bersama service Fase 3; schema lineage dasarnya tersedia.
- Test legacy memakai DDL hanya pada database testing dan mengembalikan kolom ke schema kanonis. Bug callback pertama terdeteksi, testing dibangun ulang, lalu semua test lulus.
- Semua file awal proyek masih untracked secara Git; SHA256 baseline adalah bukti perubahan. Tidak ada file hilang, workbook/.env/UI/dependency lock tidak berubah, dan tidak ada commit/push.

## Selanjutnya
Fase 3B MVP selesai. Langkah berikut setelah persetujuan pengguna adalah meninjau 446 QC/staging exclusions dan menentukan fase dashboard/API baca atau perhitungan derived yang telah tervalidasi. Jangan mempromosikan mapping ambigu atau membuat UI sebelum scope berikut disetujui.

## Catatan final Fase 3B MVP

- Baseline `654e663`; working tree awal bersih. Tidak ada dependency, auth, UI, workbook, production, commit, push, fetch, atau akses remote yang dilakukan.
- Reader aman memakai `ZipArchive`/SimpleXML yang sudah tersedia: batas file/expanded archive, larangan XML entity/DOCTYPE dan external/unsafe path, sheet/header tervalidasi, shared formula lineage dan cached values dipertahankan. Tidak ada package baru.
- Migration `000006_create_excel_import_staging_tables` menambah batch/row/value/import QC dan FK revision→import value. Database testing dibangun ulang; development hanya memakai migration non-destruktif.
- Command: `php artisan dashboard:import-jabar --file="data/source/Input Data Jawa Barat.xlsx" --as-of-date=2026-08-04 [--dry-run]`.
- Dry-run final: 25 wilayah, 54 kolom, 1.350 cell, 292 formula, 225 kandidat promosi, 446 QC, 0 blocking error, dan tidak menulis database.
- Promosi: C/I.1, N/VI.1, Q/VIII.1, AB/VII.1, AK/III.1, AL/III.2, AM/XVIII.1, AO/IX.1, BB/II.2. Hanya owner ATR/BPN, bukan derived/feature/ambiguous.
- Tidak dipromosikan: Pemda/manual/Kantah; formula derived; MAP.1; deferred/definition pending; kolom pendukung tanpa indikator; XII.1/AN ambigu; blank/dash. Semua tetap staging/lineage atau no-data.
- QC wajib terdeteksi: 10 selisih APL ±1 ha, 2 teks NIB–NOP terbalik, dan 8 denominator RDTR nol. Formula cache hilang, region mismatch, indicator master hilang, ownership mismatch, dan invalid value juga memiliki jalur fail-closed/test.
- Test importer **6 PASS/90 assertions**. Full regression **35 PASS/471 assertions**, 0 FAIL/SKIPPED. Baseline Fase 2A–3A tetap lulus.
- Verifikasi kode: 15 file PHP berubah/baru lint PASS; Composer platform requirements, Artisan command discovery, route list, dan `git diff --check` PASS. Frontend tidak berubah sehingga npm build tidak dijalankan.
- Development final: 11 migration Ran; satu batch completed (ID 2), 25 row, 1.350 values, 446 import QC, satu audit, satu snapshot, 225 observation/revision draft, 25 wilayah, 9 indikator, 0 non-ATR, 0 missing lineage, dan 0 published value. Pengulangan command berstatus idempotent.
- Workbook SHA256 sama sebelum/sesudah import, tetap ignored dan untracked. Environment/credential tidak dibaca ke output atau diubah.

## File berubah Fase 3B

- Baru: migration `000006_create_excel_import_staging_tables.php`; model `ImportBatch`, `ImportRow`, `ImportValue`, `ImportQualityFlag`; `JawaBaratWorkbookReader`, `JawaBaratImportService`, `JawaBaratImportMapping`, command `ImportJawaBaratWorkbook`, dan `JawaBaratImportTest`.
- Diubah: `IndicatorDefinitionSeeder`, `ObservationRevision`, `DomainFoundationTest`, `ManualObservationApiTest`, `tools/domain-database.php`, serta empat dokumen fase.

## Catatan final Fase 3A

- Baseline tetap `c35006eacecaa84fc3fba667d90dd023ffa583cb`; working tree awal bersih. Partial work dilanjutkan tanpa memulai ulang. Tidak ada commit, push, fetch, atau akses remote.
- Migration baru: `000004_create_definition_and_workflow_tables` dan `000005_reconcile_observation_identity_and_definition`. `000004` tidak diedit setelah diterapkan. `000005` memulihkan identity observation yang menyertakan source dan membersihkan placeholder formula XIII.4 secara fail-fast/non-destruktif.
- Schema baru/berubah: `indicator_definitions`; FK definition/source serta `change_note` pada `observation_revisions`; `revision_status_events`; `published_values`; unique observation `(region,snapshot,indicator,source,dimension)`.
- Seeder definition idempotent menghasilkan 57 definition. MAP.1 tetap feature tanpa definition; XIII.4 tetap katalog, `definition_pending`, tanpa formula. Jumlah master tetap 5 role, 26 region, 5 owner, 6 source, 3 category, 7 submenu, dan 58 indikator/feature.
- Delapan endpoint `/api/domain` tersedia untuk katalog, observation terfilter tanggal/wilayah, draft manual, revision, submit, publish, reject, dan history. Semua memakai Sanctum Bearer dan middleware akun aktif.
- Source input manual diturunkan server-side dari ownership; payload client tidak boleh memilih source, actor, checksum, revision number, atau field internal. Pemda/Kantah dibatasi owner dan scope; scope provinsi mewarisi kabupaten/kota; admin BPN/viewer/role unknown/scope kosong ditolak menulis. Hanya super admin publish/reject.
- Revision value append-only, bernomor berurutan dalam transaksi dan row lock. `expected_revision` stale serta revision identik menghasilkan 409. Workflow `draft -> submitted -> published|rejected`; viewer hanya membaca published. Audit mencatat ID/status/action tanpa raw value, note, secret atau request headers.
- Value validator mencakup decimal, integer, percentage, year, status, boolean, text, dan range. Error input 422; tanggal tanpa definition tidak fallback. Threshold/formula baru dan quality flag tidak dikarang.
- Testing dibuktikan `APP_ENV=testing`, mysql, `dashboard_pertanahan_test`, bukan development. `migrate:fresh --seed` menjalankan 10 migration; seeder kedua tidak menambah baris. Identity/index, XIII.4, dan MAP.1 diperiksa langsung.
- Test akhir: ManualObservation **10 PASS/128 assertions**; Fase 2A **7 PASS/107 assertions**; full suite **29 PASS/376 assertions**, tanpa FAIL/SKIPPED setelah opt-in database test.
- Verifikasi: 26 file PHP lint PASS; Composer platform PASS; route inspection PASS; `git diff --check` PASS dengan peringatan normalisasi LF/CRLF saja. Frontend tidak berubah sehingga npm build tidak dijalankan.
- Development dibuktikan mysql `dashboard_pertanahan_dev`, 0 observation/revision sebelum perubahan. `php artisan migrate --seed --force` menerapkan `000005` non-destruktif; sesudahnya 10 migration Ran, metadata benar, dan observation/revision tetap 0.
- Ditunda: import Excel dan data aktual (3B), formula runtime/derived computation serta UI (fase berikutnya), kecamatan/desa, dan resolusi definisi XIII.4.

## File berubah Fase 3A

- Migration/seeder: `api/database/migrations/2026_09_06_000004_create_definition_and_workflow_tables.php`, `2026_09_06_000005_reconcile_observation_identity_and_definition.php`, `api/database/seeders/IndicatorDefinitionSeeder.php`, `DomainMasterDataSeeder.php`, dan `DatabaseSeeder.php`.
- Domain code: `Indicator.php`, `Observation.php`, `ObservationRevision.php`, model baru `IndicatorDefinition.php`, `PublishedValue.php`, `RevisionStatusEvent.php`; `DefinitionValueValidator.php`, `ManualObservationService.php`, `RevisionConflictException.php`.
- HTTP/API: `DomainCatalogueController.php`, `DomainObservationController.php`; lima request domain dan `StrictApiRequest.php`; `api/routes/api.php`.
- Test/tool: `ManualObservationApiTest.php`, `DomainFoundationTest.php`, dan `api/tools/domain-database.php`.
- Dokumentasi: `docs/04-database-plan.md`, `docs/progress.md`, dan `docs/HANDOFF.md`.

## Checkpoint Git lokal
- [x] Repository diverifikasi pada `C:/xampp/htdocs/dashboard-pertanahan`.
- [x] Identitas Git lokal terisi: `Joshua Paskah <supremiedrive@gmail.com>`.
- [x] `.env`, `.env.testing`, `vendor/`, `node_modules/`, `data/source/*.xlsx`, `data/source/*.xls`, database lokal, log/cache, dan file sesi runtime diabaikan.
- [x] Dry-run `git add --dry-run .` aman dan tidak menampilkan file sensitif atau artefak runtime.
- [x] Staging final akan mencakup project artefak relevan, dokumentasi, migration, seeder, test, dan lockfile tanpa secret.
- [x] Commit lokal akan dibuat dengan pesan `chore: checkpoint through phase 2b` dan tanpa push.
- [x] File remote tidak diakses; status `git remote -v` hanya untuk informasi lokal, bukan untuk menarik data apapun.
