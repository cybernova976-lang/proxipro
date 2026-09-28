<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'content', 'is_read', 'read_at', 'client_token', 'edited_at'];

    protected $with = ['sender'];

    protected $appends = ['time_ago', 'is_own'];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Mettre à jour la dernière activité de la conversation
            $message->conversation()->update([
                'last_message_at' => now(),
            ]);
        });
    }

    // Relations
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Attributs calculés
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getIsOwnAttribute()
    {
        return $this->sender_id == auth()->id();
    }

    // Méthodes d'aide
    public function markAsRead()
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}
