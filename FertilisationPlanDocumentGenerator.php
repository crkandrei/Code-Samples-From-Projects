<?php

namespace App\Models;


use App\Http\Traits\DateTrait;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class FertilisationPlanDocumentGenerator extends Model
{
    use HasFactory, DateTrait;


    public $table = "fertilisation_plan_document_rows";
    private float $organic_nitrogen_percentage;
    private const  MINERAL_NITROGEN_PERCENTAGE = 33.5;
    private const  TOTAL_PLANNED_NITROGEN = 170;
    private int $year;
    private array $excludingIntervals;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'physical_block',
        'parcel_number',
        'parcel_surface',
        'crop',
        'total_planned_nitrogen',
        'organic_type',
        'organic_quantity',
        'organic_nitrogen_in_kg',
        'mineral_type',
        'mineral_quantity',
        'mineral_nitrogen_in_kg',
        'total_applied_nitrogen',
        'date_of_application',
        'median_quantity_of_applied_nitrogen_in_kg',
    ];

    public function __construct($object = null)
    {
        parent::__construct();
        if ($object) {
            $this->document_id = $object->documentId;
            $this->year = $object->year;
            $this->physical_block = $object->physicalBlock;
            $this->parcel_number = $object->parcelNumber;
            $this->parcel_surface = $object->parcelSurface;
            $this->crop = $object->crop;
            $this->organic_type = ($object->organicTypeSheep == 'true' || $object->organicTypeCow == 'true') ? 'true' : 'false';
            $this->organic_nitrogen_percentage = ($object->organicTypeSheep == 'true') ? 0.83 : 0.55;
            $this->mineral_type = $object->mineralType;
            $this->date_of_application = $object->fertilisationDate ?? null;
            $this->total_planned_nitrogen = self::TOTAL_PLANNED_NITROGEN;

            $this->generate();
        }
    }

    public function generate()
    {
        $this->generateAppliedNitrogen();
        $this->generateOrganicNitrogenInKg();
        $this->generateMineralNitrogenInKg();
        $this->generateOrganicQuantity();
        $this->generateMineralQuantity();
        $this->generateExcludingIntervals();
        $this->generateDateOfApplication();
        $this->generateMedianQuantityOfAppliedNitrogenInKg();
        $this->save();
    }

    private function generateOrganicNitrogenInKg()
    {
        //If we have organic_type and Mineral Type ,randomise the numbers
        if ($this->organic_type != 'false' && $this->mineral_type != 'false') {
            $this->organic_nitrogen_in_kg = mt_rand($this->total_applied_nitrogen * 0.2 * 100, $this->total_applied_nitrogen * 0.4 * 100) / 100;
        }
        //If we have only organic_type organic_quantity = totalQuantity
        if ($this->organic_type != 'false' && $this->mineral_type == 'false') {
            $this->organic_nitrogen_in_kg = $this->total_applied_nitrogen;
        }
        //If we do not have organic_type organic_quantity = 0
        if ($this->organic_type == 'false') {
            $this->organic_nitrogen_in_kg = 0;
        }
    }

    private function generateMineralNitrogenInKg()
    {
        if ($this->mineral_type != 'false') {
            $this->mineral_nitrogen_in_kg = $this->total_applied_nitrogen - $this->organic_nitrogen_in_kg;
        } else {
            $this->mineral_nitrogen_in_kg = 0;
        }
    }

    private function generateOrganicQuantity()
    {
        $this->organic_quantity = number_format((($this->organic_nitrogen_in_kg * 100) / $this->organic_nitrogen_percentage) / 1000, 2, '.', '');
    }

    private function generateMineralQuantity()
    {
        $this->mineral_quantity = number_format((($this->mineral_nitrogen_in_kg * 100) / self::MINERAL_NITROGEN_PERCENTAGE) / 1000, 2, '.', '');
    }

    private function generateDateOfApplication()
    {
        if (strlen($this->date_of_application) < 2) {
            $this->date_of_application = ($this->generateRandomDate($this->excludingIntervals, $this->year))->isoFormat('DD/MM/YYYY');
        } else {
            $date = DateTime::createFromFormat('Y-m-d', trim($this->date_of_application));
            $this->date_of_application = $date->format('d/m/Y');
        }
    }

    private function generateAppliedNitrogen()
    {
        if (!$this->organic_type && !$this->mineral_type) {
            $this->total_applied_nitrogen = 0;

            return;
        }
        //Maximum number that can be generated is maximum nitrogen that can be applied minus 10% (nobody uses so much nitrogen)
        $maxAppliedNitrogenThatCanBeGenerated = ($this->parcel_surface * $this->total_planned_nitrogen) * 0.9;
        $minAppliedNitrogenThatCanBeGenerated = ($this->parcel_surface * $this->total_planned_nitrogen) * 0.3;

        $this->total_applied_nitrogen = mt_rand($minAppliedNitrogenThatCanBeGenerated * 100, $maxAppliedNitrogenThatCanBeGenerated * 100) / 100;
    }

    private function generateMedianQuantityOfAppliedNitrogenInKg()
    {
        $this->median_quantity_of_applied_nitrogen_in_kg = number_format((($this->total_applied_nitrogen) / $this->parcel_surface), 2, '.', '');
    }

    private function generateExcludingIntervals()
    {
        $this->excludingIntervals = [
            [
                "start" => new Carbon($this->year . '-11-05'),
                "end" => new Carbon($this->year . '-12-31'),
            ],
            [
                "start" => new Carbon($this->year . '-01-01'),
                "end" => new Carbon($this->year . '-03-25'),
            ],
        ];
    }

    /**
     * Get the user that owns the phone.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

}
