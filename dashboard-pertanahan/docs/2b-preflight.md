# Preflight Fase 2B — Keamanan, Schema Audit, dan Rencana Migration

2026-09-06. **Read-only assessment**, bukan implementasi atau perubahan database/kode.

## 1. PEMERIKSAAN KEAMANAN CREDENTIAL

### Status .env dan .env.testing
- ✅ **SAFE**: `api/.env` dan `api/.env.testing` terdaftar di `.gitignore` (Laravel default + root custom).
- ✅ **SAFE**: `git check-ignore` mengkonfirmasi keduanya diabaikan.
- ✅ **SAFE**: Kedua file tidak tercatat sebagai tracked files (`git ls-files` kosong).

### Status Credential di File Terlacak
- ✅ **SAFE**: Tidak ada password, hash (`$2y$`), atau APP_KEY exposure di `docs/*.md`, `api/phpunit.xml`, atau `api/tests/**`.
- ✅ **SAFE**: `phpunit.xml` hanya menyimpan nama database testing (`dashboard_pertanahan_test`), bukan credentials.
- ✅ **SAFE**: Dokumentasi mengacu lokasi secret tanpa nilai (contoh: "di `api/.env.testing`" bukan konten aktual).
- ✅ **SAFE**: `01-auth-api-audit.md` menyebutkan struktur password (hashing, validation) tanpa nilai sample.

**Rekomendasi keamanan Fase 2B:**
1. Jangan commit `.env` maupun `.env.testing` walaupun template/example tersedia.
2. Jangan log secret di validation rules, error response, atau audit trails.
3. Gunakan environment variables untuk production database credentials; jangan hardcode di config.

---

## 2. AUDIT SCHEMA TESTING VERSUS MIGRATION

### Schema Actual di dashboard_pertanahan_test (Setelah Fase 2A Verifikasi)

```
Field             Type                  Null  Key      Default             Extra
─────────────────────────────────────────────────────────────────────────────
id                BIGINT UNSIGNED       NO    PRI      NULL                auto_increment
role_id           SMALLINT UNSIGNED     YES   MUL      NULL
name              VARCHAR(150)          NO                                 
email             VARCHAR(180)          NO    UNI      NULL
password          VARCHAR(255)          NO                                 (MANUAL ADD)
password_hash     VARCHAR(255)          YES                                 (DOMAIN: original, no longer used)
is_active         TINYINT(1)            NO              1
created_at        TIMESTAMP             NO              current_timestamp()
updated_at        TIMESTAMP             YES             NULL                on update...
remember_token    VARCHAR(100)          YES                                 (MANUAL ADD)
email_verified_at TIMESTAMP             YES                                 (MANUAL ADD)
```

### Schema dari Laravel Migrations

**`0001_01_01_000000_create_users_table.php`:**
- id, name, email, email_verified_at (nullable), password, remember_token, timestamps

**`2026_09_03_133822_add_role_to_users_table.php`:**
- Menambah `role` VARCHAR(255) dengan default 'user'

### Discrepancies (Manual Alterations Not in Migration)

| Perbedaan | Aktual di Test | Migration | Penyebab Manual | Status |
|---|---|---|---|---|
| role_id (FK) | SMALLINT UNSIGNED/FK roles | role VARCHAR/string | Domain schema muncul dari seeding lama; migration hanya `role` | Incompatible; fase 2B harus normalisasi |
| password | VARCHAR(255) | string/hashed | Ditambah manual untuk test agar User::create() bisa menyimpan | Temporary fix; normalisasi phase 2B |
| password_hash | VARCHAR(255)/nullable | — | Kolom domain original; tidak dipakai Laravel | Redundant; dokumentasi saja atau cleanup phase cleanser |
| is_active | TINYINT | — | Domain schema, bukan Laravel | Must add to migration jika tetap |
| remember_token | VARCHAR(100)/nullable | rememberToken() | Ditambah manual | Redundant dengan Laravel native, tetap untuk domain compat |
| email_verified_at | TIMESTAMP/nullable | timestamp/nullable | Ditambah manual | Laravel standard, perlu di migration |

