<?php

namespace App\Filament\Pages;

use App\Enums\CashEntryDirection;
use App\Enums\CashExpenseCategory;
use App\Filament\Concerns\LimitsToAutomotive;
use App\Filament\Forms\Components\BrlMoneyInput;
use App\Models\CashEntry;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class CashFlow extends Page implements HasForms
{
    use InteractsWithForms;
    use LimitsToAutomotive;

    protected static ?string $navigationLabel = 'Fluxo de caixa';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Fluxo de caixa';

    protected static ?string $slug = 'cash-flow';

    protected string $view = 'filament.pages.cash-flow';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->endOfMonth()->toDateString(),
            'expense_on' => now()->toDateString(),
            'category' => CashExpenseCategory::Other->value,
            'amount' => null,
            'notes' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Período')->schema([
                DatePicker::make('from')->label('De')->required()->live(),
                DatePicker::make('until')->label('Até')->required()->live(),
            ])->columns(2),
            Section::make('Nova saída')->schema([
                DatePicker::make('expense_on')->label('Data')->required(),
                Select::make('category')->label('Categoria')->options(CashExpenseCategory::options())->searchable()->required(),
                BrlMoneyInput::make('amount')->label('Valor')->required()->minValue(0.01),
                Textarea::make('notes')->label('Observação')->columnSpanFull(),
            ])->columns(2),
        ])->statePath('data');
    }

    public function addExpense(): void
    {
        $data = $this->form->getState();

        CashEntry::create([
            'direction' => CashEntryDirection::Expense,
            'category' => $data['category'],
            'amount' => $data['amount'],
            'occurred_on' => Carbon::parse($data['expense_on'])->toDateString(),
            'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
        ]);

        $this->data['amount'] = null;
        $this->data['notes'] = null;

        Notification::make()->success()->title('Saída lançada.')->send();
    }

    /**
     * @return array{entries: Collection<int, CashEntry>, income: string, expense: string, balance: string}
     */
    public function statement(): array
    {
        $from = Carbon::parse($this->data['from'] ?? now()->startOfMonth())->toDateString();
        $until = Carbon::parse($this->data['until'] ?? now()->endOfMonth())->toDateString();

        $entries = CashEntry::query()
            ->with('receipt.serviceOrder.customer')
            ->whereDate('occurred_on', '>=', $from)
            ->whereDate('occurred_on', '<=', $until)
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        $income = 0.0;
        $expense = 0.0;

        foreach ($entries as $entry) {
            if ($entry->direction === CashEntryDirection::Income) {
                $income += (float) $entry->amount;
            } else {
                $expense += (float) $entry->amount;
            }
        }

        return [
            'entries' => $entries,
            'income' => number_format($income, 2, ',', '.'),
            'expense' => number_format($expense, 2, ',', '.'),
            'balance' => number_format($income - $expense, 2, ',', '.'),
        ];
    }
}
