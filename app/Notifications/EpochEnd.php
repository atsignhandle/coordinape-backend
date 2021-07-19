<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use App\Helper\Utils;

class EpochEnd extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $unallocated_users, $epoch_num, $circle_name;
    public function __construct($epoch_num, $circle_name, $unallocated_users = [])
    {
        $this->unallocated_users = $unallocated_users;
        $this->epoch_num = $epoch_num;
        $this->circle_name = $circle_name;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return config('telegram.token') ? [TelegramChannel::class] : [];
    }

    public function toTelegram($notifiable=null)
    {
        $name = '_'.$this->circle_name.'_';
        $unalloc_users = $this->unallocated_users;
        $unalloc_str = '';
        foreach($unalloc_users as $user) {
            if($unalloc_str)
                $unalloc_str .= ', ';
            $user_name = Utils::cleanStr($user->name) ;
            $unalloc_str .= $user_name;
        }

        if($unalloc_str) {
            $unalloc_str = "Users that did not allocate any GIVE Tokens:\n" . $unalloc_str;
        }
//        else {
//            $unalloc_str = "All users has fully allocated all their GIVE tokens !";
//        }

        $app_domain = 'coordinape.me';
        $url = $app_domain== 'localhost:8000' ?
            'http://'.$app_domain."/api/$notifiable->id/csv" : 'https://'.$app_domain."/api/$notifiable->id/csv";
        $url .=  "?epoch=". $this->epoch_num;
        return TelegramMessage::create()
            // Markdown supported.
            ->content("$name epoch has just ended !\n$unalloc_str")
            ->button('Click to Download CSV', $url);
    }
    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