### Perubahan Manual Belum Reproducible  

Perubahan berikut dilakukan via `ALTER TABLE` tanpa migration formal di Fase 2A untuk enable test:

1. **`password` column**: `ALTER TABLE users ADD COLUMN password VARCHAR(255) DEFAULT '' AFTER email`
   - Diperlukan karena Laravel `User::create(['password'=>...])` mencari kolom `password`, bukan `password_hash`.
   - Status: Temporary workaround untuk test; phase 2B harus keputusan: masahkan password+password_hash menjadi satu field, atau pisahkan schema jelas.

2. **`role_id` nullable**: `ALTER TABLE users MODIFY role_id SMALLINT(5) UNSIGNED NULL DEFAULT NULL`
   - Diperlukan karena User factory tidak menyediakan role_id; test perlu create user tanpa constraint.
   - Status: Perlu migration resmi jika schema tetap role_id FK; atau keputusan: gunakan Laravel `role` string dengan relasi roles table terpisah.

3. **`password_hash` nullable**: `ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL DEFAULT NULL`
   - Diperlukan untuk test; domain schema original masih memiliki kolom ini.
   - Status: Apakah password_hash tetap untuk audit/migrasi identitas lama? Jika tidak, buang; jika ya, dokumentasikan ownership dan normalisasi.

4. **`remember_token`, `email_verified_at`**: `ALTER TABLE ... ADD COLUMN` untuk Laravel standard.
   - Status: Tambahkan ke migration jika tetap dipakai; test mengharapkan kedua kolom tersedia.

### Rekomendasi Fase 2B

1. **Normalisasi users table:**
   - Keputusan: Gunakan Laravel standard `password` (hashed), atau pertahankan kompatibilitas domain `password_hash`?
   - Jika hanya Laravel: Hapus `password_hash` dan normalisasi constraint.
   - Jika dual identity: Dokumentasikan, migrasi identity lama terpisah, jangan campur di production.

2. **Role management:**
   - Pilihan: `role` string (current Laravel) vs `role_id` FK (domain schema).
   - Jika FK: Buat migration `roles` table, migration relasi `user_roles`, buat model `Role`, update `User`.
   - Jika string: Tetap simple, tapi terbatas 1 role/user; definisikan enum/domain.

3. **Semua alterasi manual:** Konversi ke migration formal sebelum production.

---

## 3. PROPOSAL ROLE DAN WILAYAH

### Skema Role Bisnis (Proposal)

Berdasarkan mapping data-dictionary dan ownership indikator, proposal role berikut:

| Role Code | Nama | Tindakan Utama | Scope Wilayah | Ownership Indikator | Status |
|---|---|---|---|---|---|
| `super_admin` | Admin Sistem | Kelola user, role, akses, audit trail | Seluruh negara | Tidak ada (system-level) | Hanya setup/maintenance |
| `admin_data_bpn` | Admin Data ATR/BPN | Input/edit indikator ATR/BPN (KKP, E-Cert, APL, GAP, ZNT, TORA, RDTR), validasi, approve dengan evidence | 1 atau beberapa Kab/Kota | KKP/Excel: ATR/BPN | Master data, quality control |
| `operator_pemda` | Operator PEMDA | Input/edit indikator Pemda (FPR, KKPR, Aset Pemda, PAD manual) | Kab/Kota asal Pemda | Pemda | Permintaan input berkelanjutan |
| `operator_kantah` | Operator Kantah | Input/edit indikator Kantah (PBB, BPHTB, layanan prioritas, durasi) via form/API | Kab/Kota asal Kantah | Kantah | Integrasi KKP/API |
| `viewer_eksekutif` | Viewer Eksekutif | Tampilkan dashboard read-only; no export/download advanced | Kab/Kota target + parent provinsi | Tidak ada (read-only) | UI stakeholder |

### Ownership Indikator (Matrix)

