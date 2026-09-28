<?php

namespace App\Filament\Super\Resources\Companies;

use App\Filament\Super\Resources\Companies\Pages\EditCompany;
use App\Filament\Super\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use App\Models\User;
use App\Support\InterfaceLabels;
use App\Support\SignupMonitor;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationLabel = 'Empresas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome')->required(),
            TextInput::make('slug')->label('Identificador')->required(),
            Select::make('status')->label('Situação')->options(['trial' => 'Período de teste', 'active' => 'Ativa', 'suspended' => 'Suspensa', 'cancelled' => 'Cancelada'])->searchable()->required(),
            TextInput::make('confirmation_hours')->label('Antecedência de confirmação')->numeric()->minValue(1)->maxValue(72),
            TextInput::make('reactivation_months')->label('Meses para reativação')->numeric()->minValue(1),
            TextEntry::make('owner_email')->label('E-mail do responsável')->state(fn (?Company $record): string => $record?->owner?->email ?? '—'),
            TextEntry::make('trial_ends_at')->label('Fim do teste')->state(fn (?Company $record): string => $record?->subscription?->ends_at?->format('d/m/Y') ?? '—'),
            TextEntry::make('month_usage')->label('Uso do mês')->state(fn (?Company $record): string => $record ? SignupMonitor::usageLabel($record) : '—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['owner', 'subscription.plan', 'currentUsage']))->columns([
            TextColumn::make('name')->label('Empresa')->searchable(),
            TextColumn::make('owner.name')->label('Responsável')->searchable(),
            TextColumn::make('owner.email')->label('E-mail')->searchable(),
            TextColumn::make('status')->label('Situação')->badge()->color(fn (?string $state): string => InterfaceLabels::companyStatusColor($state))->formatStateUsing(fn (?string $state): string => InterfaceLabels::companyStatus($state)),
            TextColumn::make('users_count')->label('Usuários')->counts('users'),
            TextColumn::make('subscription.plan.name')->label('Plano'),
            TextColumn::make('subscription.ends_at')->label('Fim do teste')->dateTime('d/m/Y'),
            TextColumn::make('month_usage')->label('Uso do mês')->state(fn (Company $record): string => SignupMonitor::usageLabel($record)),
            TextColumn::make('created_at')->label('Cadastro')->dateTime('d/m/Y'),
        ])->filters([
            SelectFilter::make('status')->label('Situação')->options(['trial' => 'Período de teste', 'active' => 'Ativa', 'suspended' => 'Suspensa', 'cancelled' => 'Cancelada']),
            Filter::make('trial_ending')->label('Teste acaba em 3 dias')->query(fn (Builder $query): Builder => SignupMonitor::trialEndingSoon($query)),
            Filter::make('unused')->label('Sem uso')->query(fn (Builder $query): Builder => SignupMonitor::unusedTrials($query)),
            Filter::make('quota')->label('Cota cheia')->query(fn (Builder $query): Builder => SignupMonitor::quotaExhausted($query)),
            Filter::make('recent')->label('Cadastros em 7 dias')->query(fn (Builder $query): Builder => SignupMonitor::signedUpRecently($query)),
        ])->recordActions([
            Action::make('trocarPlano')->label('Trocar plano')->color('info')->form([Select::make('plan_id')->label('Plano')->options(fn () => Plan::query()->pluck('name', 'id'))->searchable()->preload()->required()])->action(function (Company $record, array $data): void {
                $payload = [
                    'plan_id' => $data['plan_id'],
                    'status' => $record->status,
                    'starts_at' => now(),
                ];

                if ($record->status === 'trial') {
                    $payload['ends_at'] = now()->addDays(14);
                }

                CompanySubscription::withoutGlobalScopes()->updateOrCreate(['company_id' => $record->id], $payload);
            }),
            Action::make('promoverSuperadmin')
                ->label('Promover superadmin')
                ->color('warning')
                ->form([
                    Select::make('user_id')
                        ->label('Usuário da empresa')
                        ->options(fn (Company $record): array => $record->users()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Promover a superadmin')
                ->modalDescription('Esta pessoa passa a acessar o painel /platform.')
                ->action(function (Company $record, array $data): void {
                    $updated = User::query()
                        ->whereKey($data['user_id'])
                        ->where('company_id', $record->id)
                        ->update(['is_super_admin' => true]);

                    if ($updated) {
                        Notification::make()->success()->title('Usuário promovido a superadmin')->send();
                    } else {
                        Notification::make()->danger()->title('Usuário não encontrado nesta empresa')->send();
                    }
                }),
            Action::make('estenderTeste')->label('Estender teste')->color('info')->visible(fn (Company $record): bool => $record->status === 'trial')->requiresConfirmation()->modalHeading('Estender teste')->modalDescription('O teste ganha mais 7 dias.')->action(function (Company $record): void {
                SignupMonitor::extendTrial($record);
                Notification::make()->success()->title('Teste estendido por 7 dias')->send();
            }),
            Action::make('suspender')->label('Suspender')->color('danger')->visible(fn (Company $record) => $record->status === 'active')->requiresConfirmation()->action(fn (Company $record) => $record->update(['status' => 'suspended'])),
            Action::make('ativar')->label('Ativar')->color('success')->visible(fn (Company $record) => in_array($record->status, ['trial', 'suspended'], true))->action(fn (Company $record) => $record->update(['status' => 'active'])),
            EditAction::make()->color('info')->url(fn (Company $record) => self::getUrl('edit', ['record' => $record])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCompanies::route('/'), 'edit' => EditCompany::route('/{record}/edit')];
    }
}
