<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BusinessSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Horários de atendimento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static UnitEnum|string|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Horários de atendimento';

    protected static ?string $slug = 'business-settings';

    protected string $view = 'filament.pages.business-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $company = auth()->user()->company;

        $this->form->fill([
            'business_days' => $company->business_days ?: [1, 2, 3, 4, 5, 6],
            'business_starts_at_hour' => $company->business_starts_at_hour ?? 9,
            'business_ends_at_hour' => $company->business_ends_at_hour ?? 17,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            CheckboxList::make('business_days')->label('Dias de atendimento')->options([
                1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 7 => 'Domingo',
            ])->columns(3)->minItems(1)->required(),
            TextInput::make('business_starts_at_hour')->label('Início do expediente')->numeric()->integer()->minValue(0)->maxValue(23)->suffix('h')->required(),
            TextInput::make('business_ends_at_hour')->label('Fim do expediente')->numeric()->integer()->minValue(1)->maxValue(24)->suffix('h')->gt('business_starts_at_hour')->required(),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['business_days'] = array_map('intval', $data['business_days']);

        auth()->user()->company->update($data);

        Notification::make()->title('Horários de atendimento atualizados.')->success()->send();
    }
}
