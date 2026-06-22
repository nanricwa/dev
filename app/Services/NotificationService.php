<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * 同一通知が既に送信済みかチェック（重複防止）
     */
    private function alreadySent(int $bookingId, string $channel, string $type, ?int $userId = null): bool
    {
        $query = NotificationLog::where('booking_id', $bookingId)
            ->where('channel', $channel)
            ->where('type', $type)
            ->where('status', 'sent')
            ->where('created_at', '>=', now()->subHours(1));

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id');
        }

        return $query->exists();
    }

    public function sendBookingConfirmation(Booking $booking): void
    {
        if ($booking->isGuest()) {
            $this->sendGuestBookingApproved($booking);
            return;
        }

        $user = $booking->user;
        $consultant = $booking->consultant;
        $consultantProfile = $consultant->consultantProfile;
        $date = $booking->booking_date->format('Y年m月d日');
        $time = substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5);
        $meetingUrl = $booking->meeting_url ?: ($consultantProfile?->meeting_url ?? null);

        $subject = '【予約確定】コンサルティング予約のお知らせ';
        $content = "{$user->name}様\n\n"
            . "コンサルティングの予約が確定しました。\n\n"
            . "■ コンサルタント: {$consultant->name}\n"
            . "■ 日時: {$date} {$time}\n"
            . "■ ステータス: {$booking->status}\n"
            . ($meetingUrl ? "■ ミーティングURL: {$meetingUrl}\n" : '')
            . ($booking->notes ? "■ 備考: {$booking->notes}\n" : '')
            . "\nよろしくお願いいたします。";

        $this->send($user, $booking, 'booking_confirmed', $subject, $content);

        // Also notify consultant
        $consultantContent = "{$consultant->name}様\n\n"
            . "コンサルティングの新しい予約が入りました。\n\n"
            . "■ 予約者: {$user->name}\n"
            . "■ メール: {$user->email}\n"
            . "■ 日時: {$date} {$time}\n"
            . "■ ステータス: {$booking->status}\n"
            . ($meetingUrl ? "■ ミーティングURL: {$meetingUrl}\n" : '')
            . ($booking->notes ? "■ 備考: {$booking->notes}\n" : '');

        $this->send($consultant, $booking, 'booking_confirmed', $subject, $consultantContent);

        // System Room ID notification
        $defaultCwMsg = "新しい予約が入りました。\n■ 予約者: {$user->name}\n■ コンサルタント: {$consultant->name}\n■ 日時: {$date} {$time}"
            . ($meetingUrl ? "\n■ ミーティングURL: {$meetingUrl}" : '')
            . ($booking->notes ? "\n■ 備考: {$booking->notes}" : '');
        $this->sendSystemChatwork($booking, 'chatwork_booking_confirm_message', $defaultCwMsg);
    }

    private function sendGuestBookingApproved(Booking $booking): void
    {
        $consultant = $booking->consultant;
        $consultantProfile = $consultant->consultantProfile;
        $date = $booking->booking_date->format('Y年m月d日');
        $time = substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5);
        $dateTime = "{$date} {$time}";
        $guestName = $booking->guest_name ?? '';
        $consultantName = $consultant->name;
        $meetingUrl = $booking->meeting_url ?: ($consultantProfile?->meeting_url ?? '');

        // Email subject (custom or default)
        $customSubject = SystemSetting::get('booking_confirm_email_subject', '');
        $subject = $customSubject
            ? $this->replacePlaceholders($customSubject, $guestName, $dateTime, $consultantName, $meetingUrl, '')
            : '【予約確定】個別相談のご予約が確定しました';

        // Email body
        $customEmailBody = SystemSetting::get('booking_confirm_email_body', '');
        if ($customEmailBody) {
            $content = $this->replacePlaceholders($customEmailBody, $guestName, $dateTime, $consultantName, $meetingUrl, '');
        } else {
            $content = "{$guestName}様\n\n"
                . "個別相談のご予約が確定しました。\n\n"
                . "■ 日時: {$dateTime}\n"
                . "\nよろしくお願いいたします。";
        }

        $this->sendGuestEmail($booking, $subject, $content);

        // LINE message (separate from email)
        $customLineMessage = SystemSetting::get('booking_confirm_line_message', '');
        if ($customLineMessage) {
            $lineContent = $this->replacePlaceholders($customLineMessage, $guestName, $dateTime, $consultantName, $meetingUrl, '');
        } else {
            $lineContent = "{$guestName}様\n個別相談のご予約が確定しました。\n■ 日時: {$dateTime}";
        }

        if ($booking->guest_line_user_id) {
            try {
                $lineService = app(LineNotificationService::class);
                $lineService->pushMessage($booking->guest_line_user_id, $lineContent);
            } catch (\Exception $e) {
                Log::error('Failed to send LINE message to guest', ['error' => $e->getMessage()]);
            }
        }

        // Also notify consultant
        $consultantContent = "{$consultant->name}様\n\n"
            . "個別相談の新しい予約が入りました。\n\n"
            . "■ お客様名: {$guestName}\n"
            . "■ メール: {$booking->guest_email}\n"
            . "■ 電話番号: {$booking->guest_phone}\n"
            . "■ 日時: {$date} {$time}\n"
            . ($meetingUrl ? "■ ミーティングURL: {$meetingUrl}\n" : '')
            . ($booking->notes ? "■ 相談内容: {$booking->notes}\n" : '');

        $this->send($consultant, $booking, 'booking_confirmed', $subject, $consultantContent);

        // System Room ID notification
        $defaultCwMsg = "新しい予約が入りました。\n■ 予約者: {$guestName}\n■ コンサルタント: {$consultantName}\n■ 日時: {$dateTime}"
            . ($meetingUrl ? "\n■ ミーティングURL: {$meetingUrl}" : '')
            . ($booking->notes ? "\n■ 備考: {$booking->notes}" : '');
        $this->sendSystemChatwork($booking, 'chatwork_booking_confirm_message', $defaultCwMsg);
    }

    public function sendBookingCancelled(Booking $booking): void
    {
        $consultant = $booking->consultant;
        $consultantProfile = $consultant->consultantProfile;
        $date = $booking->booking_date->format('Y年m月d日');
        $time = substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5);
        $dateTime = "{$date} {$time}";
        $bookerName = $booking->bookerName();
        $consultantName = $consultant->name;
        $meetingUrl = $booking->meeting_url ?: ($consultantProfile?->meeting_url ?? '');

        // Email subject (custom or default)
        $customSubject = SystemSetting::get('cancel_notification_email_subject', '');
        $subject = $customSubject
            ? $this->replacePlaceholders($customSubject, $bookerName, $dateTime, $consultantName, $meetingUrl, '')
            : '【キャンセル】コンサルティング予約のキャンセル';

        if ($booking->isGuest()) {
            // Email body (custom or default)
            $customEmailBody = SystemSetting::get('cancel_notification_email_body', '');
            if ($customEmailBody) {
                $content = $this->replacePlaceholders($customEmailBody, $bookerName, $dateTime, $consultantName, $meetingUrl, '');
            } else {
                $content = "{$bookerName}様\n\n"
                    . "以下の予約がキャンセルされました。\n\n"
                    . "■ 日時: {$date}\n"
                    . ($booking->cancel_reason ? "■ 理由: {$booking->cancel_reason}\n" : '');
            }

            $this->sendGuestEmail($booking, $subject, $content, 'booking_cancelled');

            // LINE message for guest (separate from email)
            $customLineMessage = SystemSetting::get('cancel_notification_line_message', '');
            if ($customLineMessage) {
                $lineContent = $this->replacePlaceholders($customLineMessage, $bookerName, $dateTime, $consultantName, $meetingUrl, '');
            } else {
                $lineContent = "{$bookerName}様\n以下の予約がキャンセルされました。\n■ 日時: {$date}";
            }

            if ($booking->guest_line_user_id) {
                try {
                    $lineService = app(LineNotificationService::class);
                    $lineService->pushMessage($booking->guest_line_user_id, $lineContent);
                } catch (\Exception $e) {
                    Log::error('Failed to send LINE cancel message to guest', ['error' => $e->getMessage()]);
                }
            }
        } else {
            $user = $booking->user;
            $content = "{$user->name}様\n\n"
                . "以下の予約がキャンセルされました。\n\n"
                . "■ コンサルタント: {$consultant->name}\n"
                . "■ 日時: {$date}\n"
                . ($booking->cancel_reason ? "■ 理由: {$booking->cancel_reason}\n" : '');

            $this->send($user, $booking, 'booking_cancelled', $subject, $content);
        }

        // Also notify consultant
        $consultantContent = "{$consultant->name}様\n\n"
            . "{$bookerName}様の以下の予約がキャンセルされました。\n\n"
            . "■ 日時: {$date}\n";

        $this->send($consultant, $booking, 'booking_cancelled', $subject, $consultantContent);

        // System Room ID notification
        $defaultCwMsg = "予約がキャンセルされました。\n■ 予約者: {$bookerName}\n■ コンサルタント: {$consultantName}\n■ 日時: {$dateTime}"
            . ($booking->cancel_reason ? "\n■ 理由: {$booking->cancel_reason}" : '');
        $this->sendSystemChatwork($booking, 'chatwork_cancel_notification_message', $defaultCwMsg);
    }

    public function sendGuestReminder(Booking $booking, string $type): void
    {
        $date = $booking->booking_date->format('Y年m月d日');
        $time = substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5);
        $dateTime = "{$date} {$time}";
        $guestName = $booking->guest_name ?? '';

        $minutesBefore = (int) SystemSetting::get('reminder_minutes_before', 10);
        $typeLabel = match ($type) {
            'reminder_day_before' => '明日',
            'reminder_day_of' => '本日',
            'reminder_before_start' => "{$minutesBefore}分後",
            default => '',
        };

        $consultant = $booking->consultant;
        $consultantProfile = $consultant->consultantProfile;
        $meetingUrl = $booking->meeting_url ?: ($consultantProfile?->meeting_url ?? '');
        $consultantName = $consultant->name;

        // Determine type-specific setting key prefixes
        $keyPrefix = match ($type) {
            'reminder_day_before' => 'reminder_day_before',
            'reminder_day_of' => 'reminder_day_of',
            'reminder_before_start' => 'reminder_minutes_before',
            default => null,
        };

        // Email subject (custom or default)
        $customSubject = $keyPrefix ? SystemSetting::get("{$keyPrefix}_email_subject", '') : '';
        $subject = $customSubject
            ? $this->replacePlaceholders($customSubject, $guestName, $dateTime, $consultantName, $meetingUrl, '')
            : "【リマインド】{$typeLabel}の個別相談のご予約";

        // Email body (system setting > built-in)
        $customEmailBody = $keyPrefix ? SystemSetting::get("{$keyPrefix}_email_body", '') : '';
        if ($customEmailBody) {
            $content = $this->replacePlaceholders($customEmailBody, $guestName, $dateTime, $consultantName, $meetingUrl, '');
        } else {
            $content = "{$guestName}様\n\n"
                . "{$typeLabel}、個別相談のご予約があります。\n\n"
                . "■ 日時: {$dateTime}\n"
                . ($meetingUrl ? "■ ミーティングURL: {$meetingUrl}\n" : '')
                . "\nお忘れなくご参加ください。";
        }

        $this->sendGuestEmail($booking, $subject, $content, $type);

        // LINE message (separate from email)
        $customLineMessage = $keyPrefix ? SystemSetting::get("{$keyPrefix}_line_message", '') : '';
        if ($customLineMessage) {
            $lineContent = $this->replacePlaceholders($customLineMessage, $guestName, $dateTime, $consultantName, $meetingUrl, '');
        } else {
            $lineContent = "{$guestName}様\n{$typeLabel}、個別相談のご予約があります。\n■ 日時: {$dateTime}";
        }

        if ($booking->guest_line_user_id) {
            try {
                $lineService = app(LineNotificationService::class);
                $lineService->pushMessage($booking->guest_line_user_id, $lineContent);
            } catch (\Exception $e) {
                Log::error('Failed to send LINE reminder to guest', ['error' => $e->getMessage()]);
            }
        }

        // Also remind consultant
        $consultantContent = "{$consultant->name}様\n\n"
            . "{$typeLabel}、{$guestName}様（個別相談）との予約があります。\n\n"
            . "■ 日時: {$dateTime}\n"
            . "■ 電話番号: {$booking->guest_phone}\n"
            . ($meetingUrl ? "■ ミーティングURL: {$meetingUrl}\n" : '');

        $this->send($consultant, $booking, $type, $subject, $consultantContent);

        // System Room ID Chatwork notification (当日はsendMorningChatworkNotificationが担当)
        $chatworkSettingKey = match ($type) {
            'reminder_day_before' => 'chatwork_reminder_day_before_message',
            'reminder_before_start' => 'chatwork_reminder_before_start_message',
            default => null,
        };
        if ($chatworkSettingKey) {
            $defaultCwMsg = "{$typeLabel}、予約があります。\n■ 予約者: {$guestName}\n■ コンサルタント: {$consultantName}\n■ 日時: {$dateTime}"
                . ($meetingUrl ? "\n■ ミーティングURL: {$meetingUrl}" : '')
                . ($booking->notes ? "\n■ 備考: {$booking->notes}" : '');
            $this->sendSystemChatwork($booking, $chatworkSettingKey, $defaultCwMsg);
        }
    }

    public function sendReminder(Booking $booking, string $type): void
    {
        // Dispatch to guest reminder if this is a guest booking
        if ($booking->isGuest()) {
            $this->sendGuestReminder($booking, $type);
            return;
        }

        $user = $booking->user;
        $consultant = $booking->consultant;
        $consultantProfile = $consultant->consultantProfile;
        $date = $booking->booking_date->format('Y年m月d日');
        $time = substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5);
        $dateTime = "{$date} {$time}";

        $minutesBefore = (int) SystemSetting::get('reminder_minutes_before', 10);
        $typeLabel = match ($type) {
            'reminder_day_before' => '明日',
            'reminder_day_of' => '本日',
            'reminder_before_start' => "{$minutesBefore}分後",
            default => '',
        };

        // Determine meeting URL: booking > consultant profile
        $meetingUrl = $booking->meeting_url ?: ($consultantProfile?->meeting_url ?? '');
        $consultantName = $consultant->name;

        // Determine type-specific setting key prefix
        $keyPrefix = match ($type) {
            'reminder_day_before' => 'reminder_day_before',
            'reminder_day_of' => 'reminder_day_of',
            'reminder_before_start' => 'reminder_minutes_before',
            default => null,
        };

        // Email subject (custom or default)
        $customSubject = $keyPrefix ? SystemSetting::get("{$keyPrefix}_email_subject", '') : '';
        $subject = $customSubject
            ? $this->replacePlaceholders($customSubject, $user->name, $dateTime, $consultantName, $meetingUrl, '')
            : "【リマインド】{$typeLabel}のコンサルティング予約";

        // Email body: system setting > built-in
        $customEmailBody = $keyPrefix ? SystemSetting::get("{$keyPrefix}_email_body", '') : '';
        if ($customEmailBody) {
            $content = $this->replacePlaceholders($customEmailBody, $user->name, $dateTime, $consultantName, $meetingUrl, '');
        } else {
            $content = "{$user->name}様\n\n"
                . "{$typeLabel}、コンサルティングの予約があります。\n\n"
                . "■ コンサルタント: {$consultant->name}\n"
                . "■ 日時: {$dateTime}\n"
                . ($meetingUrl ? "■ ミーティングURL: {$meetingUrl}\n" : '')
                . "\nお忘れなくご参加ください。";
        }

        $this->send($user, $booking, $type, $subject, $content);

        // Also remind consultant
        $consultantContent = "{$consultant->name}様\n\n"
            . "{$typeLabel}、{$user->name}様とのコンサルティングがあります。\n\n"
            . "■ 日時: {$dateTime}\n"
            . ($meetingUrl ? "■ ミーティングURL: {$meetingUrl}\n" : '');

        $this->send($consultant, $booking, $type, $subject, $consultantContent);

        // System Room ID Chatwork notification (当日はsendMorningChatworkNotificationが担当)
        $chatworkSettingKey = match ($type) {
            'reminder_day_before' => 'chatwork_reminder_day_before_message',
            'reminder_before_start' => 'chatwork_reminder_before_start_message',
            default => null,
        };
        if ($chatworkSettingKey) {
            $defaultCwMsg = "{$typeLabel}、予約があります。\n■ 予約者: {$user->name}\n■ コンサルタント: {$consultantName}\n■ 日時: {$dateTime}"
                . ($meetingUrl ? "\n■ ミーティングURL: {$meetingUrl}" : '')
                . ($booking->notes ? "\n■ 備考: {$booking->notes}" : '');
            $this->sendSystemChatwork($booking, $chatworkSettingKey, $defaultCwMsg);
        }
    }

    private function send($user, Booking $booking, string $type, string $subject, string $content): void
    {
        if ($user->notify_email) {
            $this->sendEmail($user, $booking, $type, $subject, $content);
        }

        if ($user->notify_line) {
            $this->sendLine($user, $booking, $type, $content);
        }

        // Send to Chatwork if enabled and room ID is configured
        // コンサルタントはchatwork_room_idを持たない設計のためスキップ（システムルームはsendSystemChatworkで送信）
        if ($user->notify_chatwork && $user->chatwork_room_id && $user->role !== 'consultant') {
            $this->sendChatwork($user, $booking, $type, $content);
        }
    }

    private function sendEmail($user, Booking $booking, string $type, string $subject, string $content): void
    {
        // 重複防止チェック
        if ($this->alreadySent($booking->id, 'email', $type, $user->id)) {
            return;
        }

        try {
            Mail::raw($content, function ($message) use ($user, $subject) {
                $message->to($user->email)
                    ->subject($subject);
            });

            $this->logNotification($user->id, $booking->id, 'email', $type, $subject, $content, 'sent');
        } catch (\Exception $e) {
            $this->logNotification($user->id, $booking->id, 'email', $type, $subject, $content, 'failed', $e->getMessage());
        }
    }

    private function sendGuestEmail(Booking $booking, string $subject, string $content, string $type = 'booking_confirmed'): void
    {
        // 重複防止チェック
        if ($this->alreadySent($booking->id, 'email', $type)) {
            return;
        }

        try {
            Mail::raw($content, function ($message) use ($booking, $subject) {
                $message->to($booking->guest_email)
                    ->subject($subject);
            });

            $this->logNotification(null, $booking->id, 'email', $type, $subject, $content, 'sent');
        } catch (\Exception $e) {
            $this->logNotification(null, $booking->id, 'email', $type, $subject, $content, 'failed', $e->getMessage());
        }
    }

    private function sendChatwork($user, Booking $booking, string $type, string $content): void
    {
        // 重複防止チェック
        if ($this->alreadySent($booking->id, 'chatwork', $type, $user->id)) {
            return;
        }

        try {
            $chatworkService = new ChatworkService();
            $message = $user->chatwork_id
                ? "[To:{$user->chatwork_id}]{$user->name}さん\n{$content}"
                : "{$user->name}さん\n{$content}";
            $chatworkService->sendMessage($user->chatwork_room_id, $message);

            $this->logNotification($user->id, $booking->id, 'chatwork', $type, null, $content, 'sent');
        } catch (\Exception $e) {
            $this->logNotification($user->id, $booking->id, 'chatwork', $type, null, $content, 'failed', $e->getMessage());
        }
    }

    private function sendLine($user, Booking $booking, string $type, string $content): void
    {
        if (!$user->line_user_id) {
            return;
        }

        // 重複防止チェック
        if ($this->alreadySent($booking->id, 'line', $type, $user->id)) {
            return;
        }

        try {
            $lineService = app(LineNotificationService::class);
            $lineService->pushMessage($user->line_user_id, $content);

            $this->logNotification($user->id, $booking->id, 'line', $type, null, $content, 'sent');
        } catch (\Exception $e) {
            $this->logNotification($user->id, $booking->id, 'line', $type, null, $content, 'failed', $e->getMessage());
        }
    }

    /**
     * システム設定のRoom IDにChatwork通知を送信
     */
    private function sendSystemChatwork(Booking $booking, string $settingKey, string $defaultMessage): void
    {
        $systemRoomId = SystemSetting::get('chatwork_room_id', '');
        if (!$systemRoomId || SystemSetting::get('chatwork_enabled', '0') !== '1') {
            return;
        }

        // 通知項目ごとのオンオフチェック
        $enabledKey = str_replace('_message', '_enabled', $settingKey);
        if (SystemSetting::get($enabledKey, '1') !== '1') {
            return;
        }

        // 重複防止: 同一booking・同一typeで既に送信済みならスキップ
        if ($this->alreadySent($booking->id, 'chatwork_system', $settingKey)) {
            return;
        }

        $consultant = $booking->consultant;
        $consultantProfile = $consultant->consultantProfile;
        $date = $booking->booking_date->format('Y年m月d日');
        $time = substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5);
        $dateTime = "{$date} {$time}";
        $bookerName = $booking->bookerName();
        $bookerEmail = $booking->bookerEmail();
        $bookerPhone = $booking->is_guest ? ($booking->guest_phone ?? '') : ($booking->user->phone ?? '');
        $consultantName = $consultant->name;
        $meetingUrl = $booking->meeting_url ?: ($consultantProfile?->meeting_url ?? '');
        $chatworkId = $consultantProfile?->chatwork_account_id ?? '';

        $notes = $booking->notes ?? '';

        $customMessage = SystemSetting::get($settingKey, '');
        $message = $customMessage
            ? $this->replacePlaceholders($customMessage, $bookerName, $dateTime, $consultantName, $meetingUrl, $chatworkId, $bookerEmail, $bookerPhone, $notes)
            : $defaultMessage;

        try {
            $chatworkService = new ChatworkService();
            $chatworkService->sendMessage($systemRoomId, $message);
            $this->logNotification(null, $booking->id, 'chatwork_system', $settingKey, null, $message, 'sent');
        } catch (\Exception $e) {
            $this->logNotification(null, $booking->id, 'chatwork_system', $settingKey, null, $message, 'failed', $e->getMessage());
        }
    }

    /**
     * 当日朝のChatwork通知を送信（スケジューラーから呼ばれる）
     */
    public function sendMorningChatworkNotification(Booking $booking): void
    {
        $consultant = $booking->consultant;
        $consultantProfile = $consultant->consultantProfile;
        $date = $booking->booking_date->format('Y年m月d日');
        $time = substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5);
        $dateTime = "{$date} {$time}";
        $bookerName = $booking->bookerName();
        $consultantName = $consultant->name;
        $meetingUrl = $booking->meeting_url ?: ($consultantProfile?->meeting_url ?? '');
        $chatworkId = $consultantProfile?->chatwork_account_id ?? '';

        $defaultMessage = "本日の予約があります。\n■ 予約者: {$bookerName}\n■ コンサルタント: {$consultantName}\n■ 日時: {$dateTime}"
            . ($meetingUrl ? "\n■ ミーティングURL: {$meetingUrl}" : '')
            . ($booking->notes ? "\n■ 備考: {$booking->notes}" : '');

        $this->sendSystemChatwork($booking, 'chatwork_morning_notification_message', $defaultMessage);
    }

    /**
     * 相談記録入力時のChatwork通知を送信
     */
    public function sendConsultationRecordNotification(Booking $booking): void
    {
        $systemRoomId = SystemSetting::get('chatwork_room_id', '');
        if (!$systemRoomId || SystemSetting::get('chatwork_enabled', '0') !== '1') {
            return;
        }
        if (SystemSetting::get('chatwork_consultation_record_enabled', '1') !== '1') {
            return;
        }

        // 初回のみ通知（追記時は飛ばさない）。
        // ただし記録のリカバリー（初期化/確定への差戻し）が行われた後の再入力は
        // 「初回入力」扱いで通知するため、リセット時刻以降のログのみを見る。
        $query = NotificationLog::where('booking_id', $booking->id)
            ->where('channel', 'chatwork_system')
            ->where('type', 'consultation_record')
            ->where('status', 'sent');
        if ($booking->consultation_record_reset_at) {
            $query->where('created_at', '>', $booking->consultation_record_reset_at);
        }
        $alreadyNotified = $query->exists();
        if ($alreadyNotified) {
            return;
        }

        $consultant = $booking->consultant;
        $date = $booking->booking_date->format('Y年m月d日');
        $time = substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5);
        $dateTime = "{$date} {$time}";
        $bookerName = $booking->bookerName();
        $consultantName = $consultant->name;

        $resultLabel = match ($booking->consultation_result) {
            'success' => '成約',
            'failure' => '不成約',
            'pending' => '保留',
            default => '不明',
        };

        $docIssued = $booking->important_document_issued ? '発行済み' : '未発行';

        $message = "[toall]\n相談記録が入力されました。\n\n"
            . "■ 予約者: {$bookerName}\n"
            . "■ コンサルタント: {$consultantName}\n"
            . "■ 日時: {$dateTime}\n"
            . "■ 結果: {$resultLabel}\n"
            . "■ 記録内容:\n{$booking->consultation_notes}\n"
            . "■ 重要事項説明書: {$docIssued}";

        try {
            $chatworkService = new ChatworkService();
            $chatworkService->sendMessage($systemRoomId, $message);
            $this->logNotification(null, $booking->id, 'chatwork_system', 'consultation_record', null, $message, 'sent');
        } catch (\Exception $e) {
            $this->logNotification(null, $booking->id, 'chatwork_system', 'consultation_record', null, $message, 'failed', $e->getMessage());
        }
    }

    private function replacePlaceholders(string $text, string $name, string $date, string $consultantName = '', string $meetingUrl = '', string $chatworkId = '', string $email = '', string $phone = '', string $notes = ''): string
    {
        // 旧プレースホルダ {important_document_url} は廃止。既存テンプレに残っていても
        // 空文字に置換することで literal 表示を防ぐ。
        return str_replace(
            ['{name}', '{date}', '{consultant}', '{meeting_url}', '{chatwork_id}', '{important_document_url}', '{email}', '{phone}', '{notes}'],
            [$name, $date, $consultantName, $meetingUrl, $chatworkId, '', $email, $phone, $notes],
            $text
        );
    }

    private function logNotification(?int $userId, int $bookingId, string $channel, string $type, ?string $subject, ?string $content, string $status, ?string $errorMessage = null): void
    {
        try {
            NotificationLog::create([
                'user_id' => $userId,
                'booking_id' => $bookingId,
                'channel' => $channel,
                'type' => $type,
                'subject' => $subject,
                'content' => $content,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write notification log', [
                'booking_id' => $bookingId,
                'channel' => $channel,
                'type' => $type,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
