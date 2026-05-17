<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MessageReadNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'link_id' => $this->message->link->id,
            'type' => 'message_read',
            'title' => 'メッセージが開封されました',
            'body' => "あなたが{$this->message->link->display_name}宛に送ったメッセージが開封されました",
            'url' => "/dashboard/messages/sent?message={$this->message->id}",
        ];
    }
}
