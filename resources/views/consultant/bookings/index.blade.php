@extends('layouts.app')

@section('content')
<div class="w-full px-4 sm:px-6 py-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">予約管理</h1>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-300 text-green-700 rounded-md p-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-300 text-red-700 rounded-md p-4">
            {{ session('error') }}
        </div>
    @endif

    {{-- 検索フォーム --}}
    <div class="bg-white rounded-lg shadow mb-6 px-4 py-3">
        <form method="GET" action="{{ route('consultant.bookings.index') }}" class="flex items-center gap-3">
            @if($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}"
                    placeholder="名前またはメールアドレスで検索"
                    class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">
                検索
            </button>
            @if($search)
                <a href="{{ route('consultant.bookings.index', $status !== 'all' ? ['status' => $status] : []) }}"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 transition">
                    クリア
                </a>
            @endif
        </form>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                <a href="{{ route('consultant.bookings.index', $search ? ['search' => $search] : []) }}"
                    class="whitespace-nowrap py-4 px-6 border-b-2 text-sm font-medium
                        {{ !$status ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    すべて
                </a>
                <a href="{{ route('consultant.bookings.index', array_merge(['status' => 'approved'], $search ? ['search' => $search] : [])) }}"
                    class="whitespace-nowrap py-4 px-6 border-b-2 text-sm font-medium
                        {{ $status === 'approved' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    確定済み
                </a>
                <a href="{{ route('consultant.bookings.index', array_merge(['status' => 'completed'], $search ? ['search' => $search] : [])) }}"
                    class="whitespace-nowrap py-4 px-6 border-b-2 text-sm font-medium
                        {{ $status === 'completed' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    完了
                </a>
                <a href="{{ route('consultant.bookings.index', array_merge(['status' => 'cancelled'], $search ? ['search' => $search] : [])) }}"
                    class="whitespace-nowrap py-4 px-6 border-b-2 text-sm font-medium
                        {{ $status === 'cancelled' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    キャンセル
                </a>
            </nav>
        </div>
    </div>

    {{-- 相談結果フィルター（完了タブのみ） --}}
    @if($status === 'completed')
        @php
            $resultParams = array_merge(['status' => 'completed'], $search ? ['search' => $search] : []);
        @endphp
        <div class="flex items-center gap-2 mb-6 flex-wrap">
            <span class="text-sm font-medium text-gray-600">相談結果:</span>
            <a href="{{ route('consultant.bookings.index', $resultParams) }}"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition {{ !$result ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                すべて
            </a>
            <a href="{{ route('consultant.bookings.index', array_merge($resultParams, ['result' => 'success'])) }}"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition {{ $result === 'success' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                成約
            </a>
            <a href="{{ route('consultant.bookings.index', array_merge($resultParams, ['result' => 'failure'])) }}"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition {{ $result === 'failure' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
                不成約
            </a>
            <a href="{{ route('consultant.bookings.index', array_merge($resultParams, ['result' => 'pending'])) }}"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition {{ $result === 'pending' ? 'bg-yellow-600 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100' }}">
                検討中
            </a>
            <a href="{{ route('consultant.bookings.index', array_merge($resultParams, ['result' => 'none'])) }}"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition {{ $result === 'none' ? 'bg-gray-600 text-white' : 'bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
                未記録
            </a>
        </div>
    @endif

    {{-- Bookings List --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($bookings->isEmpty())
            <div class="p-8 text-center text-gray-500">
                該当する予約はありません。
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">予約者</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">紹介者</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">日付</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">時間</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">メモ</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">相談結果</th>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($bookings as $booking)
                            <tr>
                                <td class="px-2 py-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="text-sm font-medium text-gray-900">{{ $booking->bookerName() }}</div>
                                        @if($booking->isGuest())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">個別相談</span>
                                        @endif
                                    </div>
                                    @if($booking->isGuest())
                                        <div class="text-xs text-gray-500 break-all">{{ $booking->guest_email }}</div>
                                        <div class="text-xs text-gray-500">{{ $booking->guest_phone }}</div>
                                    @else
                                        <div class="text-xs text-gray-500 break-all">{{ $booking->user->email }}</div>
                                    @endif
                                    @if($booking->notes)
                                        <div class="text-xs text-gray-500 mt-1">
                                            <span class="font-medium text-gray-600">備考:</span>
                                            <span class="whitespace-pre-wrap break-words">{{ $booking->notes }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-sm text-gray-900">
                                    @if($booking->isGuest() && $booking->guest_referrer)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                            {{ $booking->guest_referrer }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $booking->booking_date->format('Y/m/d') }}
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($booking->end_time)->format('H:i') }}
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($booking->status === 'approved') bg-green-100 text-green-800
                                        @elseif($booking->status === 'completed') bg-blue-100 text-blue-800
                                        @elseif($booking->status === 'cancelled') bg-gray-100 text-gray-800
                                        @endif">
                                        @if($booking->status === 'approved') 確定
                                        @elseif($booking->status === 'completed') 完了
                                        @elseif($booking->status === 'cancelled') キャンセル
                                        @endif
                                    </span>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap" x-data="{ showNotesModal: false }">
                                    @php
                                        $currentNotes = $booking->admin_notes;
                                    @endphp
                                    @if($currentNotes)
                                        <button type="button" @click="showNotesModal = true"
                                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            編集
                                        </button>
                                    @else
                                        <button type="button" @click="showNotesModal = true"
                                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-50 text-gray-400 hover:bg-gray-100 border border-dashed border-gray-300 transition">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            未記入
                                        </button>
                                    @endif

                                    {{-- Notes Edit Modal --}}
                                    <div x-show="showNotesModal" x-cloak @keydown.escape.window="showNotesModal = false"
                                        class="fixed inset-0 z-50 overflow-y-auto" x-transition>
                                        <div class="flex items-center justify-center min-h-screen px-4">
                                            <div class="fixed inset-0 bg-black/50" @click="showNotesModal = false"></div>
                                            <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6 z-10">
                                                <h3 class="text-lg font-semibold text-gray-900 mb-2">管理メモ</h3>
                                                <p class="text-sm text-gray-500 mb-4">{{ $booking->bookerName() }}</p>
                                                <form method="POST" action="{{ route('consultant.bookings.user-notes.update', $booking) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="mb-4">
                                                        <textarea name="admin_notes" rows="6"
                                                            class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                            placeholder="社内用メモを入力...">{{ $currentNotes }}</textarea>
                                                    </div>
                                                    <div class="flex justify-end space-x-3">
                                                        <button type="button" @click="showNotesModal = false"
                                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">閉じる</button>
                                                        <button type="submit"
                                                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">保存</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap">
                                    @if($booking->consultation_result === 'success')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">成約</span>
                                    @elseif($booking->consultation_result === 'failure')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">不成約</span>
                                    @elseif($booking->consultation_result === 'pending')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">検討中</span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 text-sm">
                                    <div class="flex flex-wrap items-center gap-1">
                                        {{-- Cancel button (approved only) --}}
                                        @if($booking->isApproved())
                                            <div x-data="{ showCancelModal: false }">
                                                <button type="button" @click="showCancelModal = true"
                                                    class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                                    キャンセル
                                                </button>

                                                {{-- Cancel Modal --}}
                                                <div x-show="showCancelModal" x-cloak
                                                    class="fixed inset-0 z-50 overflow-y-auto"
                                                    x-transition:enter="ease-out duration-300"
                                                    x-transition:enter-start="opacity-0"
                                                    x-transition:enter-end="opacity-100"
                                                    x-transition:leave="ease-in duration-200"
                                                    x-transition:leave-start="opacity-100"
                                                    x-transition:leave-end="opacity-0">
                                                    <div class="flex items-center justify-center min-h-screen px-4">
                                                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showCancelModal = false"></div>
                                                        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 z-10">
                                                            <h3 class="text-lg font-medium text-gray-900 mb-2">予約をキャンセル</h3>
                                                            <p class="text-sm text-gray-500 mb-4">この操作は取り消せません。</p>
                                                            <form action="{{ route('consultant.bookings.cancel', $booking) }}" method="POST">
                                                                @csrf
                                                                <div class="mb-4">
                                                                    <label for="cancel_reason_cancel_{{ $booking->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                                                        キャンセル理由 <span class="text-red-500">*</span>
                                                                    </label>
                                                                    <textarea id="cancel_reason_cancel_{{ $booking->id }}" name="cancel_reason" rows="3" required
                                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                                                                        placeholder="キャンセル理由を入力してください（必須）"></textarea>
                                                                </div>
                                                                <div class="mb-4">
                                                                    <label class="inline-flex items-center cursor-pointer">
                                                                        <input type="checkbox" name="skip_notification" value="1"
                                                                               class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500">
                                                                        <span class="ml-2 text-sm text-gray-600">通知を送信しない</span>
                                                                    </label>
                                                                </div>
                                                                <div class="flex justify-end space-x-3">
                                                                    <button type="button" @click="showCancelModal = false"
                                                                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                                        閉じる
                                                                    </button>
                                                                    <button type="submit"
                                                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                                                        キャンセルする
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Email Send Button --}}
                                            <div x-data="{ showEmailModal: false }">
                                                <button type="button" @click="showEmailModal = true"
                                                    class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                    メール
                                                </button>

                                                {{-- Email Send Modal --}}
                                                <div x-show="showEmailModal" x-cloak @keydown.escape.window="showEmailModal = false"
                                                    class="fixed inset-0 z-50 overflow-y-auto" x-transition>
                                                    <div class="flex items-center justify-center min-h-screen px-4">
                                                        <div class="fixed inset-0 bg-black/50" @click="showEmailModal = false"></div>
                                                        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6 z-10">
                                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">メール送信</h3>
                                                            <p class="text-sm text-gray-500 mb-1">
                                                                送信先: {{ $booking->bookerName() }}
                                                                ({{ $booking->isGuest() ? $booking->guest_email : $booking->user?->email }})
                                                            </p>
                                                            <p class="text-xs text-gray-400 mb-4">
                                                                置換タグ: {name}=予約者名, {date}=予約日時, {consultant}=コンサルタント名
                                                            </p>
                                                            <form method="POST" action="{{ route('consultant.bookings.send-email', $booking) }}">
                                                                @csrf
                                                                <div class="mb-4">
                                                                    <label class="block text-sm font-medium text-gray-700 mb-1">件名 <span class="text-red-500">*</span></label>
                                                                    <input type="text" name="subject" required maxlength="200"
                                                                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
                                                                        placeholder="例: {name}様 ご予約に関するご連絡">
                                                                </div>
                                                                <div class="mb-4">
                                                                    <label class="block text-sm font-medium text-gray-700 mb-1">本文 <span class="text-red-500">*</span></label>
                                                                    <textarea name="message" rows="8" required maxlength="5000"
                                                                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
                                                                        placeholder="メール本文を入力してください..."></textarea>
                                                                </div>
                                                                <div class="flex justify-end space-x-3">
                                                                    <button type="button" @click="showEmailModal = false"
                                                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">閉じる</button>
                                                                    <button type="submit"
                                                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">送信</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        {{-- Consultation Record Button (approved:当日以降 / completed) --}}
                                        @if($booking->canEnterConsultationRecord())
                                            <div x-data="{ showRecordModal: false }">
                                                <button type="button" @click="showRecordModal = true"
                                                    class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                                    <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    記録
                                                </button>

                                                {{-- Record Modal --}}
                                                <div x-show="showRecordModal" x-cloak @keydown.escape.window="showRecordModal = false"
                                                    class="fixed inset-0 z-50 overflow-y-auto" x-transition>
                                                    <div class="flex items-center justify-center min-h-screen px-4">
                                                        <div class="fixed inset-0 bg-black/50" @click="showRecordModal = false"></div>
                                                        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6 z-10">
                                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">相談記録</h3>
                                                            <p class="text-sm text-gray-500 mb-4">{{ $booking->bookerName() }} / {{ $booking->booking_date->format('Y/m/d') }}</p>
                                                            <form method="POST" action="{{ route('consultant.bookings.consultation-record.update', $booking) }}">
                                                                @csrf
                                                                @method('PUT')
                                                                <div class="mb-4">
                                                                    <label class="block text-sm font-medium text-gray-700 mb-2">相談結果 <span class="text-red-500">*</span></label>
                                                                    <div class="flex gap-4">
                                                                        <label class="inline-flex items-center">
                                                                            <input type="radio" name="consultation_result" value="success" class="form-radio text-green-600 focus:ring-green-500" {{ $booking->consultation_result === 'success' ? 'checked' : '' }}>
                                                                            <span class="ml-2 text-sm text-gray-700">成約</span>
                                                                        </label>
                                                                        <label class="inline-flex items-center">
                                                                            <input type="radio" name="consultation_result" value="failure" class="form-radio text-red-600 focus:ring-red-500" {{ $booking->consultation_result === 'failure' ? 'checked' : '' }}>
                                                                            <span class="ml-2 text-sm text-gray-700">不成約</span>
                                                                        </label>
                                                                        <label class="inline-flex items-center">
                                                                            <input type="radio" name="consultation_result" value="pending" class="form-radio text-yellow-600 focus:ring-yellow-500" {{ $booking->consultation_result === 'pending' ? 'checked' : '' }}>
                                                                            <span class="ml-2 text-sm text-gray-700">検討中</span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-4">
                                                                    <label class="block text-sm font-medium text-gray-700 mb-2">相談メモ <span class="text-red-500">*</span></label>
                                                                    <textarea name="consultation_notes" rows="5" required
                                                                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-purple-500 focus:border-purple-500"
                                                                        placeholder="相談内容、結果の詳細、フォローアップ事項など...">{{ $booking->consultation_notes }}</textarea>
                                                                </div>
                                                                <div class="mb-4">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="hidden" name="important_document_issued" value="0">
                                                                        <input type="checkbox" name="important_document_issued" value="1"
                                                                            {{ $booking->important_document_issued ? 'checked' : '' }}
                                                                            class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                                                                        <span class="ml-2 text-sm font-medium text-gray-700">重要事項説明書を発行済み</span>
                                                                    </label>
                                                                </div>
                                                                <div class="flex justify-end space-x-3">
                                                                    <button type="button" @click="showRecordModal = false"
                                                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">閉じる</button>
                                                                    <button type="submit"
                                                                        class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700">保存</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($bookings->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $bookings->appends(['status' => $status, 'search' => $search, 'result' => $result])->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
