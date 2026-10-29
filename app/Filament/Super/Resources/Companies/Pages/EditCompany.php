<?php

namespace App\Filament\Super\Resources\Companies\Pages;

use App\Filament\Super\Resources\Companies\CompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Excluir empresa permanentemente')
                ->modalDescription('Isto apaga clientes, agendamentos, campanhas, tarefas e demais dados da empresa. Os usuários da conta ficam sem empresa vinculada e não podem ser recuperados pelo sistema.')
                ->successRedirectUrl(CompanyResource::getUrl('index')),
        ];
    }
}
