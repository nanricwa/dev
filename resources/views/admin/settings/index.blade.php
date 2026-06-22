@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">システム設定</h1>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div x-data="{ activeTab: new URLSearchParams(window.location.search).get('tab') || 'booking' }">
        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'booking'" :class="activeTab === 'booking' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                    予約設定
                </button>
                <button @click="activeTab = 'reminder'" :class="activeTab === 'reminder' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                    リマインダ設定
                </button>
                <button @click="activeTab = 'templates'" :class="activeTab === 'templates' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                    ゲスト向けメッセージ設定
                </button>
                <button @click="activeTab = 'integration'" :class="activeTab === 'integration' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                    連携設定
                </button>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">

                {{-- ========================================== --}}
                {{-- Tab 1: Booking Settings --}}
                {{-- ========================================== --}}
                <div x-show="activeTab === 'booking'" x-cloak>
                    <div class="bg-white rounded-lg shadow" x-data="{ acceptanceEnabled: {{ (optional($settings['booking_acceptance_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }} }">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">予約設定</h2>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.update.booking') }}" class="p-6">
                            @csrf
                            @method('PUT')

                            <div class="space-y-6">
                                {{-- Booking Acceptance Toggle --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">受付状況</label>
                                    <div class="flex items-center">
                                        <button type="button"
                                                @click="acceptanceEnabled = !acceptanceEnabled"
                                                :class="acceptanceEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                role="switch">
                                            <span :class="acceptanceEnabled ? 'translate-x-5' : 'translate-x-0'"
                                                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                        </button>
                                        <input type="hidden" name="booking_acceptance_enabled" :value="acceptanceEnabled ? '1' : '0'">
                                        <span class="ml-3 text-sm" :class="acceptanceEnabled ? 'text-green-600 font-medium' : 'text-gray-500'" x-text="acceptanceEnabled ? '受付中' : '受付停止'"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">無効にすると新規予約の受付を停止します。</p>
                                </div>

                                {{-- Cancel Policy Hours --}}
                                <div>
                                    <label for="cancel_policy_hours" class="block text-sm font-medium text-gray-700 mb-1">
                                        予約のキャンセル期限（時間）
                                    </label>
                                    <input type="number" name="cancel_policy_hours" id="cancel_policy_hours"
                                           value="{{ old('cancel_policy_hours', optional($settings['cancel_policy_hours'] ?? null)->value ?? 24) }}"
                                           min="0"
                                           class="block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cancel_policy_hours') border-red-500 @enderror">
                                    <p class="mt-1 text-xs text-gray-500">予約の何時間前までキャンセル可能か設定します。</p>
                                    @error('cancel_policy_hours')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Hours From Now --}}
                                <div>
                                    <label for="hours_from_now" class="block text-sm font-medium text-gray-700 mb-1">
                                        何時間後から先の日程候補を提示しますか？
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" name="hours_from_now" id="hours_from_now"
                                               value="{{ old('hours_from_now', optional($settings['hours_from_now'] ?? null)->value ?? 2) }}"
                                               min="0"
                                               class="w-28 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('hours_from_now') border-red-500 @enderror">
                                        <span class="text-sm text-gray-500">時間後</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">現在時刻から指定時間以降の日程候補のみをユーザーに表示します。</p>
                                    @error('hours_from_now')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Schedule Disclosure Days --}}
                                <div>
                                    <label for="schedule_disclosure_days" class="block text-sm font-medium text-gray-700 mb-1">
                                        何日間分の日程候補を提示しますか？
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="number" name="schedule_disclosure_days" id="schedule_disclosure_days"
                                               value="{{ old('schedule_disclosure_days', optional($settings['schedule_disclosure_days'] ?? null)->value ?? 30) }}"
                                               min="1"
                                               class="w-28 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('schedule_disclosure_days') border-red-500 @enderror">
                                        <span class="text-sm text-gray-500">日間</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">今日から何日先までの予約枠をユーザーに表示するか設定します。</p>
                                    @error('schedule_disclosure_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Placeholder info --}}
                                <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
                                    <p class="text-xs text-blue-700">利用可能なプレースホルダー: <code class="bg-blue-100 px-1 rounded">{name}</code>（予約者名）、<code class="bg-blue-100 px-1 rounded">{date}</code>（日時）、<code class="bg-blue-100 px-1 rounded">{consultant}</code>（コンサルタント名）、<code class="bg-blue-100 px-1 rounded">{meeting_url}</code>（ミーティングURL）</p>
                                </div>

                                {{-- Booking Confirmation Message --}}
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">予約完了（自動返信）メール</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="booking_confirm_email_subject" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール件名）</label>
                                            <input type="text" name="booking_confirm_email_subject" id="booking_confirm_email_subject"
                                                   value="{{ old('booking_confirm_email_subject', optional($settings['booking_confirm_email_subject'] ?? null)->value ?? '') }}"
                                                   maxlength="200"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="例: 【予約確定】個別相談のご予約が確定しました">
                                            <p class="mt-1 text-xs text-gray-500">空欄時はデフォルトの件名が使用されます。</p>
                                        </div>
                                        <div>
                                            <label for="booking_confirm_email_body" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール本文）</label>
                                            <textarea name="booking_confirm_email_body" id="booking_confirm_email_body" rows="4"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="予約完了メールに追加するメッセージを入力してください。">{{ old('booking_confirm_email_body', optional($settings['booking_confirm_email_body'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">予約完了時の自動返信メールに追記される内容です。空欄時はデフォルトの文面のみ送信されます。</p>
                                        </div>
                                        <div>
                                            <label for="booking_confirm_line_message" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（LINE）</label>
                                            <textarea name="booking_confirm_line_message" id="booking_confirm_line_message" rows="4"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="予約完了時のLINEメッセージを入力してください。">{{ old('booking_confirm_line_message', optional($settings['booking_confirm_line_message'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">予約完了時のLINE通知に使用されるメッセージです。空欄時はデフォルトの文面が使用されます。</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Cancel Notification Message --}}
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">キャンセル通知メール</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="cancel_notification_email_subject" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール件名）</label>
                                            <input type="text" name="cancel_notification_email_subject" id="cancel_notification_email_subject"
                                                   value="{{ old('cancel_notification_email_subject', optional($settings['cancel_notification_email_subject'] ?? null)->value ?? '') }}"
                                                   maxlength="200"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="例: 【キャンセル】予約のキャンセルについて">
                                            <p class="mt-1 text-xs text-gray-500">空欄時はデフォルトの件名が使用されます。</p>
                                        </div>
                                        <div>
                                            <label for="cancel_notification_email_body" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール本文）</label>
                                            <textarea name="cancel_notification_email_body" id="cancel_notification_email_body" rows="4"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="キャンセル通知メールに追加するメッセージを入力してください。">{{ old('cancel_notification_email_body', optional($settings['cancel_notification_email_body'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">キャンセル通知メールに追記される内容です。空欄時はデフォルトの文面のみ送信されます。</p>
                                        </div>
                                        <div>
                                            <label for="cancel_notification_line_message" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（LINE）</label>
                                            <textarea name="cancel_notification_line_message" id="cancel_notification_line_message" rows="4"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="キャンセル時のLINEメッセージを入力してください。">{{ old('cancel_notification_line_message', optional($settings['cancel_notification_line_message'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">キャンセル時のLINE通知に使用されるメッセージです。空欄時はデフォルトの文面が使用されます。</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Max Bookings Per Day --}}
                                <div>
                                    <label for="max_bookings_per_day" class="block text-sm font-medium text-gray-700 mb-1">
                                        1日あたりの最大予約数
                                    </label>
                                    <input type="number" name="max_bookings_per_day" id="max_bookings_per_day"
                                           value="{{ old('max_bookings_per_day', optional($settings['max_bookings_per_day'] ?? null)->value ?? 10) }}"
                                           min="1"
                                           class="block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('max_bookings_per_day') border-red-500 @enderror">
                                    <p class="mt-1 text-xs text-gray-500">コンサルタント1人あたりの1日の最大予約数を設定します。</p>
                                    @error('max_bookings_per_day')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="flex items-center justify-end pt-6 mt-6 border-t border-gray-200">
                                <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                    予約設定を保存
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- Tab 2: Reminder Settings --}}
                {{-- ========================================== --}}
                <div x-show="activeTab === 'reminder'" x-cloak>
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">リマインダ設定</h2>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.update.reminder') }}" class="p-6"
                              x-data="{
                                  dayBeforeEnabled: {{ (optional($settings['reminder_day_before_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                                  dayOfEnabled: {{ (optional($settings['reminder_day_of_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                                  minutesBeforeEnabled: {{ (optional($settings['reminder_minutes_before_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }}
                              }">
                            @csrf
                            @method('PUT')

                            <div class="space-y-6">
                                {{-- Placeholder info --}}
                                <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
                                    <p class="text-xs text-blue-700">利用可能なプレースホルダー: <code class="bg-blue-100 px-1 rounded">{name}</code>（予約者名）、<code class="bg-blue-100 px-1 rounded">{date}</code>（日時）、<code class="bg-blue-100 px-1 rounded">{consultant}</code>（コンサルタント名）、<code class="bg-blue-100 px-1 rounded">{meeting_url}</code>（ミーティングURL）</p>
                                </div>

                                {{-- Day-Before Reminder --}}
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-medium text-gray-700">前日リマインダ（メール/LINE）</h4>
                                        <div class="flex items-center">
                                            <button type="button"
                                                    @click="dayBeforeEnabled = !dayBeforeEnabled"
                                                    :class="dayBeforeEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                    role="switch">
                                                <span :class="dayBeforeEnabled ? 'translate-x-5' : 'translate-x-0'"
                                                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                            </button>
                                            <input type="hidden" name="reminder_day_before_enabled" :value="dayBeforeEnabled ? '1' : '0'">
                                            <span class="ml-2 text-xs" :class="dayBeforeEnabled ? 'text-green-600 font-medium' : 'text-gray-500'" x-text="dayBeforeEnabled ? 'ON' : 'OFF'"></span>
                                        </div>
                                    </div>
                                    <div class="space-y-3" x-show="dayBeforeEnabled">
                                        <div>
                                            <label for="reminder_day_before_hour" class="block text-xs font-medium text-gray-600 mb-1">送信時刻</label>
                                            <select name="reminder_day_before_hour" id="reminder_day_before_hour"
                                                    class="block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                @for($h = 8; $h <= 21; $h++)
                                                    <option value="{{ $h }}" {{ (int)(optional($settings['reminder_day_before_hour'] ?? null)->value ?? 18) === $h ? 'selected' : '' }}>{{ sprintf('%02d:00', $h) }}</option>
                                                @endfor
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500">予約前日のリマインドを送信する時刻です。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_day_before_email_subject" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール件名）</label>
                                            <input type="text" name="reminder_day_before_email_subject" id="reminder_day_before_email_subject"
                                                   value="{{ old('reminder_day_before_email_subject', optional($settings['reminder_day_before_email_subject'] ?? null)->value ?? '') }}"
                                                   maxlength="200"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="例: 【リマインド】明日の個別相談のご予約">
                                            <p class="mt-1 text-xs text-gray-500">空欄時はデフォルトの件名が使用されます。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_day_before_email_body" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール本文）</label>
                                            <textarea name="reminder_day_before_email_body" id="reminder_day_before_email_body" rows="3"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="前日リマインダのメール本文">{{ old('reminder_day_before_email_body', optional($settings['reminder_day_before_email_body'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">空欄時はシステムデフォルトのメッセージが使用されます。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_day_before_line_message" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（LINE）</label>
                                            <textarea name="reminder_day_before_line_message" id="reminder_day_before_line_message" rows="3"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="前日リマインダのLINEメッセージ">{{ old('reminder_day_before_line_message', optional($settings['reminder_day_before_line_message'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">空欄時はシステムデフォルトのメッセージが使用されます。</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Day-Of Reminder --}}
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-medium text-gray-700">当日リマインダ（メール/LINE）</h4>
                                        <div class="flex items-center">
                                            <button type="button"
                                                    @click="dayOfEnabled = !dayOfEnabled"
                                                    :class="dayOfEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                    role="switch">
                                                <span :class="dayOfEnabled ? 'translate-x-5' : 'translate-x-0'"
                                                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                            </button>
                                            <input type="hidden" name="reminder_day_of_enabled" :value="dayOfEnabled ? '1' : '0'">
                                            <span class="ml-2 text-xs" :class="dayOfEnabled ? 'text-green-600 font-medium' : 'text-gray-500'" x-text="dayOfEnabled ? 'ON' : 'OFF'"></span>
                                        </div>
                                    </div>
                                    <div class="space-y-3" x-show="dayOfEnabled">
                                        <div>
                                            <label for="reminder_day_of_hour" class="block text-xs font-medium text-gray-600 mb-1">送信時刻</label>
                                            <select name="reminder_day_of_hour" id="reminder_day_of_hour"
                                                    class="block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                @for($h = 6; $h <= 12; $h++)
                                                    <option value="{{ $h }}" {{ (int)(optional($settings['reminder_day_of_hour'] ?? null)->value ?? 8) === $h ? 'selected' : '' }}>{{ sprintf('%02d:00', $h) }}</option>
                                                @endfor
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500">予約当日朝のリマインドを送信する時刻です。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_day_of_email_subject" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール件名）</label>
                                            <input type="text" name="reminder_day_of_email_subject" id="reminder_day_of_email_subject"
                                                   value="{{ old('reminder_day_of_email_subject', optional($settings['reminder_day_of_email_subject'] ?? null)->value ?? '') }}"
                                                   maxlength="200"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="例: 【リマインド】本日の個別相談のご予約">
                                            <p class="mt-1 text-xs text-gray-500">空欄時はデフォルトの件名が使用されます。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_day_of_email_body" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール本文）</label>
                                            <textarea name="reminder_day_of_email_body" id="reminder_day_of_email_body" rows="3"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="当日リマインダのメール本文">{{ old('reminder_day_of_email_body', optional($settings['reminder_day_of_email_body'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">空欄時はシステムデフォルトのメッセージが使用されます。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_day_of_line_message" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（LINE）</label>
                                            <textarea name="reminder_day_of_line_message" id="reminder_day_of_line_message" rows="3"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="当日リマインダのLINEメッセージ">{{ old('reminder_day_of_line_message', optional($settings['reminder_day_of_line_message'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">空欄時はシステムデフォルトのメッセージが使用されます。</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Minutes-Before Reminder --}}
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-medium text-gray-700">当日リマインダ（分前）（メール/LINE）</h4>
                                        <div class="flex items-center">
                                            <button type="button"
                                                    @click="minutesBeforeEnabled = !minutesBeforeEnabled"
                                                    :class="minutesBeforeEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                    role="switch">
                                                <span :class="minutesBeforeEnabled ? 'translate-x-5' : 'translate-x-0'"
                                                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                            </button>
                                            <input type="hidden" name="reminder_minutes_before_enabled" :value="minutesBeforeEnabled ? '1' : '0'">
                                            <span class="ml-2 text-xs" :class="minutesBeforeEnabled ? 'text-green-600 font-medium' : 'text-gray-500'" x-text="minutesBeforeEnabled ? 'ON' : 'OFF'"></span>
                                        </div>
                                    </div>
                                    <div class="space-y-3" x-show="minutesBeforeEnabled">
                                        <div>
                                            <label for="reminder_minutes_before" class="block text-xs font-medium text-gray-600 mb-1">時刻設定（分前）</label>
                                            <select name="reminder_minutes_before" id="reminder_minutes_before"
                                                    class="block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="5" {{ (int)(optional($settings['reminder_minutes_before'] ?? null)->value ?? 10) === 5 ? 'selected' : '' }}>5分前</option>
                                                <option value="10" {{ (int)(optional($settings['reminder_minutes_before'] ?? null)->value ?? 10) === 10 ? 'selected' : '' }}>10分前</option>
                                                <option value="15" {{ (int)(optional($settings['reminder_minutes_before'] ?? null)->value ?? 10) === 15 ? 'selected' : '' }}>15分前</option>
                                                <option value="30" {{ (int)(optional($settings['reminder_minutes_before'] ?? null)->value ?? 10) === 30 ? 'selected' : '' }}>30分前</option>
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500">予約開始の何分前にリマインドを送信するかを設定します。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_minutes_before_email_subject" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール件名）</label>
                                            <input type="text" name="reminder_minutes_before_email_subject" id="reminder_minutes_before_email_subject"
                                                   value="{{ old('reminder_minutes_before_email_subject', optional($settings['reminder_minutes_before_email_subject'] ?? null)->value ?? '') }}"
                                                   maxlength="200"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="例: 【リマインド】まもなく個別相談が始まります">
                                            <p class="mt-1 text-xs text-gray-500">空欄時はデフォルトの件名が使用されます。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_minutes_before_email_body" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（メール本文）</label>
                                            <textarea name="reminder_minutes_before_email_body" id="reminder_minutes_before_email_body" rows="3"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="開始前リマインダのメール本文">{{ old('reminder_minutes_before_email_body', optional($settings['reminder_minutes_before_email_body'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">空欄時はシステムデフォルトのメッセージが使用されます。</p>
                                        </div>
                                        <div>
                                            <label for="reminder_minutes_before_line_message" class="block text-xs font-medium text-gray-600 mb-1">メッセージカスタマイズ（LINE）</label>
                                            <textarea name="reminder_minutes_before_line_message" id="reminder_minutes_before_line_message" rows="3"
                                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                      placeholder="開始前リマインダのLINEメッセージ">{{ old('reminder_minutes_before_line_message', optional($settings['reminder_minutes_before_line_message'] ?? null)->value ?? '') }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">空欄時はシステムデフォルトのメッセージが使用されます。</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="flex items-center justify-end pt-6 mt-6 border-t border-gray-200">
                                <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                    リマインダ設定を保存
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- Tab 3: Guest Message Templates --}}
                {{-- ========================================== --}}
                <div x-show="activeTab === 'templates'" x-cloak>
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">ゲスト向けメッセージ設定</h2>
                            <p class="mt-1 text-xs text-gray-500">テンプレートを最大10件まで登録できます。</p>
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-100 rounded-lg">
                                <p class="text-xs text-blue-700">利用可能なプレースホルダー: <code class="bg-blue-100 px-1 rounded">{name}</code>（ゲスト名）、<code class="bg-blue-100 px-1 rounded">{date}</code>（予約日時）、<code class="bg-blue-100 px-1 rounded">{consultant}</code>（コンサルタント名）、<code class="bg-blue-100 px-1 rounded">{meeting_url}</code>（ミーティングURL）</p>
                            </div>
                        </div>
                        <script id="templates-initial-data" type="application/json">@json($templatesJson)</script>
                        <form method="POST" action="{{ route('admin.settings.update.templates') }}" class="p-6"
                              x-data="{
                                  templates: JSON.parse(document.getElementById('templates-initial-data').textContent),
                                  addTemplate() {
                                      if (this.templates.length < 10) {
                                          this.templates.push({ id: null, name: '', subject: '', body: '' });
                                      }
                                  },
                                  removeTemplate(index) {
                                      this.templates.splice(index, 1);
                                  }
                              }">
                            @csrf
                            @method('PUT')

                            <div class="space-y-4">
                                <template x-for="(tpl, index) in templates" :key="index">
                                    <div class="p-4 bg-gray-50 rounded-lg relative">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-medium text-gray-700" x-text="'テンプレート ' + (index + 1)"></span>
                                            <button type="button" @click="removeTemplate(index)"
                                                    class="text-red-500 hover:text-red-700 text-xs font-medium transition"
                                                    x-show="templates.length > 1">
                                                削除
                                            </button>
                                        </div>
                                        <input type="hidden" :name="'templates[' + index + '][id]'" :value="tpl.id">
                                        <div class="space-y-3">
                                            <div>
                                                <label :for="'tpl_name_' + index" class="block text-xs font-medium text-gray-600 mb-1">テンプレート名</label>
                                                <input type="text" :id="'tpl_name_' + index"
                                                       :name="'templates[' + index + '][name]'"
                                                       x-model="tpl.name"
                                                       required maxlength="100"
                                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                       placeholder="テンプレート名">
                                            </div>
                                            <div>
                                                <label :for="'tpl_subject_' + index" class="block text-xs font-medium text-gray-600 mb-1">件名（メールのみ）</label>
                                                <input type="text" :id="'tpl_subject_' + index"
                                                       :name="'templates[' + index + '][subject]'"
                                                       x-model="tpl.subject"
                                                       maxlength="200"
                                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                       placeholder="メールの件名">
                                            </div>
                                            <div>
                                                <label :for="'tpl_body_' + index" class="block text-xs font-medium text-gray-600 mb-1">メッセージ（メール/LINE）</label>
                                                <textarea :id="'tpl_body_' + index"
                                                          :name="'templates[' + index + '][body]'"
                                                          x-model="tpl.body"
                                                          rows="4"
                                                          class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                          placeholder="メッセージ本文"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Add Template Button --}}
                            <div class="mt-4">
                                <button type="button" @click="addTemplate()"
                                        x-show="templates.length < 10"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    テンプレートを追加
                                </button>
                                <p class="mt-1 text-xs text-gray-400" x-show="templates.length >= 10">最大10件まで登録可能です。</p>
                            </div>

                            {{-- Submit --}}
                            <div class="flex items-center justify-end pt-6 mt-6 border-t border-gray-200">
                                <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                    テンプレートを保存
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- Tab 4: Integration Settings --}}
                {{-- ========================================== --}}
                <div x-show="activeTab === 'integration'" x-cloak>
                    <div class="bg-white rounded-lg shadow"
                         x-data="{
                             lineEnabled: {{ (optional($settings['line_enabled'] ?? null)->value ?? '0') === '1' ? 'true' : 'false' }},
                             chatworkEnabled: {{ (optional($settings['chatwork_enabled'] ?? null)->value ?? '0') === '1' ? 'true' : 'false' }},
                             cwBookingConfirmEnabled: {{ (optional($settings['chatwork_booking_confirm_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                             cwCancelEnabled: {{ (optional($settings['chatwork_cancel_notification_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                             cwMorningEnabled: {{ (optional($settings['chatwork_morning_notification_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                             cwScheduleRequestEnabled: {{ (optional($settings['chatwork_schedule_request_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                             cwReminderDayBeforeEnabled: {{ (optional($settings['chatwork_reminder_day_before_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                             cwReminderBeforeStartEnabled: {{ (optional($settings['chatwork_reminder_before_start_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                             cwConsultationRecordEnabled: {{ (optional($settings['chatwork_consultation_record_enabled'] ?? null)->value ?? '1') === '1' ? 'true' : 'false' }},
                             googleCalendarEnabled: {{ (optional($settings['google_calendar_enabled'] ?? null)->value ?? '0') === '1' ? 'true' : 'false' }}
                         }">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">連携設定</h2>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.update.integration') }}" class="p-6">
                            @csrf
                            @method('PUT')

                            <div class="space-y-8">
                                {{-- LINE Integration --}}
                                <div>
                                    <h3 class="text-md font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">LINE連携設定</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="line_channel_token" class="block text-sm font-medium text-gray-700 mb-1">
                                                LINEチャネルトークン
                                            </label>
                                            <input type="text" name="line_channel_token" id="line_channel_token"
                                                   value="{{ old('line_channel_token', optional($settings['line_channel_token'] ?? null)->value ?? '') }}"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="チャネルアクセストークンを入力">
                                        </div>
                                        <div>
                                            <label for="line_channel_secret" class="block text-sm font-medium text-gray-700 mb-1">
                                                LINEチャネルシークレット
                                            </label>
                                            <input type="password" name="line_channel_secret" id="line_channel_secret"
                                                   value="{{ old('line_channel_secret', optional($settings['line_channel_secret'] ?? null)->value ?? '') }}"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="チャネルシークレットを入力">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">LINE連携</label>
                                            <div class="flex items-center">
                                                <button type="button"
                                                        @click="lineEnabled = !lineEnabled"
                                                        :class="lineEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                        role="switch">
                                                    <span :class="lineEnabled ? 'translate-x-5' : 'translate-x-0'"
                                                          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                </button>
                                                <input type="hidden" name="line_enabled" :value="lineEnabled ? '1' : '0'">
                                                <span class="ml-3 text-sm" :class="lineEnabled ? 'text-green-600 font-medium' : 'text-gray-500'" x-text="lineEnabled ? '有効' : '無効'"></span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500">有効にするとLINE経由での通知が送信されます。</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Chatwork Integration --}}
                                <div>
                                    <h3 class="text-md font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">Chatwork連携設定</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="chatwork_api_token" class="block text-sm font-medium text-gray-700 mb-1">
                                                Chatwork APIトークン
                                            </label>
                                            <input type="password" name="chatwork_api_token" id="chatwork_api_token"
                                                   value="{{ old('chatwork_api_token', optional($settings['chatwork_api_token'] ?? null)->value ?? '') }}"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="Chatwork APIトークンを入力">
                                            <p class="mt-1 text-xs text-gray-500">Chatwork管理画面から取得したAPIトークンを入力してください。</p>
                                        </div>
                                        <div>
                                            <label for="chatwork_room_id" class="block text-sm font-medium text-gray-700 mb-1">
                                                Chatwork Room ID
                                            </label>
                                            <input type="text" name="chatwork_room_id" id="chatwork_room_id"
                                                   value="{{ old('chatwork_room_id', optional($settings['chatwork_room_id'] ?? null)->value ?? '') }}"
                                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                   placeholder="通知先のRoom IDを入力">
                                            <p class="mt-1 text-xs text-gray-500">通知メッセージを送信するChatworkルームのIDを入力してください。</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Chatwork通知</label>
                                            <div class="flex items-center">
                                                <button type="button"
                                                        @click="chatworkEnabled = !chatworkEnabled"
                                                        :class="chatworkEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                        role="switch">
                                                    <span :class="chatworkEnabled ? 'translate-x-5' : 'translate-x-0'"
                                                          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                </button>
                                                <input type="hidden" name="chatwork_enabled" :value="chatworkEnabled ? '1' : '0'">
                                                <span class="ml-3 text-sm" :class="chatworkEnabled ? 'text-green-600 font-medium' : 'text-gray-500'" x-text="chatworkEnabled ? '有効' : '無効'"></span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500">有効にするとChatwork経由での通知が送信されます。</p>
                                        </div>

                                        {{-- Chatwork Notification Message Templates --}}
                                        <div class="mt-6 pt-4 border-t border-gray-200">
                                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Chatwork通知メッセージ設定</h4>
                                            <p class="text-xs text-gray-500 mb-3">通知項目ごとにオンオフとメッセージをカスタマイズできます。</p>
                                            <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg mb-4">
                                                <p class="text-xs text-blue-700">利用可能なプレースホルダー: <code class="bg-blue-100 px-1 rounded">{name}</code>（予約者名）、<code class="bg-blue-100 px-1 rounded">{email}</code>（メール）、<code class="bg-blue-100 px-1 rounded">{phone}</code>（電話番号）、<code class="bg-blue-100 px-1 rounded">{date}</code>（日時）、<code class="bg-blue-100 px-1 rounded">{consultant}</code>（コンサルタント名）、<code class="bg-blue-100 px-1 rounded">{meeting_url}</code>（ミーティングURL）、<code class="bg-blue-100 px-1 rounded">{chatwork_id}</code>（コンサルタントChatwork ID ※TO指定用）</p>
                                            </div>
                                            <div class="space-y-5">
                                                {{-- 予約確定通知 --}}
                                                <div class="p-4 border border-gray-200 rounded-lg">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <label class="text-xs font-medium text-gray-700">予約確定通知</label>
                                                        <div class="flex items-center">
                                                            <button type="button" @click="cwBookingConfirmEnabled = !cwBookingConfirmEnabled"
                                                                    :class="cwBookingConfirmEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" role="switch">
                                                                <span :class="cwBookingConfirmEnabled ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                            <input type="hidden" name="chatwork_booking_confirm_enabled" :value="cwBookingConfirmEnabled ? '1' : '0'">
                                                            <span class="ml-2 text-xs" :class="cwBookingConfirmEnabled ? 'text-green-600' : 'text-gray-400'" x-text="cwBookingConfirmEnabled ? 'ON' : 'OFF'"></span>
                                                        </div>
                                                    </div>
                                                    <textarea name="chatwork_booking_confirm_message" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="予約が入った時にChatworkへ送信されるメッセージ">{{ old('chatwork_booking_confirm_message', optional($settings['chatwork_booking_confirm_message'] ?? null)->value ?? '') }}</textarea>
                                                    <p class="mt-1 text-xs text-gray-500">空欄時はデフォルトの文面が使用されます。</p>
                                                </div>

                                                {{-- キャンセル通知 --}}
                                                <div class="p-4 border border-gray-200 rounded-lg">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <label class="text-xs font-medium text-gray-700">キャンセル通知</label>
                                                        <div class="flex items-center">
                                                            <button type="button" @click="cwCancelEnabled = !cwCancelEnabled"
                                                                    :class="cwCancelEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" role="switch">
                                                                <span :class="cwCancelEnabled ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                            <input type="hidden" name="chatwork_cancel_notification_enabled" :value="cwCancelEnabled ? '1' : '0'">
                                                            <span class="ml-2 text-xs" :class="cwCancelEnabled ? 'text-green-600' : 'text-gray-400'" x-text="cwCancelEnabled ? 'ON' : 'OFF'"></span>
                                                        </div>
                                                    </div>
                                                    <textarea name="chatwork_cancel_notification_message" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="キャンセル時にChatworkへ送信されるメッセージ">{{ old('chatwork_cancel_notification_message', optional($settings['chatwork_cancel_notification_message'] ?? null)->value ?? '') }}</textarea>
                                                    <p class="mt-1 text-xs text-gray-500">空欄時はデフォルトの文面が使用されます。</p>
                                                </div>

                                                {{-- 当日朝通知 --}}
                                                <div class="p-4 border border-gray-200 rounded-lg">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <label class="text-xs font-medium text-gray-700">当日朝通知（毎朝8:00配信）</label>
                                                        <div class="flex items-center">
                                                            <button type="button" @click="cwMorningEnabled = !cwMorningEnabled"
                                                                    :class="cwMorningEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" role="switch">
                                                                <span :class="cwMorningEnabled ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                            <input type="hidden" name="chatwork_morning_notification_enabled" :value="cwMorningEnabled ? '1' : '0'">
                                                            <span class="ml-2 text-xs" :class="cwMorningEnabled ? 'text-green-600' : 'text-gray-400'" x-text="cwMorningEnabled ? 'ON' : 'OFF'"></span>
                                                        </div>
                                                    </div>
                                                    <textarea name="chatwork_morning_notification_message" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="当日朝8:00にChatworkへ送信されるメッセージ">{{ old('chatwork_morning_notification_message', optional($settings['chatwork_morning_notification_message'] ?? null)->value ?? '') }}</textarea>
                                                    <p class="mt-1 text-xs text-gray-500">当日の予約があるコンサルタントへ朝8:00に配信されます。空欄時はデフォルトの文面が使用されます。</p>
                                                </div>

                                                {{-- 前日リマインド --}}
                                                <div class="p-4 border border-gray-200 rounded-lg">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <label class="text-xs font-medium text-gray-700">前日リマインド通知</label>
                                                        <div class="flex items-center">
                                                            <button type="button" @click="cwReminderDayBeforeEnabled = !cwReminderDayBeforeEnabled"
                                                                    :class="cwReminderDayBeforeEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" role="switch">
                                                                <span :class="cwReminderDayBeforeEnabled ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                            <input type="hidden" name="chatwork_reminder_day_before_enabled" :value="cwReminderDayBeforeEnabled ? '1' : '0'">
                                                            <span class="ml-2 text-xs" :class="cwReminderDayBeforeEnabled ? 'text-green-600' : 'text-gray-400'" x-text="cwReminderDayBeforeEnabled ? 'ON' : 'OFF'"></span>
                                                        </div>
                                                    </div>
                                                    <textarea name="chatwork_reminder_day_before_message" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="前日リマインド時にChatworkへ送信されるメッセージ">{{ old('chatwork_reminder_day_before_message', optional($settings['chatwork_reminder_day_before_message'] ?? null)->value ?? '') }}</textarea>
                                                    <p class="mt-1 text-xs text-gray-500">予約前日のリマインド通知。空欄時はデフォルトの文面が使用されます。</p>
                                                </div>

                                                {{-- 開始前リマインド --}}
                                                <div class="p-4 border border-gray-200 rounded-lg">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <label class="text-xs font-medium text-gray-700">開始前リマインド通知</label>
                                                        <div class="flex items-center">
                                                            <button type="button" @click="cwReminderBeforeStartEnabled = !cwReminderBeforeStartEnabled"
                                                                    :class="cwReminderBeforeStartEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" role="switch">
                                                                <span :class="cwReminderBeforeStartEnabled ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                            <input type="hidden" name="chatwork_reminder_before_start_enabled" :value="cwReminderBeforeStartEnabled ? '1' : '0'">
                                                            <span class="ml-2 text-xs" :class="cwReminderBeforeStartEnabled ? 'text-green-600' : 'text-gray-400'" x-text="cwReminderBeforeStartEnabled ? 'ON' : 'OFF'"></span>
                                                        </div>
                                                    </div>
                                                    <textarea name="chatwork_reminder_before_start_message" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="開始前リマインド時にChatworkへ送信されるメッセージ">{{ old('chatwork_reminder_before_start_message', optional($settings['chatwork_reminder_before_start_message'] ?? null)->value ?? '') }}</textarea>
                                                    <p class="mt-1 text-xs text-gray-500">予約開始N分前のリマインド通知。空欄時はデフォルトの文面が使用されます。</p>
                                                </div>

                                                {{-- 相談記録通知 --}}
                                                <div class="p-4 border border-gray-200 rounded-lg">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <label class="text-xs font-medium text-gray-700">相談記録通知</label>
                                                            <p class="text-xs text-gray-500 mt-1">相談記録が入力された時にTOALLで配信されます。</p>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <button type="button" @click="cwConsultationRecordEnabled = !cwConsultationRecordEnabled"
                                                                    :class="cwConsultationRecordEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" role="switch">
                                                                <span :class="cwConsultationRecordEnabled ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                            <input type="hidden" name="chatwork_consultation_record_enabled" :value="cwConsultationRecordEnabled ? '1' : '0'">
                                                            <span class="ml-2 text-xs" :class="cwConsultationRecordEnabled ? 'text-green-600' : 'text-gray-400'" x-text="cwConsultationRecordEnabled ? 'ON' : 'OFF'"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- 日程リクエスト通知 --}}
                                                <div class="p-4 border border-gray-200 rounded-lg">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <label class="text-xs font-medium text-gray-700">日程リクエスト通知</label>
                                                        <div class="flex items-center">
                                                            <button type="button" @click="cwScheduleRequestEnabled = !cwScheduleRequestEnabled"
                                                                    :class="cwScheduleRequestEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" role="switch">
                                                                <span :class="cwScheduleRequestEnabled ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                            </button>
                                                            <input type="hidden" name="chatwork_schedule_request_enabled" :value="cwScheduleRequestEnabled ? '1' : '0'">
                                                            <span class="ml-2 text-xs" :class="cwScheduleRequestEnabled ? 'text-green-600' : 'text-gray-400'" x-text="cwScheduleRequestEnabled ? 'ON' : 'OFF'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="p-2 bg-amber-50 border border-amber-100 rounded-lg mb-2">
                                                        <p class="text-xs text-amber-700">利用可能なプレースホルダー: <code class="bg-amber-100 px-1 rounded">{name}</code>（ゲスト名）、<code class="bg-amber-100 px-1 rounded">{email}</code>（メール）、<code class="bg-amber-100 px-1 rounded">{phone}</code>（電話番号）、<code class="bg-amber-100 px-1 rounded">{candidate_1}</code>（候補1）、<code class="bg-amber-100 px-1 rounded">{candidate_2}</code>（候補2）、<code class="bg-amber-100 px-1 rounded">{candidate_3}</code>（候補3）、<code class="bg-amber-100 px-1 rounded">{message}</code>（ご相談内容）</p>
                                                    </div>
                                                    <textarea name="chatwork_schedule_request_message" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="日程リクエスト受信時にChatworkへ送信されるメッセージ">{{ old('chatwork_schedule_request_message', optional($settings['chatwork_schedule_request_message'] ?? null)->value ?? '') }}</textarea>
                                                    <p class="mt-1 text-xs text-gray-500">ゲストから日程リクエストを受信した時に配信されます。空欄時はデフォルトの文面が使用されます。</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Google Integration --}}
                                <div>
                                    <h3 class="text-md font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">Google連携設定</h3>
                                    <div class="space-y-4">
                                        {{-- Google Account Connection --}}
                                        @php
                                            $googleRefreshToken = optional($settings['google_refresh_token'] ?? null)->value;
                                            $googleAdminEmail = optional($settings['google_admin_email'] ?? null)->value;
                                            $isGoogleConnected = !empty($googleRefreshToken);
                                        @endphp
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Googleアカウント連携</label>
                                            @if($isGoogleConnected)
                                                <div class="flex items-center space-x-3 p-3 bg-green-50 border border-green-200 rounded-md">
                                                    <svg class="h-5 w-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-medium text-green-800">接続済み</p>
                                                        @if($googleAdminEmail)
                                                            <p class="text-xs text-green-600">{{ $googleAdminEmail }}</p>
                                                        @endif
                                                    </div>
                                                    <button type="button"
                                                            onclick="if(confirm('Googleアカウントの連携を解除しますか？')){fetch('{{ route('admin.google.disconnect') }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'text/html'}}).then(()=>location.reload())}"
                                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-100 border border-red-200 rounded-md hover:bg-red-200 transition flex-shrink-0">
                                                        連携解除
                                                    </button>
                                                </div>
                                            @else
                                                <div class="flex items-center space-x-3 p-3 bg-gray-50 border border-gray-200 rounded-md">
                                                    <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm text-gray-600">未接続</p>
                                                        <p class="text-xs text-gray-400">Googleアカウントを連携してカレンダー同期を利用できます。</p>
                                                    </div>
                                                    <a href="{{ route('admin.google.auth') }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 transition">
                                                        <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" />
                                                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                                        </svg>
                                                        Googleアカウントを連携
                                                    </a>
                                                </div>
                                            @endif
                                            <p class="mt-1 text-xs text-gray-500">予約確定時にこのアカウントのカレンダーにイベントが作成されます。</p>
                                        </div>

                                        {{-- Google Calendar Selector --}}
                                        @if($isGoogleConnected)
                                            <div x-data="adminCalendarSelector()" x-init="init()">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">同期先カレンダー</label>
                                                <div class="flex items-center space-x-3">
                                                    <select x-model="selectedCalendar"
                                                            :disabled="loading"
                                                            class="block w-full max-w-md border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm disabled:bg-gray-100">
                                                        <option value="" x-show="loading">読み込み中...</option>
                                                        <template x-for="cal in calendars" :key="cal.id">
                                                            <option :value="cal.id" x-text="cal.summary + (cal.primary ? ' (メイン)' : '')"></option>
                                                        </template>
                                                    </select>
                                                    <button type="button" @click="saveCalendar()"
                                                            :disabled="saving || loading"
                                                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition">
                                                        <span x-show="!saving">保存</span>
                                                        <span x-show="saving" x-cloak>保存中...</span>
                                                    </button>
                                                </div>
                                                <p x-show="error" x-text="error" class="mt-1 text-sm text-red-600" x-cloak></p>
                                                <p x-show="successMsg" x-text="successMsg" class="mt-1 text-sm text-green-600" x-cloak></p>
                                                <p class="mt-1 text-xs text-gray-500">予約が確定した際にイベントが作成されるカレンダーを選択してください。</p>
                                            </div>

                                            <script>
                                                function adminCalendarSelector() {
                                                    return {
                                                        calendars: [],
                                                        selectedCalendar: 'primary',
                                                        loading: true,
                                                        saving: false,
                                                        error: '',
                                                        successMsg: '',
                                                        init() {
                                                            fetch('{{ route("admin.google.calendars") }}', {
                                                                headers: { 'Accept': 'application/json' }
                                                            })
                                                            .then(r => r.json())
                                                            .then(data => {
                                                                this.calendars = data.calendars || [];
                                                                if (data.selected && this.calendars.length > 0) {
                                                                    this.selectedCalendar = data.selected;
                                                                }
                                                                this.loading = false;
                                                            })
                                                            .catch(() => {
                                                                this.error = 'カレンダー一覧の取得に失敗しました。';
                                                                this.loading = false;
                                                            });
                                                        },
                                                        saveCalendar() {
                                                            this.saving = true;
                                                            this.error = '';
                                                            this.successMsg = '';
                                                            fetch('{{ route("admin.google.calendar.update") }}', {
                                                                method: 'PUT',
                                                                headers: {
                                                                    'Content-Type': 'application/json',
                                                                    'Accept': 'application/json',
                                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                                },
                                                                body: JSON.stringify({ google_calendar_id: this.selectedCalendar })
                                                            })
                                                            .then(r => {
                                                                if (r.ok) {
                                                                    this.successMsg = 'カレンダーを変更しました。';
                                                                } else {
                                                                    this.error = 'カレンダーの保存に失敗しました。';
                                                                }
                                                                this.saving = false;
                                                            })
                                                            .catch(() => {
                                                                this.error = 'カレンダーの保存に失敗しました。';
                                                                this.saving = false;
                                                            });
                                                        }
                                                    };
                                                }
                                            </script>
                                        @endif

                                        {{-- Google Calendar Toggle --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">カレンダー自動同期</label>
                                            <div class="flex items-center">
                                                <button type="button"
                                                        @click="googleCalendarEnabled = !googleCalendarEnabled"
                                                        :class="googleCalendarEnabled ? 'bg-blue-600' : 'bg-gray-200'"
                                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                        role="switch"
                                                        :aria-checked="googleCalendarEnabled">
                                                    <span :class="googleCalendarEnabled ? 'translate-x-5' : 'translate-x-0'"
                                                          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                </button>
                                                <input type="hidden" name="google_calendar_enabled" :value="googleCalendarEnabled ? '1' : '0'">
                                                <span class="ml-3 text-sm" :class="googleCalendarEnabled ? 'text-green-600 font-medium' : 'text-gray-500'" x-text="googleCalendarEnabled ? '有効' : '無効'"></span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500">有効にすると予約がGoogleカレンダーに自動同期されます。</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="flex items-center justify-end pt-6 mt-6 border-t border-gray-200">
                                <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                    連携設定を保存
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Audit Log --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">監査ログ</h2>
                        <p class="mt-1 text-xs text-gray-500">最近のシステム操作履歴</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日時</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs font-medium text-gray-900">
                                            {{ $log->user->name ?? '不明' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500">
                                            {{ $log->action }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                            {{ $log->created_at->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-400 font-mono">
                                            {{ $log->ip_address }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-center text-xs text-gray-500">
                                            ログがありません
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
