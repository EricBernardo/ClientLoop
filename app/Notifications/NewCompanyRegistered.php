<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCompanyRegistered extends Notification
{
    use Queueable;

    public function __construct(public User $owner) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->owner->loadMissing('company');
        $company = $this->owner->company;

        return (new MailMessage)
            ->subject('Novo cadastro no ClientLoop')
            ->line('Uma empresa acabou de se cadastrar no ClientLoop.')
            ->line('Empresa: '.$company->name)
            ->line('Tipo de negócio: '.$company->vertical->label())
            ->line('Responsável: '.$this->owner->name)
            ->line('E-mail: '.$this->owner->email);
    }
}
