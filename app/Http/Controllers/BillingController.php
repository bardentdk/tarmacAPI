<?php

namespace App\Http\Controllers;

use App\Enums\Plan;
use App\Models\User;
use App\Services\Entitlements;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    /**
     * Plans, current subscription and trial.
     */
    public function index(Request $request, Entitlements $entitlements): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('billing/Index', [
            'subscription' => [
                ...$entitlements->toArray($user),
                'plan_period' => $entitlements->hasActivePaidPlan($user) ? $user->plan_period : null,
                'is_paid' => $entitlements->hasActivePaidPlan($user),
            ],
            'plans' => collect(Plan::cases())->map(fn (Plan $plan): array => [
                'value' => $plan->value,
                'label' => $plan->label(),
                'monthly' => config("tarmac.plans.{$plan->value}.monthly"),
                'yearly' => config("tarmac.plans.{$plan->value}.yearly"),
                'features' => $plan->features(),
            ]),
            'checkoutEnabled' => filled(config('tarmac.stripe.key')) && filled(config('tarmac.stripe.secret')),
        ]);
    }
}