```
Indikator / Sumber          | Admin_Data_BPN | Operator_Pemda | Operator_Kantah | Viewer | Akses Default
────────────────────────────┼────────────────┼────────────────┼─────────────────┼────────┼──────────────
I.1–I.3 (APL)               | Read+Write     | —              | —               | Read   | Deny
II.1–II.2 (KW456)           | Read+Write     | —              | —               | Read   | Deny
III.1–III.2 (GTRA)          | Read+Write     | —              | —               | Read   | Deny
III.3–III.4 (FPR)           | —              | Read+Write     | —               | Read   | Deny (Pemda input)
IV.1 (E-Cert)               | Read+Write     | —              | —               | Read   | Deny
V.2 (PBB)                   | —              | —              | Read+Write      | Read   | Deny (Kantah/Pemda)
V.3 (BPHTB)                 | Read+Write     | —              | —               | Read   | Deny
VI.1–VI.3 (NIB/NOP/GAP)     | Read+Write     | —              | —               | Read   | Deny
VII.1–VII.2 (ZNT)           | Read+Write     | —              | —               | Read   | Deny
VIII.1–VIII.2 (Kredit/HT)   | Read+Write     | —              | —               | Read   | Deny
X (KKPR)                    | —              | Read+Write     | —               | Read   | Deny
XI.1–XI.3 (Aset Pemda)      | —              | Read+Write     | —               | Read   | Deny
XII (Aset KKP)              | —              | —              | Read+Write      | Read   | Deny
XIII (Layanan Prioritas)    | —              | —              | Read+Write      | Read   | Deny
XV–XVI (Durasi Layanan)     | —              | —              | Read+Write      | Read   | Deny
XVII (KP2B/LP2B)            | —              | Read+Write     | —               | Read   | Deny
XVIII (TORA)                | Read+Write     | —              | —               | Read   | Deny
XIX (Kuasa, Persalinan)     | —              | —              | Read+Write      | Read   | Deny
XX (Kepemilikan E-Cert)     | Read+Write     | —              | —               | Read   | Deny
MAP.1 (Feature Peta BHUMI)  | —              | —              | —               | Read   | Deny (placeholder)
```

**Keterangan:**
- `—` = Role tidak memiliki tanggung jawab indikator.
- `Deny` = Default access control: permission hanya jika role + wilayah + indikator permission explicit.
- Wilayah scope berlaku per user via `user_region_scopes` table.

### Keputusan Domain yang Masih Pending

1. **Pemda vs Kantah untuk PBB/BPHTB:** Mapping menyebutkan Excel awal (ATR/BPN owner), lalu manual Pemda. Fase 2B perlu: siapa input primary di production? Workflow approval?
2. **Layanan Prioritas XIII:** Adalah tanggung jawab mana, Kantah atau Pemda? Formula r43–r49 mengacu XIII; sumber XIII dari KKP Kantah.
3. **Aset Pemda khusus Hak Pakai (XII):** Owner adalah Kantah atau Pemda? Mapping ambigu (Sistem ATR/BPN bukan sumber final).
4. **Viewport data sebelum approval:** Bolehkah Viewer melihat draft/pending indikator, atau hanya approved snapshot?

---

## 4. RENCANA MIGRATION FASE 2B

### Urutan Migrasi Minimal

1. **Normalisasi users table** (jika belum)
   - Rename/consolidate `password` ++ `password_hash`.
   - Jadikan `is_active` standard column jika tetap dipakai.
   - Tambahkan `is_active: default 1` jika belum di migration.

2. **roles table**
   - id, code (UNIQUE), name, description, created_at, updated_at.
   - Enum: super_admin, admin_data_bpn, operator_pemda, operator_kantah, viewer_eksekutif.

3. **user_roles table**
   - user_id FK users, role_id FK roles.
   - UNIQUE(user_id, role_id); ON DELETE CASCADE.
   - Catatan: Fase ini satu role/user; multiple roles perlu design terpisah.

