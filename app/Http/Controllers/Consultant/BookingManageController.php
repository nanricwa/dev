<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BookingManageController extends Controller
{
    public function index(Request $request)
    {
        $consultant = auth()->user();
        $status = $request->get('status', 'all');
        $search = $request->get('search');
        $result = $request->get('result'); // 相談結果フィルター

        $query = Booking::where('consultant_id', $consultant->id)
            ->with('user')
            ->orderByDesc('booking_date');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // 相談結果フィルター（完了タブ用）
        if ($result && in_array($result, ['success', 'failure', 'pending', 'none'])) {
            if ($result === 'none') {
                $query->whereNull('consultation_result');
            } else {
                $query->where('consultation_result', $result);
            }
        }

        // 名前・メールアドレスで検索
        if ($search) {
            $query->where(function ($q) use ($search) {
                // ゲスト予約: guest_name, guest_email
                $q->where('guest_name', 'like', "%{$search}%")
                    ->orWhere('guest_email', 'like', "%{$search}%")
                    // 会員予約: users テーブルの name, email
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query->paginate(10);

        return view('consultant.bookings.index', compact('bookings', 'status', 'search', 'result'));
    }

    public function complete(Booking $booking)
    {
        if ($booking->consultant_id !== auth()->id()) {
            abort(403);
        }

        if (!$booking->isApproved()) {
            return back()->with('error', 'この予約は完了にできません。');
        }

        $booking->update(['status' => 'completed']);

        $profile = auth()->user()->consultantProfile;
        if ($profile) {
            $profile->increment('total_bookings');
        }

        AuditLog::log('booking_completed', $booking);

        return back()->with('success', '予約を完了にしました。');
    }

    public function cancel(Request $request, Booking $booking)
    {
        if ($booking->consultant_id !== auth()->id()) {
            abort(403);
        }

        if (!$booking->isApproved()) {
            return back()->with('error', 'この予約はキャンセルできません。');
        }

        $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ]);

        $booking->update([
            'status' => 'cancelled',
            'cancel_reason' => $request->cancel_reason,
        ]);

        if ($booking->google_event_id || $booking->consultant_google_event_id) {
            try {
                $googleService = app(GoogleCalendarService::class);
                $googleService->syncDeleteEvent($booking);
                $booking->update(['google_event_id' => null, 'consultant_google_event_id' => null]);
            } catch (\Exception $e) {
                // Ignore Google Calendar errors
            }
        }

        AuditLog::log('booking_cancelled_by_consultant', $booking);

        if (!$request->boolean('skip_notification')) {
            $notificationService = app(NotificationService::class);
            $notificationService->sendBookingCancelled($booking);
            return back()->with('success', '予約をキャンセルしました。予約者に通知が送信されました。');
        }

        return back()->with('success', '予約をキャンセルしました。（通知なし）');
    }

    public function updateConsultationRecord(Request $request, Booking $booking)
    {
        if ($booking->consultant_id !== auth()->id()) {
            abort(403);
        }

        if (!$booking->canEnterConsultationRecord()) {
            return back()->with('error', '予約日前の相談には記録を入力できません。');
        }

        $request->validate([
            'consultation_result' => ['required', 'in:success,failure,pending'],
            'consultation_notes' => ['required', 'string'],
            'important_document_issued' => ['nullable', 'boolean'],
        ]);

        $data = [
            'consultation_result' => $request->consultation_result,
            'consultation_notes' => $request->consultation_notes,
            'important_document_issued' => $request->boolean('important_document_issued'),
        ];

        // 承認済みの予約は自動的に完了にする
        if ($booking->isApproved()) {
            $data['status'] = 'completed';
        }

        $booking->update($data);

        // 完了時にコンサルタントの実績をカウント
        if ($booking->status === 'completed' && $booking->wasChanged('status')) {
            $profile = auth()->user()->consultantProfile;
            if ($profile) {
                $profile->increment('total_bookings');
            }
            AuditLog::log('booking_completed', $booking);
        }

        AuditLog::log('consultation_record_updated', $booking);

        // Chatworkシステムルームへ通知
        try {
            app(NotificationService::class)->sendConsultationRecordNotification($booking);
        } catch (\Exception $e) {
            \Log::warning('相談記録のChatwork通知に失敗: ' . $e->getMessage());
        }

        return back()->with('success', '相談記録を保存しました。');
    }

    public function updateUserNotes(Request $request, Booking $booking)
    {
        if ($booking->consultant_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        $booking->update([
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'メモを保存しました。');
    }

    public function sendEmail(Request $request, Booking $booking)
    {
        if ($booking->consultant_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // 送信先メールアドレスを取得
        $toEmail = $booking->isGuest() ? $booking->guest_email : $booking->user?->email;
        $toName = $booking->bookerName();

        if (!$toEmail) {
            return back()->with('error', '送信先のメールアドレスが見つかりません。');
        }

        // プレースホルダー置換
        $dateLabel = $booking->booking_date->format('Y年m月d日') . ' '
            . \Carbon\Carbon::parse($booking->start_time)->format('H:i') . ' - '
            . \Carbon\Carbon::parse($booking->end_time)->format('H:i');

        $replacements = [
            '{name}' => $toName,
            '{date}' => $dateLabel,
            '{consultant}' => auth()->user()->name,
        ];
        $subject = str_replace(array_keys($replacements), array_values($replacements), $validated['subject']);
        $body = str_replace(array_keys($replacements), array_values($replacements), $validated['message']);

        try {
            Mail::raw($body, function ($mail) use ($toEmail, $subject) {
                $mail->to($toEmail)->subject($subject);
            });

            AuditLog::log('email_sent_by_consultant', $booking);

            return back()->with('success', "{$toName}さんにメールを送信しました。");
        } catch (\Exception $e) {
            \Log::error('Consultant email send failed', ['error' => $e->getMessage(), 'booking_id' => $booking->id]);
            return back()->with('error', 'メールの送信に失敗しました。');
        }
    }
}
