<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DomainMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roles = [
                'super_admin' => 'Super Admin', 'admin_data_bpn' => 'Admin Data BPN',
                'operator_pemda' => 'Operator Pemda', 'operator_kantah' => 'Operator Kantah',
                'viewer_eksekutif' => 'Viewer Eksekutif',
            ];
            foreach ($roles as $code => $name) $this->put('roles', 'code', compact('code', 'name') + ['is_active' => true]);

            $ownerDefs = [
                'atr_bpn' => ['ATR/BPN atau KKP/Excel','government_bpn',false],
                'pemda' => ['Pemerintah Daerah','government_region',true],
                'kantah' => ['Kantor Pertanahan','land_office',true],
                'bhumi_external' => ['BHUMI / Eksternal','external',false],
                'deferred' => ['Ditunda / Belum Tersedia','deferred',false],
            ];
            $owners = [];
            foreach ($ownerDefs as $code => [$name,$type,$manual]) $owners[$code] = $this->put('data_owners', 'code', [
                'code'=>$code, 'name'=>$name, 'organization_type'=>$type,
                'allows_manual_input'=>$manual, 'is_active'=>true,
            ]);
            foreach ([
                ['excel_jawa_barat','atr_bpn','Excel Jawa Barat','excel'],
                ['kkp_atr_bpn','atr_bpn','KKP / Sistem ATR-BPN','kkp'],
                ['manual_pemda','pemda','Input Manual Pemda','manual'],
                ['manual_kantah','kantah','Input Manual Kantah','manual'],
                ['bhumi','bhumi_external','BHUMI','external'],
                ['no_data_deferred','deferred','Belum Tersedia','deferred'],
            ] as [$code,$owner,$name,$channel]) $this->put('data_sources', 'code', [
                'code'=>$code, 'data_owner_id'=>$owners[$owner], 'name'=>$name,
                'channel'=>$channel, 'is_active'=>true,
            ]);

            $province = $this->put('regions', 'internal_code', [
                'internal_code'=>'jawa-barat', 'parent_id'=>null, 'level'=>'province',
                'name'=>'Jawa Barat', 'bps_code'=>null, 'kemendagri_code'=>null, 'is_active'=>true,
            ]);
            foreach (explode('|', 'Kabupaten Bandung|Kabupaten Bandung Barat|Kabupaten Bekasi|Kabupaten Ciamis|Kabupaten Cianjur|Kabupaten Cirebon|Kabupaten Garut|Kabupaten Indramayu|Kabupaten Karawang|Kabupaten Kuningan|Kabupaten Majalengka|Kabupaten Purwakarta|Kabupaten Subang|Kabupaten Sukabumi|Kabupaten Sumedang|Kabupaten Tasikmalaya|Kota Bandung|Kota Banjar|Kota Bekasi|Kota Bogor|Kota Cimahi|Kota Cirebon|Kota Depok|Kota Sukabumi|Kota Tasikmalaya') as $name) {
                $this->put('regions', 'internal_code', [
                    'internal_code'=>'jabar-'.Str::slug($name), 'parent_id'=>$province,
                    'level'=>'regency_city', 'name'=>$name, 'bps_code'=>null,
                    'kemendagri_code'=>null, 'is_active'=>true,
                ]);
            }

            $categories = [];
            foreach ([['general','Informasi Umum',1],['thematic','Informasi Tematik',2],['map','Penampil Peta BHUMI',3]] as [$code,$name,$order]) {
                $categories[$code] = $this->put('categories', 'code', ['code'=>$code,'name'=>$name,'display_order'=>$order,'is_active'=>true]);
            }
            $submenus = [];
            foreach ([
                ['general','general','Informasi Umum',1], ['finance','thematic','Keuangan Daerah',1],
                ['investment','thematic','Iklim Investasi Daerah',2], ['assets','thematic','Optimalisasi Aset Pemda',3],
                ['services','thematic','Layanan Pertanahan',4], ['spatial','thematic','Aspek Tata Ruang',5],
                ['bhumi','map','Penampil Peta BHUMI',1],
            ] as [$code,$category,$name,$order]) $submenus[$code] = $this->put('submenus', 'code', [
                'code'=>$code, 'category_id'=>$categories[$category], 'name'=>$name,
                'display_order'=>$order, 'is_active'=>true,
            ]);

            $rows = <<<'DATA'
