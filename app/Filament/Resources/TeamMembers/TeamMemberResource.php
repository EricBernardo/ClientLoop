<?php

namespace App\Filament\Resources\TeamMembers;

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Models\User;
use App\Support\InterfaceLabels;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TeamMemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static UnitEnum|string|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return 'Equipe';
    }

    public static function getModelLabel(): string
    {
        return 'membro da equipe';
    }

    public static function getPluralModelLabel(): string
    {
        return 'equipe';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()?->company_id)
            ->where(function (Builder $query): void {
                $query->where('is_super_admin', false)->orWhereNull('is_super_admin');
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome')->required()->maxLength(255),
            TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('password')
                ->label('Senha')
                ->password()
                ->revealable()
                ->required(fn (?User $record): bool => $record === null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(fn (?User $record): ?string => $record ? 'Deixe em branco para manter a senha atual.' : null),
            Select::make('role')
                ->label('Função')
                ->options(InterfaceLabels::userRoles())
                ->searchable()
                ->default('attendant')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('role')->label('Função')->badge()->formatStateUsing(fn (?string $state): string => InterfaceLabels::userRole($state)),
            ])
            ->recordActions([
                EditAction::make()->color('info')->url(fn (User $record): string => self::getUrl('edit', ['record' => $record])),
                DeleteAction::make()->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ])
            ->emptyStateHeading('Nenhum membro na equipe')
            ->emptyStateDescription('Cadastre atendentes para acessar o painel da loja.')
            ->emptyStateActions([
                Action::make('create')->label('Novo membro')->url(static::getUrl('create')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamMembers::route('/'),
            'create' => CreateTeamMember::route('/create'),
            'edit' => EditTeamMember::route('/{record}/edit'),
        ];
    }
}
