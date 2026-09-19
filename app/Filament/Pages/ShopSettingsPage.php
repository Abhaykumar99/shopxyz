<?php

namespace App\Filament\Pages;

use App\Enums\PrintFormat;
use App\Models\Setting;
use App\Support\ShopSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * The shop's own details, payment, delivery rules and print formats (ADR-013,
 * ADR-014). Saved as settings rows; `App\Support\ShopSettings` reads them and
 * falls back to `config/shop.php`, so the shop name is never hardcoded.
 */
class ShopSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Shop settings';

    protected static ?string $title = 'Shop settings';

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.pages.shop-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->getSchema('form')?->fill(Setting::map());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')->tabs([
                    Tab::make('The shop')->icon('heroicon-m-building-storefront')->schema([
                        Section::make('What customers see')
                            ->description('Used on the website, invoices, labels and messages. Never written into the code.')
                            ->columns(2)
                            ->schema([
                                TextInput::make('shop.name')->label('Shop name')->required()->maxLength(60),
                                TextInput::make('shop.tagline')->label('Tagline')->maxLength(120),
                                TextInput::make('shop.phone')->label('Phone')->maxLength(20),
                                TextInput::make('shop.whatsapp')->label('WhatsApp')->maxLength(20),
                                TextInput::make('shop.email')->label('Email')->email()->maxLength(120),
                                TextInput::make('shop.hours')->label('Opening hours')->maxLength(120),
                                Textarea::make('shop.address')->label('Address')->rows(2)->columnSpanFull(),
                                FileUpload::make('shop.logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->directory('shop')
                                    ->visibility('public')
                                    ->helperText('A square logo works everywhere. Without one we show the initials.'),
                            ]),
                    ]),

                    Tab::make('Payment')->icon('heroicon-m-qr-code')->schema([
                        Section::make('UPI')
                            ->description('Shown on the payment QR code the customer scans.')
                            ->columns(2)
                            ->schema([
                                TextInput::make('payment.upi_vpa')->label('UPI ID')->maxLength(60),
                                TextInput::make('payment.upi_payee_name')->label('Payee name')->maxLength(60),
                                TextInput::make('payment.cod_max_paise')
                                    ->label('Cash on delivery limit, in paise')
                                    ->numeric()
                                    ->helperText('Above this only UPI is offered. 2000000 is ₹20,000. Zero removes the limit.'),
                            ]),
                    ]),

                    Tab::make('Delivery')->icon('heroicon-m-truck')->schema([
                        Section::make('Charges and area')
                            ->columns(2)
                            ->schema([
                                TextInput::make('delivery.charge_paise')->label('Delivery charge, in paise')->numeric(),
                                TextInput::make('delivery.free_above_paise')->label('Free above, in paise')->numeric(),
                                TextInput::make('delivery.min_order_paise')->label('Minimum order, in paise')->numeric(),
                                TextInput::make('delivery.eta')->label('Delivery promise')->maxLength(120),
                                TextInput::make('delivery.area')->label('Area name')->maxLength(60),
                                TagsInput::make('delivery.pincodes')
                                    ->label('Pincodes served')
                                    ->placeholder('800001')
                                    ->columnSpanFull()
                                    ->helperText('Leave empty to accept every pincode.'),
                            ]),
                    ]),

                    Tab::make('Printing')->icon('heroicon-m-printer')->schema([
                        Section::make('Default formats')
                            ->description('The admin can still pick another format when printing.')
                            ->columns(2)
                            ->schema([
                                Select::make('print.label_format')
                                    ->label('Parcel label')
                                    ->options([
                                        PrintFormat::Thermal4x6->value => '4 × 6 inch thermal',
                                        PrintFormat::A5->value => 'A5',
                                        PrintFormat::A4->value => 'A4',
                                    ]),
                                Select::make('print.invoice_format')
                                    ->label('Invoice')
                                    ->options([
                                        PrintFormat::A4->value => 'A4',
                                        PrintFormat::A5->value => 'A5',
                                    ]),
                            ]),
                    ]),
                ])->persistTabInQueryString(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->icon('heroicon-m-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $values = $this->getSchema('form')?->getState() ?? [];

        foreach ($this->flatten($values) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        app()->forgetInstance(ShopSettings::class);
        app()->forgetScopedInstances();

        Notification::make()
            ->title('Settings saved')
            ->body('The website, invoices and labels use them straight away.')
            ->success()
            ->send();
    }

    /**
     * Turns the nested form state back into `shop.name` style keys.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && ! array_is_list($value)) {
                $flat += $this->flatten($value, $path);

                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }
}
