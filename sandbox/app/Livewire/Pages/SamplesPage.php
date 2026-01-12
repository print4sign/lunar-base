<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use App\Mail\SampleRequestConfirmation;
use App\Mail\SampleRequestReceived;
use App\Models\SampleRequest;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class SamplesPage extends Component
{
    use HasLocale;

    // Form fields
    public string $company_name = '';
    public string $contact_person = '';
    public string $street = '';
    public string $house_number = '';
    public string $postal_code = '';
    public string $city = '';
    public string $country = 'NL';
    public string $email = '';
    public array $selected_samples = [];

    public bool $submitted = false;

    // Available sample categories with materials
    public array $sampleCategories = [
        'doek' => [
            'label' => 'Doek',
            'materials' => [
                'neon-print' => 'Neon print',
                'quapro-promotioneel' => 'QuaPro Promotioneel',
                'doek-airtex' => 'Airtex',
                'backlit-polyester' => 'Backlit polyester',
                'backlitdoek' => 'Backlitdoek',
                'banner-510' => 'Banner 510',
                'banner-610' => 'Banner 610',
                'blackback' => 'Blackback',
                'blackback-soft' => 'Blackback Soft',
                'blackback-renew' => 'Blackback ReNew',
                'budget-banner' => 'Budget Banner',
                'canvas' => 'Canvas',
                'decotex' => 'Decotex',
                'dekostof' => 'Dekostof',
                'flag-ciclo' => 'Flag Ciclo',
                'flag' => 'Flag',
                'flag-longlife' => 'Flag Longlife',
                'flag-pet' => 'Flag PET',
                'lumitex' => 'Lumitex',
                'meshdoek' => 'Meshdoek',
                'multitex-pro' => 'MultiTexPro®',
                'polymesh' => 'Polymesh (lochfilet)',
                'propes-fr' => 'ProPES FR',
                'propes-outdoor' => 'ProPES Outdoor',
                'samba-backlit' => 'Samba Backlit (Lightbox)',
                'sanotex' => 'SanoTex',
                'soundmesh' => 'Soundmesh',
                'structex' => 'Structex',
                'velours' => 'Velours',
                'vloervinyl' => 'Vloervinyl',
                'walltex-pro' => 'WalltexPro®',
                'zwart-wit-banner' => 'Zwart-wit banner',
            ],
        ],
        'folie' => [
            'label' => 'Folie',
            'materials' => [
                'orajet-3951ra-pro' => 'Orajet 3951RA+ Pro',
                'slide-3m-ij280' => 'Slide3M™ IJ280',
                '3m-ij20' => '3M™ IJ20',
                '3m-ij40' => '3M™ IJ40',
                '3m-envision' => '3M™ Envision™',
                'pl50' => 'PL50',
                'asfaltsticker' => 'Asfaltsticker',
                'avery-mpi-1104-ea' => 'Avery MPI 1104 EA',
                'avery-mpi-1105-ea' => 'Avery MPI 1105 EA',
                'clearview' => 'Clearview',
                'dot-folie' => 'DOT folie',
                'gekkotex' => 'Gekkotex',
                'lintec' => 'Lintec',
                'magneetfolie' => 'Magneetfolie',
                'one-way-vision' => 'One way Vision Folie',
                'orajet' => 'Orajet®',
                'quapro-permanent' => 'QuaPro permanent',
                'quapro-promotioneel-folie' => 'QuaPro promotioneel',
                'roughmark' => 'Roughmark',
                'slagvaste-sticker' => 'Slagvaste sticker',
                'statisch-hechtend' => 'Statisch hechtende folie',
                'zandstraalfolie' => 'Zandstraalfolie',
            ],
        ],
        'plaat' => [
            'label' => 'Plaat',
            'materials' => [
                'akylite' => 'Akylite',
                'dibond' => 'Dibond',
                'dibond-butler' => 'Dibond Butler Finish',
                'displaykarton' => 'Displaykarton',
                'easyprint' => 'Easyprint',
                'evacast' => 'Evacast',
                'flexibel-pvc' => 'Flexibel PVC',
                'forex' => 'Forex®',
                'kanaalplaat' => 'Kanaalplaat (Polyprop)',
                'multiplex' => 'Multiplex',
                'pet-vilt' => 'PET Vilt',
                'plexiglas-helder' => 'Plexiglas Helder',
                'plexiglas-opaal' => 'Plexiglas Opaal',
                'polystyreen' => 'Polystyreen',
                're-board' => 'Re-board',
            ],
        ],
        'papier' => [
            'label' => 'Papier',
            'materials' => [
                'bluebackpaper' => 'Bluebackpaper',
                'hv-gesatineerd-135' => 'HV gesatineerd MC 135 g/m²',
                'hv-gesatineerd-170' => 'HV gesatineerd MC 170 g/m²',
                'hv-gesatineerd-250' => 'HV gesatineerd MC 250 g/m²',
                'hv-gesatineerd-300' => 'HV gesatineerd MC 300 g/m²',
                'hv-gesatineerd-400' => 'HV gesatineerd MC 400 g/m²',
                'hv-halfmat-135' => 'HV halfmat MC 135 g/m²',
                'hv-halfmat-170' => 'HV halfmat MC 170 g/m²',
                'hv-halfmat-250' => 'HV halfmat MC 250 g/m²',
                'hv-halfmat-300' => 'HV halfmat MC 300 g/m²',
                'hv-halfmat-400' => 'HV halfmat MC 400 g/m²',
                'hvo-wit-90' => 'HVO wit 90 g/m²',
                'hvo-wit-170' => 'HVO wit 170 g/m²',
                'natuurkarton-300' => 'Natuurkarton 300 g/m²',
                'natuurkarton-350' => 'Natuurkarton 350 g/m²',
                'posterpapier' => 'Posterpapier',
                'sulfaatkarton-260' => 'Sulfaatkarton 260 g/m²',
                'sulfaatkarton-300' => 'Sulfaatkarton 300 g/m²',
                'synaps-170' => 'Synaps 170 g/m² (watervaste poster)',
                'synaps-300' => 'Synaps 300 g/m²',
            ],
        ],
        'textiel' => [
            'label' => 'Textiel',
            'materials' => [
                'kendal' => 'Kendal',
                'mezo' => 'Mezo',
                'percal' => 'Percal',
            ],
        ],
        'wandbekleding' => [
            'label' => 'Wandbekleding',
            'materials' => [
                'veloursbehang' => 'Veloursbehang',
                'basicwall' => 'Basicwall',
                'erfurt-variovlies' => 'Erfurt Variovlies',
                'provinyl-fijn' => 'Provinyl fijn',
                'provinyl-textiel' => 'Provinyl textiel',
                'provlies-mat' => 'Provlies mat',
            ],
        ],
    ];

    protected $rules = [
        'company_name' => 'nullable|string|max:255',
        'contact_person' => 'nullable|string|max:255',
        'street' => 'required|string|max:255',
        'house_number' => 'required|string|max:20',
        'postal_code' => 'required|string|max:20',
        'city' => 'required|string|max:255',
        'country' => 'required|string|size:2',
        'email' => 'required|email|max:255',
        'selected_samples' => 'required|array|min:1|max:10',
    ];

    protected $messages = [
        'selected_samples.required' => 'Selecteer minimaal 1 sample.',
        'selected_samples.min' => 'Selecteer minimaal 1 sample.',
        'selected_samples.max' => 'Je kunt maximaal 10 samples bestellen.',
        'street.required' => 'Straatnaam is verplicht.',
        'house_number.required' => 'Huisnummer is verplicht.',
        'postal_code.required' => 'Postcode is verplicht.',
        'city.required' => 'Plaatsnaam is verplicht.',
        'email.required' => 'E-mailadres is verplicht.',
        'email.email' => 'Voer een geldig e-mailadres in.',
    ];

    public function mount(string $locale = 'nl'): void
    {
        $this->initializeLocale($locale);
    }

    public function toggleSample(string $sample): void
    {
        if (in_array($sample, $this->selected_samples)) {
            $this->selected_samples = array_values(array_diff($this->selected_samples, [$sample]));
        } else {
            if (count($this->selected_samples) < 10) {
                $this->selected_samples[] = $sample;
            }
        }
    }

    public function submit(): void
    {
        $this->validate();

        // 1. Save to database
        $sampleRequest = SampleRequest::create([
            'company_name' => $this->company_name ?: null,
            'contact_person' => $this->contact_person ?: null,
            'street' => $this->street,
            'house_number' => $this->house_number,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,
            'email' => $this->email,
            'selected_samples' => $this->selected_samples,
        ]);

        // Build sample labels lookup for emails
        $sampleLabels = $this->getSampleLabels();

        // 2. Send notification email to team
        $teamEmail = config('mail.sample_requests_to', 'info@drukhoek.nl');
        Mail::to($teamEmail)->queue(new SampleRequestReceived($sampleRequest, $sampleLabels));

        // 3. Send confirmation email to customer
        Mail::to($this->email)->queue(new SampleRequestConfirmation($sampleRequest, $sampleLabels));

        $this->submitted = true;
    }

    /**
     * Get a flat array of sample key => label mappings.
     */
    protected function getSampleLabels(): array
    {
        $labels = [];
        foreach ($this->sampleCategories as $category) {
            foreach ($category['materials'] as $key => $label) {
                $labels[$key] = $label;
            }
        }
        return $labels;
    }

    public function render()
    {
        return view('livewire.pages.samples-page')
            ->layout('layouts.storefront', ['title' => __('Gratis samples aanvragen')]);
    }
}
