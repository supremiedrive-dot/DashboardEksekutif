INSERT INTO roles (code, name, description) VALUES
('eksekutif', 'Eksekutif', 'Role untuk melihat ringkasan dashboard dan laporan eksekutif'),
('admin', 'Admin', 'Role untuk pengelolaan data dan konfigurasi operasional'),
('pemda', 'Pemda', 'Role untuk input data manual wilayah')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO data_sources (code, name, source_type, owner_name, is_active) VALUES
('excel', 'Excel Import', 'excel', 'Tim Data/Excel', 1),
('pemda_manual', 'Input Pemda Manual', 'pemda', 'Pemda', 1),
('kkp', 'KKP API', 'kkp', 'KKP/Pusdatin', 1),
('system', 'Sistem Internal', 'system', 'Dashboard Pertanahan', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), source_type = VALUES(source_type), owner_name = VALUES(owner_name), is_active = VALUES(is_active);

INSERT INTO provinces (code, name, is_active) VALUES
('32', 'Jawa Barat', 1),
('31', 'DKI Jakarta', 1),
('35', 'Jawa Timur', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

INSERT INTO indicator_groups (code, name, parent_group_id, sort_order) VALUES
('umum', 'Informasi Umum', NULL, 1),
('tematik_keuangan', 'Keuangan Daerah', NULL, 2),
('tematik_iklim', 'Iklim Investasi Daerah', NULL, 3),
('tematik_aset', 'Optimalisasi Aset Pemda', NULL, 4),
('layanan_pertanahan', 'Layanan Pertanahan', NULL, 5),
('tata_ruang', 'Aspek Tata Ruang', NULL, 6),
('peta_bhumi', 'Penampil Peta Dari Bhumi', NULL, 7)
ON DUPLICATE KEY UPDATE name = VALUES(name), parent_group_id = VALUES(parent_group_id), sort_order = VALUES(sort_order);

INSERT INTO indicators (group_id, code, name, short_name, unit, data_type, is_derived, formula_text, source_field_name, source_owner, is_active) VALUES
((SELECT id FROM indicator_groups WHERE code = 'umum'), 'apl_total', 'Total APL', 'APL Total', 'ha', 'decimal', 0, NULL, 'Luas APL (ha)', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'umum'), 'lahan_sertipikat', 'Luas Lahan Bersertipikat', 'Sertipikat', 'ha', 'decimal', 0, NULL, 'Luas lahan bersertipikat (ha)', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'umum'), 'lahan_belum_sertipikat', 'Luas Lahan Belum Bersertipikat', 'Belum Sertipikat', 'ha', 'decimal', 0, NULL, 'blm bersertipikat (ha)', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'umum'), 'kepemilikan_sertifikat_e', 'Informasi Kepemilikan E-Sertipikat', 'E-Sertipikat', 'percent', 'decimal', 0, NULL, 'Luas lahan bersertipikat (%)', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_keuangan'), 'pbb', 'PBB', 'PBB', 'rp', 'decimal', 0, NULL, 'PBB (Rp)', 'Pemda/Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_keuangan'), 'bphtb', 'BPHTB', 'BPHTB', 'rp', 'decimal', 0, NULL, 'BPHTB (Rp)', 'Pemda/Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_keuangan'), 'nib_nop_status', 'Status Koneksi NIB-NOP', 'Status NIB-NOP', 'status', 'string', 0, NULL, 'STATUS Terkoneksi', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_keuangan'), 'nib_bidang', 'NIB Bidang', 'NIB', 'count', 'decimal', 0, NULL, 'NIB (BIDANG)', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_keuangan'), 'nop', 'NOP', 'NOP', 'count', 'decimal', 0, NULL, 'NOP', 'Pemda', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_iklim'), 'hak_tanggungan_total', 'Total Hak Tanggungan', 'HT Total', 'rp', 'decimal', 0, NULL, 'HT (Rp)', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_iklim'), 'sertifikat_di_ht', 'Sertifikat Yang Dihitung HT', 'HT Sertifikat', 'count', 'integer', 0, NULL, 'TOTAL SERTIPIKAT YANG DI HT TAHUN 2025 (Bidang)', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_iklim'), 'rdtr_oss', 'RDTR dan Integrasi OSS', 'RDTR/OSS', 'count', 'integer', 0, NULL, 'RDTR OSS', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_iklim'), 'pkkpr', 'Penerbitan PKKPR', 'PKKPR', 'count', 'integer', 0, NULL, 'Penerbitan PKKPR', 'Pemda', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_aset'), 'aset_pemda', 'Aset Pemda', 'Aset Pemda', 'rp', 'decimal', 0, NULL, 'Aset Pemda (Bidang) data s.d. Maret 2026', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_aset'), 'aset_pemda_sudah_sertifikat', 'Aset Pemda Sudah Sertipikat', 'Aset Sudah Sertipikat', 'count', 'integer', 0, NULL, 'Sudah Sertipikat', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tematik_aset'), 'aset_pemda_belum_sertifikat', 'Aset Pemda Belum Sertipikat', 'Aset Belum Sertipikat', 'count', 'integer', 0, NULL, 'Belum Sertipikat', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'layanan_pertanahan'), 'layanan_prioritas', 'Intensitas Layanan Prioritas', 'Layanan Prioritas', 'count', 'integer', 0, NULL, 'Layanan Prioritas', 'Pemda', 1),
((SELECT id FROM indicator_groups WHERE code = 'layanan_pertanahan'), 'kelompok_pengguna', 'Kelompok Pengguna Layanan', 'Pengguna Layanan', 'count', 'integer', 0, NULL, 'Kelompok Pengguna', 'Pemda', 1),
((SELECT id FROM indicator_groups WHERE code = 'layanan_pertanahan'), 'durasi_layanan_prioritas', 'Durasi Layanan Prioritas', 'Durasi Prioritas', 'count', 'integer', 0, NULL, 'Durasi Layanan Prioritas', 'Pemda', 1),
((SELECT id FROM indicator_groups WHERE code = 'layanan_pertanahan'), 'durasi_pengukuran', 'Durasi Layanan Pengukuran', 'Durasi Pengukuran', 'count', 'integer', 0, NULL, 'Durasi Layanan Pengukuran', 'Pemda', 1),
((SELECT id FROM indicator_groups WHERE code = 'tata_ruang'), 'cakupan_znt', 'Cakupan ZNT', 'ZNT', 'percent', 'decimal', 0, NULL, 'Cakupan ZNT', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tata_ruang'), 'znt_luas_belum', 'Luas Belum ZNT', 'Luas Belum ZNT', 'ha', 'decimal', 0, NULL, 'Luas belum (ha)', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tata_ruang'), 'integrasi_kp2b_lp2b', 'Integrasi KP2B/LP2B dalam RTRW', 'KP2B/LP2B', 'status', 'string', 0, NULL, '87% KP2B/LP2B', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'tata_ruang'), 'pelepasan_kawasan_hutan', 'Pelepasan Kawasan Hutan', 'Pelepasan Hutan', 'count', 'integer', 0, NULL, 'PELEPASAN KAWASAN HUTAN', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'peta_bhumi'), 'kw456_jumlah', 'Jumlah KW456', 'KW456 Jml', 'count', 'integer', 0, NULL, 'JUMLAH KW 456', 'Excel', 1),
((SELECT id FROM indicator_groups WHERE code = 'peta_bhumi'), 'kw456_luas', 'Luas KW456', 'KW456 Luas', 'ha', 'decimal', 0, NULL, 'LUAS KW456 (Ha)', 'Excel', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), short_name = VALUES(short_name), unit = VALUES(unit), data_type = VALUES(data_type), is_derived = VALUES(is_derived), formula_text = VALUES(formula_text), source_field_name = VALUES(source_field_name), source_owner = VALUES(source_owner), is_active = VALUES(is_active);
