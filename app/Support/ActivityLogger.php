<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Writes the activity log. Every model create / update / delete / restore is
 * recorded automatically (see AppServiceProvider); logins, logouts and changes
 * made straight in the database call log() by hand.
 */
class ActivityLogger
{
    /** Models not worth a line: the log itself, carts, counters and rows derived from other changes. */
    private const SKIP = [
        \App\Models\ActivityLog::class,
        \App\Models\Cart::class,
        \App\Models\RunningNumber::class,
        \App\Models\StockLog::class,          // stock history has its own page (item log)
        \App\Models\OrderItemProfit::class,   // cost rows written with every sale
    ];

    /** Fields never shown in the log. */
    private const HIDDEN = ['password', 'remember_token', 'updated_at', 'created_at'];

    /** Updates that only touch these fields are routine (every sale moves stock) and are not logged. */
    private const ROUTINE = [
        \App\Models\Product::class   => ['stock_quantity'],
        \App\Models\BatchItem::class => ['balance'],
        \App\Models\User::class      => ['last_login_at', 'last_seen_at'],
    ];

    private static bool $paused = false;

    public static function modelEvent(string $action, Model $model): void
    {
        if (self::$paused || in_array(get_class($model), self::SKIP, true)) {
            return;
        }

        $changes = null;
        if ($action === 'updated') {
            $changed = array_diff(array_keys($model->getChanges()), self::HIDDEN);
            if (array_diff($changed, self::ROUTINE[get_class($model)] ?? []) === []) {
                return;   // nothing that matters changed
            }
            $changes = [];
            foreach ($changed as $field) {
                $changes[$field] = [self::clean($model->getOriginal($field)), self::clean($model->getAttribute($field))];
            }
        } elseif ($action === 'created') {
            $changes = [];
            foreach (array_diff_key($model->getAttributes(), array_flip(array_merge(self::HIDDEN, ['id']))) as $field => $value) {
                if ($value !== null && $value !== '') {
                    $changes[$field] = [null, self::clean($value)];
                }
            }
        }

        self::log($action, $model, $changes);
    }

    /** Write one line. Logging must never break the action being logged. */
    public static function log(string $action, ?Model $subject = null, ?array $changes = null, ?string $description = null, $user = null): void
    {
        try {
            $user = $user ?: Auth::user();
            $request = app()->runningInConsole() && !app()->runningUnitTests() ? null : request();

            ActivityLog::create([
                'user_id'       => $user?->id,
                'user_name'     => $user ? ($user->name ?: $user->username) : null,
                'action'        => $action,
                'subject_type'  => $subject ? class_basename($subject) : null,
                'subject_id'    => $subject?->getKey(),
                'subject_label' => $subject ? self::label($subject) : null,
                'changes'       => $changes ?: null,
                'description'   => $description ? mb_substr($description, 0, 255) : null,
                'branch_id'     => $subject?->getAttribute('branch_id') ?? $subject?->getAttribute('from_branch_id') ?? $user?->branch_id,
                'company_id'    => $subject?->getAttribute('company_id') ?? $subject?->getAttribute('from_company_id') ?? $user?->company_id,
                'ip'            => $request?->ip(),
                'method'        => $request?->method(),
                'url'           => $request ? mb_substr($request->fullUrl(), 0, 500) : null,
                'user_agent'    => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Run something without writing log lines (e.g. seeding). */
    public static function withoutLogging(callable $callback)
    {
        $was = self::$paused;
        self::$paused = true;
        try {
            return $callback();
        } finally {
            self::$paused = $was;
        }
    }

    /** A readable name for the record: its name / number field, else "#id". */
    private static function label(Model $model): string
    {
        foreach (['product_name', 'category_name', 'company_name', 'branch_name', 'transfer_no', 'batch_no',
                  'order_no', 'invoice_no', 'name', 'username', 'uom_name', 'method_name', 'title'] as $field) {
            $value = $model->getAttribute($field);
            if (is_scalar($value) && $value !== '') {
                return mb_substr((string) $value, 0, 255);
            }
        }
        return '#' . $model->getKey();
    }

    private static function clean($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_string($value) && mb_strlen($value) > 500) {
            return mb_substr($value, 0, 500) . '…';
        }
        return is_scalar($value) || $value === null ? $value : json_encode($value);
    }
}
