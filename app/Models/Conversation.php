<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user1_id', 'user2_id', 'subject', 'is_blocked', 'blocked_by', 'last_message_at'];

    protected $with = ['user1', 'user2', 'lastMessage'];

    protected $appends = ['other_user', 'unread_count'];

    protected $casts = [
        'is_blocked' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    // Relations
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    // Attributs calculés
    public function getOtherUserAttribute()
    {
        $currentUserId = auth()->id();

        if ($this->user1_id == $currentUserId) {
            return $this->user2;
        }

        return $this->user1;
    }

    public function getUnreadCountAttribute()
    {
        if (array_key_exists('unread_messages_count', $this->attributes)) {
            return (int) $this->attributes['unread_messages_count'];
        }
        if (! auth()->check()) {
            return 0;
        }

        return $this->messages()
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->count();
    }

    // Méthodes d'aide
    public function markAsRead($userId = null)
    {
        $userId = $userId ?? auth()->id();

        $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function canSendMessage($userId)
    {
        return in_array((int) $userId, [(int) $this->user1_id, (int) $this->user2_id], true) && ! $this->is_blocked;
    }

    public function scopeForParticipant($query, $userId)
    {
        return $query->where(fn ($participants) => $participants
            ->where('user1_id', $userId)->orWhere('user2_id', $userId));
    }

    public static function getOrCreate($user1Id, $user2Id, $subject = null)
    {
        $conversation = self::where(function ($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user1Id)
                ->where('user2_id', $user2Id);
        })->orWhere(function ($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user2Id)
                ->where('user2_id', $user1Id);
        })->first();

        if (! $conversation) {
            $conversation = self::create([
                'user1_id' => min($user1Id, $user2Id),
                'user2_id' => max($user1Id, $user2Id),
                'subject' => $subject,
            ]);
        }

        return $conversation;
    }
}
