<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'slug',
        'bio',
        'avatar',
        'stripe_account_id',
        'stripe_mode',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!$user->slug) {
                $user->slug = self::generateSlug($user->name);
            }
        });
    }

    public static function generateSlug($name, $excludeId = null)
    {
        $slug = \Str::slug($name);
        $original = $slug;
        $count = 1;

        $query = static::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $original . '-' . $count;
            $query = static::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            $count++;
        }

        return $slug;
    }

    public function painterProfile()
    {
        return $this->hasOne(PainterProfile::class);
    }

    public function paintings()
    {
        return $this->hasMany(Painting::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sales()
    {
        return $this->hasMany(Order::class, 'painter_id');
    }

    public function grisailleAnalyses()
    {
        return $this->hasMany(GrisailleAnalysis::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
