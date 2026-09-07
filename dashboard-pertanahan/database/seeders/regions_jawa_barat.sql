-- Reference codes: https://sensus.bps.go.id/topik/tabular/sp2020/1/13/0
-- Reference data only; no metric values or users.
INSERT INTO regions (province_id,code,name,region_type) VALUES
((SELECT id FROM provinces WHERE code='32'),'3201','Kabupaten Bogor','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3202','Kabupaten Sukabumi','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3203','Kabupaten Cianjur','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3204','Kabupaten Bandung','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3205','Kabupaten Garut','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3206','Kabupaten Tasikmalaya','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3207','Kabupaten Ciamis','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3208','Kabupaten Kuningan','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3209','Kabupaten Cirebon','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3210','Kabupaten Majalengka','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3211','Kabupaten Sumedang','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3212','Kabupaten Indramayu','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3213','Kabupaten Subang','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3214','Kabupaten Purwakarta','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3215','Kabupaten Karawang','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3216','Kabupaten Bekasi','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3217','Kabupaten Bandung Barat','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3218','Kabupaten Pangandaran','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3271','Kota Bogor','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3272','Kota Sukabumi','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3273','Kota Bandung','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3274','Kota Cirebon','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3275','Kota Bekasi','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3276','Kota Depok','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3277','Kota Cimahi','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3278','Kota Tasikmalaya','kabupaten_kota'),
((SELECT id FROM provinces WHERE code='32'),'3279','Kota Banjar','kabupaten_kota')
ON DUPLICATE KEY UPDATE name=VALUES(name);