4. **regions table**
   - id, parent_id FK self (nullable), level ENUM (country/province/regency/city/district), name, code_bps nullable, code_kemendagri nullable, valid_from DATE, valid_to DATE nullable.
   - UNIQUE per authority/version/code.
   - Index: (parent_id, level), (level), (code_bps), (code_kemendagri).

5. **region_identifiers table (opsional Fase 2B, bisa 2C)**
   - region_id FK regions, authority VARCHAR(30), code VARCHAR(32), version VARCHAR(30), valid_from/to.
   - UNIQUE(authority, version, code) untuk menghindari duplikasi kode antar sumber.

6. **user_region_scopes table**
   - user_id FK users, region_id FK regions, permission VARCHAR(30) (read/write/approve).
   - UNIQUE(user_id, region_id, permission); ON DELETE CASCADE.
   - Index: (region_id, user_id), (user_id, permission).

7. **data_owners table** (opsional, bisa inline atau Fase 2C)
   - id, code (UNIQUE), name (ATR/BPN, Pemda, Kantah).

8. **indicators table**
   - id, code UNIQUE, name, data_type ENUM, unit, owner_id FK data_owners, is_active BOOL.
   - Index: (code), (owner_id, is_active).

9. **reporting_snapshots table**
   - id, as_of_date DATE UNIQUE, reporting_period_label nullable.
   - Index: (as_of_date).

10. **observation_revisions table** (atau simplified `observations` versi awal)
    - id, region_id FK regions, snapshot_id FK reporting_snapshots, indicator_id FK indicators, revision, input_method, value_* columns (decimal/int/text), status, created_by FK users, created_at.
    - UNIQUE per (region, snapshot, indicator); track revision history dengan status draft/submitted/approved.
    - Index: (indicator_id, snapshot_id), (region_id, snapshot_id), (status, created_at).

11. **quality_flags table** (opsional early, bisa Fase 2C)
    - observation_revision_id FK, rule_code, severity, evidence JSON.

### Catatan Migration Order

- Mulai dengan user role management (roles, user_roles).
- Segera ikuti regions untuk scoping.
- Indicators dan observations bergantung pada semuanya; buat last.
- Foreign keys, indices, dan constraints diapply per migrasi, jangan bulk di akhir.
- Setiap migration: write, test dry-run pada test DB, validate constraint sebelum production.

---

## 5. GAP DOKUMENTASI VERSUS IMPLEMENTASI

**Belum tercakup Fase 2A, butuh keputusan Fase 2B:**

1. **Access Control Policy:** Ketika user login, sistem harus cek: role + wilayah + indikator permission. Buat Gate/Policy untuk setiap endpoint membaca/menulis indikator.

2. **Wilayah Scope Inheritance:** Jika user scope ke provinsi, apakah auto-include semua kab/kota, atau explicit per region?

3. **Status Workflow:** Draft → Submitted → Approved. Siapa bisa approve? Role approval berbeda data-owner?

4. **Audit Trail:** Jangan lupa log user, action, old value, new value, reason, timestamp di setiap perubahan.

5. **Validation Rules:** Per indicator per owner (contoh: PBB Rp > 0, persentase 0–100, durasi hari antara 1–365).

---

## 6. STATUS KESIAPAN FASE 2B

| Aspek | Status | Blocker Implementasi |
|---|---|---|
| Security credentials | ✅ PASS | Tidak ada |
| Schema normalisasi | ⚠️ PARTIAL | Normalize password/password_hash; role string vs role_id |
| Role proposal | ✅ READY | Perlu approval: PBB/BPHTB ownership, layanan prioritas owner |
| Region/wilayah model | ⚠️ PARTIAL | Migration ready; definisi hierarki (kab/kota ke kecamatan/desa) perlu input domain |
| Access control design | ⚠️ PARTIAL | Matrix role × indicator × permission siap; perlu Policy Laravel + test |
| Reporting snapshot | ⚠️ PARTIAL | as_of_date structure decided; implementasi observation_revisions siap |
| Mapping reconciled | ✅ READY | Seluruh 58 entri kamus, 57 indicator + MAP.1 tervalidasi vs Excel Jawa Barat |

