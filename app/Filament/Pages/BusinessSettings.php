<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BusinessSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Horários e regras';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static UnitEnum|string|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Horários e regras';

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
            'timezone' => $company->timezone ?: 'America/Sao_Paulo',
            'appointment_slot_minutes' => $company->appointment_slot_minutes ?? 60,
            'confirmation_hours' => $company->confirmation_hours ?? 24,
            'reactivation_months' => $company->reactivation_months ?? 6,
            'business_breaks' => $company->business_breaks ?: [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Horários de atendimento')->schema([
                CheckboxList::make('business_days')->label('Dias de atendimento')->options([
                    1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 7 => 'Domingo',
                ])->columns(3)->minItems(1)->required(),
                TextInput::make('business_starts_at_hour')->label('Início do expediente')->numeric()->integer()->minValue(0)->maxValue(23)->suffix('h')->required(),
                TextInput::make('business_ends_at_hour')->label('Fim do expediente')->numeric()->integer()->minValue(1)->maxValue(24)->suffix('h')->gt('business_starts_at_hour')->required(),
                Select::make('timezone')->label('Fuso horário')->options([
                    'America/Sao_Paulo' => 'Brasília (America/Sao_Paulo)',
                    'America/Fortaleza' => 'Fortaleza',
                    'America/Recife' => 'Recife',
                    'America/Bahia' => 'Bahia',
                    'America/Belem' => 'Belém',
                    'America/Manaus' => 'Manaus',
                    'America/Cuiaba' => 'Cuiabá',
                    'America/Campo_Grande' => 'Campo Grande',
                    'America/Porto_Velho' => 'Porto Velho',
                    'America/Boa_Vista' => 'Boa Vista',
                    'America/Rio_Branco' => 'Rio Branco',
                    'America/Noronha' => 'Fernando de Noronha',
                ])->searchable()->required(),
                Select::make('appointment_slot_minutes')->label('Intervalo da agenda')->options([
                    15 => '15 minutos',
                    30 => '30 minutos',
                    60 => '60 minutos',
                ])->required(),
                Repeater::make('business_breaks')->label('Intervalos bloqueados')->helperText('Ex.: almoço das 12h às 13h.')->schema([
                    TextInput::make('label')->label('Nome')->placeholder('Almoço'),
                    TextInput::make('start_hour')->label('Início (hora)')->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                    TextInput::make('start_minute')->label('Início (min)')->numeric()->integer()->minValue(0)->maxValue(59)->default(0)->required(),
                    TextInput::make('end_hour')->label('Fim (hora)')->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                    TextInput::make('end_minute')->label('Fim (min)')->numeric()->integer()->minValue(0)->maxValue(59)->default(0)->required(),
                ])->columns(5)->default([]),
            ]),
            Section::make('Regras de contato')->schema([
                TextInput::make('confirmation_hours')->label('Antecedência de confirmação')->numeric()->integer()->minValue(1)->maxValue(72)->suffix('horas')->helperText('Quantas horas antes do atendimento a tarefa de confirmação é criada.')->required(),
                TextInput::make('reactivation_months')->label('Meses para reativação')->numeric()->integer()->minValue(1)->suffix('meses')->helperText('Clientes sem atividade há pelo menos este tempo entram em campanhas e tarefas de reativação.')->required(),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['business_days'] = array_map('intval', $data['business_days']);
        $data['appointment_slot_minutes'] = (int) $data['appointment_slot_minutes'];
        $data['confirmation_hours'] = (int) $data['confirmation_hours'];
        $data['reactivation_months'] = (int) $data['reactivation_months'];

        auth()->user()->company->update([
            ...$data,
            'hours_configured_at' => now(),
        ]);

        Notification::make()->title('Horários e regras atualizados.')->success()->send();
    }
}
