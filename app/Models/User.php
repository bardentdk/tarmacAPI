<?php

namespace App\Models;

use App\Enums\Plan;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Plan $plan
 * @property string|null $plan_period
 * @property CarbonImmutable|null $trial_ends_at
 * @property CarbonImmutable|null $plan_expires_at
 * @property string|null $revenuecat_app_user_id
 * @property CarbonImmutable|null $last_synced_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'plan' => 'free',
    ];

    /**
     * Every new account starts with a trial of the configured plan.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->trial_ends_at === null) {
                $user->trial_ends_at = now()->addDays((int) config('tarmac.trial_days'));
            }
        });

        static::deleting(function (User $user): void {
            $user->tokens()->delete();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'plan' => Plan::class,
            'trial_ends_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasMany<Run, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(Run::class);
    }

    /**
     * @return HasMany<DtcRecord, $this>
     */
    public function dtcRecords(): HasMany
    {
        return $this->hasMany(DtcRecord::class);
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * @return HasManyThrough<SharedRun, Run, $this>
     */
    public function sharedRuns(): HasManyThrough
    {
        return $this->hasManyThrough(SharedRun::class, Run::class);
    }

    /**
     * First name, used to greet the user.
     */
    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0] ?: $this->name;
    }
}
