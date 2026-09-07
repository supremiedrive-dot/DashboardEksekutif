<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\IndicatorDefinition;
use Illuminate\Database\Seeder;

class IndicatorDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $formulas = [
            'I.2'=>['key'=>'apl_certified_percentage','expression'=>'100 * certified_area / total_apl','denominator'=>'I.1'],
            'I.3'=>['key'=>'apl_uncertified_percentage','expression'=>'100 - I.2'],
            'II.1'=>['key'=>'mapped_apl_area','expression'=>'I.1 - II.2'],
            'V.1'=>['key'=>'pad_land_total','expression'=>'V.2 + V.3'],
            'VI.3'=>['key'=>'nib_nop_gap','expression'=>'VI.1 - VI.2'],
            'VIII.2'=>['key'=>'mortgaged_certificate_percentage','expression'=>'100 * mortgaged_certificates / total_certificates'],
            'IX.2'=>['key'=>'rdtr_integrated_percentage','expression'=>'100 * integrated_rdtr / IX.1','zero_denominator'=>'no_data'],
            'IX.3'=>['key'=>'rdtr_not_integrated_percentage','expression'=>'100 - IX.2','zero_denominator'=>'no_data'],
            'XI.1'=>['key'=>'unregistered_asset_percentage','expression'=>'100 * uncertified_assets / total_assets'],
            'XIII.1'=>['key'=>'priority_service_percentage','expression'=>'100 * service_count / XIII'],
            'XIII.2'=>['key'=>'priority_service_percentage','expression'=>'100 * service_count / XIII'],
            'XIII.3'=>['key'=>'priority_service_percentage','expression'=>'100 * service_count / XIII'],
            'XIII.5'=>['key'=>'priority_service_percentage','expression'=>'100 * service_count / XIII'],
            'XIII.6'=>['key'=>'priority_service_percentage','expression'=>'100 * service_count / XIII'],
            'XIII.7'=>['key'=>'priority_service_percentage','expression'=>'100 * service_count / XIII'],
            'XIX.1'=>['key'=>'authorized_applicant_percentage','expression'=>'100 * XIX.2 / total_applications'],
            'XIX.3'=>['key'=>'direct_applicant_percentage','expression'=>'100 * XIX.4 / total_applications'],
        ];

        Indicator::query()->where('is_feature', false)->orderBy('id')->each(function (Indicator $indicator) use ($formulas) {
            $rules = match ($indicator->value_type) {
                'percentage' => ['type'=>'numeric', 'min'=>0, 'max'=>100],
                'integer' => ['type'=>'integer', 'min'=>0],
                'decimal' => ['type'=>'numeric'],
                'year' => ['type'=>'integer', 'format'=>'YYYY'],
                'status' => ['type'=>'status', 'allowed_values'=>$this->statuses($indicator->canonical_code)],
                'range' => ['type'=>'range'],
                default => ['type'=>$indicator->value_type],
            };
            $formula = $formulas[$indicator->canonical_code] ?? null;
            IndicatorDefinition::firstOrCreate(
                ['indicator_id'=>$indicator->id, 'version'=>1],
                ['valid_from'=>'2026-08-04', 'valid_to'=>null, 'value_type'=>$indicator->value_type,
                    'unit'=>$indicator->unit, 'validation_rules'=>$rules,
                    'formula_key'=>$formula['key'] ?? null, 'formula_metadata'=>$formula,
                    'is_active'=>true]
            );
        });
    }

    private function statuses(string $code): array
    {
        return match ($code) {
            'III.1', 'III.2', 'III.3' => ['ada', 'tidak_ada'],
            'XVII.1', 'XVII.2' => ['sudah', 'belum'],
            default => [],
        };
    }
}
