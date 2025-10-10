<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guidance extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'images',
        'target_type',
        'target_user_id',
        'created_by',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('target_type', 'all_users')
              ->orWhere(function($subQ) use ($userId) {
                  $subQ->where('target_type', 'specific_user')
                       ->where('target_user_id', $userId);
              });
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getTargetDisplayAttribute(): string
    {
        if ($this->target_type === 'all_users') {
            return 'All Users';
        }
        
        return $this->targetUser ? "User: {$this->targetUser->name}" : 'Specific User (Deleted)';
    }

    public function getImageCountAttribute(): int
    {
        return is_array($this->images) ? count($this->images) : 0;
    }

    public function getFirstImageAttribute(): ?string
    {
        if (is_array($this->images) && count($this->images) > 0) {
            return $this->images[0];
        }
        return null;
    }

    // Helper methods
    public function hasImages(): bool
    {
        return is_array($this->images) && count($this->images) > 0;
    }

    public function addImage(string $imagePath): void
    {
        $images = $this->images ?? [];
        $images[] = $imagePath;
        $this->images = $images;
    }

    public function removeImage(string $imagePath): void
    {
        $images = $this->images ?? [];
        $this->images = array_values(array_filter($images, fn($img) => $img !== $imagePath));
    }
}