### Keputusan Masih Pending untuk Fase 2B

1. **Normalisasi password:** Mana: satu field Laravel standard, atau tetap dual password/password_hash untuk domain compat?
2. **Role management:** String role (current) vs role_id FK (recommend)?
3. **Wilayah granularity:** Mulai dari Kab/Kota, atau langsung rinci ke kecamatan/desa/urban_village?
4. **PBB owner:** Kantah, Pemda, atau Excel awal lalu manual?
5. **Layanan prioritas owner:** Kantah, Pemda, atau keduanya?
6. **Aset Pemda Hak Pakai:** ATR/BPN vs Pemda input?

---

## 7. REKOMENDASI

✅ **Fase 2B Siap untuk Codex:**
- Schema normalisasi jelas (pilih opsi untuk password/role).
- Role matrix dan indicator ownership terdefinisi (pending approval PBB/BPHTB).
- Migration order logical dan callable.
- Keamanan credential proven aman.
- Test database isolated dan verified setelah Phase 2A.

⚠️ **Bergantung pada keputusan domain:**
1. Siapa yang input PBB/BPHTB, Kantah atau Pemda?
2. Role string vs role_id; jika FK, mulai migration roles table.
3. Wilayah sample dari Jawa Barat (25 kab/kota) saja atau terinci sub-level?

🛑 **Tidak dimulai Fase 2B sampai:**
1. Keputusan domain (PBB owner, role design, wilayah level) disetujui.
2. Baseline schema users normalisasi disetujui (password strategy).
3. Production database ready (jika belum ada).

Dokumen ini tetap read-only dan bukan perubahan. Fase 2B implementasi dimulai setelah approval pengguna.

---

**Verifikasi Akhir:**
- File ini baru, jangan di-push git sampai approval.
- Semua dokumentasi progress sudah update di progress.md.
- Test database testing (dashboard_pertanahan_test) berdiri sendiri, development (dashboard_pertanahan_dev) tetap aman.

## Hasil pelaksanaan Fase 2B (2026-09-06)

Keputusan pending preflight diselesaikan oleh instruksi pengguna: password tunggal `password`; roles+pivot; level province/regency_city; PAD/PBB/BPHTB Pemda; layanan Kantah; sebaran aset KKP/Excel. Preflight lama tetap dipertahankan sebagai catatan sebelum keputusan.

`.env` dan `.env.testing` terbukti ignored dan tidak tracked. `phpunit.xml` tidak memuat password atau APP_KEY. Koneksi efektif dibuktikan tanpa menampilkan credential: local/mysql/`dashboard_pertanahan_dev` dan testing/mysql/`dashboard_pertanahan_test`, keduanya MariaDB 10.4.27. Percobaan awal testing sempat gagal 1045 lalu berhasil setelah konfigurasi privat tersedia; tidak ada DDL dijalankan sebelum isolasi berhasil.

Testing dibangun ulang dua kali dengan `migrate:fresh --seed`; run final menjalankan 8 migration sukses. Seeder kedua tidak mengubah jumlah. Seluruh suite final: 19 test, 244 assertion, PASS; subset auth Fase 2A: 7 test, 107 assertion, PASS; subset domain: 9 test, 127 assertion, PASS; legacy normalization: 1 test, 8 assertion, PASS. Test legacy benar-benar menambah kolom sementara pada DB testing, memigrasikan hash/role, membuktikan unknown deny, lalu mengembalikan schema kanonis.

Development sebelum migration terbukti tidak memiliki tabel/data. Setelah seluruh test lulus, dijalankan `php artisan migrate --seed --force` (tanpa fresh/wipe). Delapan migration status Ran. Hasil kedua database: role 5, region 26, owner 5, source 6, category 3, submenu 7, catalogue record 58; users, snapshots, observations dan revisions 0. Tidak ada production database dan tidak ada tindakan production.