I.1|Total Luasan APL|decimal|Ha|atr_bpn|general
I.2|% Luasan APL Bersertifikat|percentage|%|atr_bpn|general
I.3|% Luasan APL Belum Bersertifikat|percentage|%|atr_bpn|general
II.1|Total Luasan APL Terpetakan|decimal|Ha|atr_bpn|general
II.2|Total Luasan APL Belum Terpetakan|decimal|Ha|atr_bpn|general
III.1|GTRA - Status Pembentukan|status||atr_bpn|general
III.2|GTRA - Dukungan APBN (2026)|status||atr_bpn|general
III.3|FPR - Status Pembentukan|status||pemda|general
III.4|FPR - Tahun Pembentukan|year|YYYY|pemda|general
IV.1|% E-Sertifikat|percentage|%|atr_bpn|general
XIX.1|Kuasa (PPAT/PPATS)|percentage|%|kantah|services
XIX.2|Kuasa PPAT/PPATS (jumlah berkas)|integer|Berkas|kantah|services
XIX.3|% Non Kuasa (Pemohon Langsung)|percentage|%|kantah|services
XIX.4|Non Kuasa - Pemohon Langsung (jumlah berkas)|integer|Berkas|kantah|services
V.1|Total PAD (PBB+BPHTB)|decimal|Rp|pemda|finance
V.2|Total PBB|decimal|Rp|pemda|finance
V.3|Total BPHTB|decimal|Rp|pemda|finance
VI.1|Total NIB (bidang)|integer|Bidang|atr_bpn|finance
VI.2|Total NOP (bidang)|integer|Bidang|pemda|finance
VI.3|GAP NIB-NOP (bidang)|integer|Bidang|deferred|finance
VII.1|% Cakupan Luas ZNT|percentage|%|atr_bpn|finance
VII.2|% Luas ZNT Detail|percentage|%|atr_bpn|finance
VII.3|Nilai Bidang Tanah (paling dominan)|range|Rp|atr_bpn|finance
VIII.1|Total Nilai Kredit|decimal|Rp|atr_bpn|investment
VIII.2|% Sertipikat di HT-kan|percentage|%|atr_bpn|investment
IX.1|Jumlah RDTR Terbit Perkada|integer|Perkada|atr_bpn|investment
IX.2|% RDTR Terintegrasi OSS|percentage|%|atr_bpn|investment
IX.3|% RDTR Belum Terintegrasi OSS|percentage|%|atr_bpn|investment
X.1|Total Persetujuan KKPR|integer|Dokumen|pemda|investment
X.2|Nilai Potensi Investasi|decimal|Rp|pemda|investment
XI.1|Aset Belum Sertipikat (%)|percentage|%|pemda|assets
XI.2|Aset Belum Sertipikat (Luas m²)|decimal|m²|pemda|assets
XI.3|Aset Belum Sertipikat (Nilai Rp)|decimal|Rp|pemda|assets
XII.1|Aset Tersertipikat (jumlah)|integer|Sertipikat|atr_bpn|assets
XII.2|Sebaran Aset - Kab/Kota (%)|percentage|%|atr_bpn|assets
XII.3|Sebaran Aset - Kecamatan (%)|percentage|%|deferred|assets
XII.4|Sebaran Aset - Desa/Kelurahan (%)|percentage|%|deferred|assets
XIII|Jumlah Berkas|integer|Berkas|kantah|services
XIII.1|Pengecekan %|percentage|%|kantah|services
XIII.2|SKPT %|percentage|%|kantah|services
XIII.3|HT-EL (%)|percentage|%|kantah|services
XIII.4|Roya (%)|percentage|%|kantah|services
XIII.5|Peralihan (%)|percentage|%|kantah|services
XIII.6|Pendaftaran SK (%)|percentage|%|kantah|services
XIII.7|Perubahan Hak (%)|percentage|%|kantah|services
XV.1|Pengecekan (durasi)|decimal|Hari|kantah|services
XV.2|SKPT (durasi)|decimal|Hari|kantah|services
XV.3|HT-EL (durasi)|decimal|Hari|kantah|services
XV.4|Roya (durasi)|decimal|Hari|kantah|services
XV.5|Peralihan (durasi)|decimal|Hari|kantah|services
XV.6|Pendaftaran SK (durasi)|decimal|Hari|kantah|services
XV.7|Perubahan Hak (durasi)|decimal|Hari|kantah|services
XVI.1|Waktu Tunggu Pengukuran|decimal|Hari|kantah|services
XVI.2|Durasi Pengukuran|decimal|Hari|kantah|services
XVII.1|Status Penerbitan SK KP2B/LP2B|status||pemda|spatial
XVII.2|Integrasi ke Dalam RTRW|status||pemda|spatial
XVIII.1|Status Belum Tersertipikat (Ha)|decimal|Ha|atr_bpn|spatial
MAP.1|Penampil Peta BHUMI|feature||bhumi_external|bhumi
DATA;
            $derived = array_flip(explode('|', 'I.2|I.3|II.1|V.1|VI.3|VIII.2|IX.2|IX.3|XI.1|XIII.1|XIII.2|XIII.3|XIII.5|XIII.6|XIII.7|XIX.1|XIX.3'));
            $manual = array_flip(explode('|', 'III.3|III.4|V.2|V.3|VI.2|X.1|X.2|XI.2|XI.3|XVII.1|XVII.2|XIII|XV.1|XV.2|XV.3|XV.4|XV.5|XV.6|XV.7|XVI.1|XVI.2|XIX.2|XIX.4'));
            foreach (preg_split('/\R/', trim($rows)) as $offset => $line) {
                [$code,$name,$type,$unit,$owner,$submenu] = explode('|', $line);
                $row = $offset + 5;
                $this->put('indicators', 'canonical_code', [
                    'canonical_code'=>$code, 'source_code'=>$code === 'MAP.1' ? 'XIX.1' : $code,
                    'source_row'=>$row, 'submenu_id'=>$submenus[$submenu], 'data_owner_id'=>$owners[$owner],
                    'name'=>$name, 'value_type'=>$type, 'unit'=>$unit ?: null, 'display_order'=>$row,
                    'is_derived'=>isset($derived[$code]), 'allows_manual_input'=>isset($manual[$code]),
                    'is_feature'=>$code === 'MAP.1', 'is_active'=>true,
                    'no_data_policy'=>'explicit_no_data',
                    'quality_status'=>$code === 'XIII.4' ? 'definition_pending' : 'mapped',
                ]);
            }
        });
    }

    private function put(string $table, string $key, array $values): int
    {
        $now = now();
        DB::table($table)->updateOrInsert([$key=>$values[$key]], $values + ['created_at'=>$now, 'updated_at'=>$now]);
        return (int) DB::table($table)->where($key, $values[$key])->value('id');
    }
}
