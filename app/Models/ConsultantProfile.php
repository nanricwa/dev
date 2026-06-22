<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultantProfile extends Model
{
    protected $fillable = [
        'user_id',
        'specialty',
        'bio',
        'hourly_rate',
        'experience_years',
        'photo',
        'qualifications',
        'languages',
        'meeting_url',
        'chatwork_account_id',
        'google_refresh_token',
        'google_calendar_email',
        'google_calendar_id',
        'google_conflict_calendar_ids',
        'is_featured',
        'booking_acceptance_enabled',
        'average_rating',
        'total_reviews',
        'total_bookings',
    ];

    protected $hidden = [
        'google_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'qualifications' => 'array',
            'languages' => 'array',
            'google_conflict_calendar_ids' => 'array',
            'is_featured' => 'boolean',
            'booking_acceptance_enabled' => 'boolean',
            'average_rating' => 'decimal:2',
        ];
    }

    /**
     * Get the full URL for the profile photo with cache busting.
     */
    public function getPhotoUrl(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        $url = url('media/' . $this->photo);

        // Add cache busting based on updated_at timestamp
        if ($this->updated_at) {
            $url .= '?v=' . $this->updated_at->timestamp;
        }

        return $url;
    }

    public function isGoogleConnected(): bool
    {
        return !empty($this->google_refresh_token);
    }

    public function getConflictCalendarIds(): array
    {
        return $this->google_conflict_calendar_ids ?? [];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updateRating(): void
    {
        $reviews = $this->user->receivedReviews();
        $this->average_rating = $reviews->avg('rating') ?? 0;
        $this->total_reviews = $reviews->count();
        $this->save();
    }
}
