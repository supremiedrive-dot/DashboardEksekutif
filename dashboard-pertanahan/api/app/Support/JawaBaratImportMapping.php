<?php

namespace App\Support;

final class JawaBaratImportMapping
{
    public const VERSION = 'jabar-2026-08-04-v1';
    public const SHEET = 'JAWA BARAT (4 Agst)';

    /** @return array<string, array{indicator:?string,header:string,promote:bool,scale?:string}> */
    public static function columns(): array
    {
        return [
            'A'=>self::m(null,'no'),
            'B'=>self::m(null,'dareah'),
            'C'=>self::m('I.1','luas apl',true),
            'D'=>self::m(null,'luas lahan bersertipikat'),
            'E'=>self::m(null,'blm bersertipikat'),
            'F'=>self::m('I.2','luas lahan bersertipikat',false,'ratio'),
            'G'=>self::m('I.3','luas lahan belum bersertipikat',false,'ratio'),
            'H'=>self::m('V.2','pbb'),
            'I'=>self::m(null,'pbb',false,'ratio'),
            'J'=>self::m('V.3','bphtb'),
            'K'=>self::m(null,'bphtb',false,'ratio'),
            'L'=>self::m('V.1','jumlah pbb'),
            'M'=>self::m(null,'status terkoneksi'),
            'N'=>self::m('VI.1','nib',true),
            'O'=>self::m('VI.2','nop'),
            'P'=>self::m(null,'keterangan'),
            'Q'=>self::m('VIII.1','ht',true),
            'R'=>self::m(null,'total sertipikat yang di ht'),
            'S'=>self::m(null,'total sertipikat tahun'),
            'T'=>self::m('VIII.2','sertifikat',false,'ratio'),
            'U'=>self::m('XII.1','sudah sertipikat'),
            'V'=>self::m(null,'luas'),
            'W'=>self::m(null,'nilai'),
            'X'=>self::m(null,'belum sertipikat'),
            'Y'=>self::m('XI.2','luas'),
            'Z'=>self::m('XI.3','nilai'),
            'AA'=>self::m('XI.1','belum sertipikat',false,'ratio'),
            'AB'=>self::m('VII.1','cakupan',true,'percent_points'),
            'AC'=>self::m(null,'luas belum'),
            'AD'=>self::m(null,'tahun'),
            'AE'=>self::m(null,'nilai rata rata znt'),
            'AF'=>self::m(null,'pks'),
            'AG'=>self::m(null,'tahun'),
            'AH'=>self::m(null,'luas wilayah kumuh'),
            'AI'=>self::m(null,'luas usulan lp2b'),
            'AJ'=>self::m(null,'ada lokasi kt'),
            'AK'=>self::m('III.1','pembentukan gtra',true),
            'AL'=>self::m('III.2','gtra aktif',true),
            'AM'=>self::m('XVIII.1','luas tora belum sertipikat',true),
            'AN'=>self::m('XVII.2','kp2b lp2b'),
            'AO'=>self::m('IX.1','jumlah perkada rdtr',true),
            'AP'=>self::m(null,'jumlah terintegrasi oss'),
            'AQ'=>self::m('IX.2','sudah',false,'ratio'),
            'AR'=>self::m('IX.3','belum',false,'ratio'),
            'AS'=>self::m(null,'nik'),
            'AT'=>self::m(null,'%',false,'percent_points'),
            'AU'=>self::m(null,'non nik'),
            'AV'=>self::m(null,'%',false,'percent_points'),
            'AW'=>self::m(null,'jumlah penduduk'),
            'AX'=>self::m(null,'jumlah sertipikat'),
            'AY'=>self::m(null,'belum kepemilikan tanah',false,'ratio'),
            'AZ'=>self::m(null,'status'),
            'BA'=>self::m(null,'jumlah kw 456'),
            'BB'=>self::m('II.2','luas kw456',true),
        ];
    }

    private static function m(?string $indicator, string $header, bool $promote = false, ?string $scale = null): array
    {
        return array_filter(compact('indicator', 'header', 'promote', 'scale'), fn ($value) => $value !== null);
    }
}
