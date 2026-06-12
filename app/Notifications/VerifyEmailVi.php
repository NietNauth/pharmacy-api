<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class VerifyEmailVi extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Xác thực địa chỉ Email của bạn')
            ->greeting('Xin chào!')
            ->line('Vui lòng nhấn vào nút bên dưới để xác thực địa chỉ email của bạn.')
            ->action('Xác thực Email', $verificationUrl)
            ->line('Nếu bạn không tạo tài khoản, bạn không cần thực hiện thêm hành động nào.')
            ->salutation('Trân trọng, ' . config('app.name'));
    }

    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
