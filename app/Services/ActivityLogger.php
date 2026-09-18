<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Keys that should always be masked or filtered out from log properties.
     */
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'token',
        'secret',
        'api_key',
        'authorization',
        'credit_card',
    ];

    /**
     * Record an activity log.
     */
    public static function log(
        string $description,
        string $event = 'custom',
        string $logName = 'default',
        ?Model $subject = null,
        array $properties = [],
        string $status = 'success',
        ?User $causer = null
    ): ?ActivityLog {
        try {
            $isConsole = App::runningInConsole();
            $request = request();

            // Determine Causer (User who performed the action)
            $causerUser = $causer ?? (Auth::check() ? Auth::user() : null);

            $causerId = $causerUser?->id;
            $causerType = $causerUser ? get_class($causerUser) : null;
            $causerName = $causerUser ? $causerUser->name : ($isConsole ? 'Sistem' : 'Tamu / Sistem');
            $causerEmail = $causerUser?->email;
            $causerRole = null;

            if ($causerUser && method_exists($causerUser, 'getRoleNames')) {
                $causerRole = $causerUser->getRoleNames()->first();
            }

            // Subject details
            $subjectType = $subject ? get_class($subject) : null;
            $subjectId = $subject?->getKey();
            $subjectLabel = null;

            if ($subject) {
                if (method_exists($subject, 'getActivitySubjectLabel')) {
                    $subjectLabel = $subject->getActivitySubjectLabel();
                } elseif (isset($subject->name)) {
                    $subjectLabel = (string) $subject->name;
                } elseif (isset($subject->title)) {
                    $subjectLabel = (string) $subject->title;
                } elseif (isset($subject->email)) {
                    $subjectLabel = (string) $subject->email;
                } else {
                    $subjectLabel = class_basename($subject).' #'.$subjectId;
                }
            }

            // Request & network info
            $ipAddress = null;
            $userAgent = null;
            $method = null;
            $url = null;
            $device = 'Sistem / CLI';

            if (! $isConsole && $request) {
                $ipAddress = $request->ip();
                $userAgent = $request->userAgent();
                $method = $request->method();
                $url = $request->fullUrl();
                $device = self::parseDevice($userAgent);
            }

            // Sanitize properties (remove passwords & secrets)
            $sanitizedProperties = self::sanitizeProperties($properties);

            return ActivityLog::create([
                'log_name' => $logName,
                'description' => $description,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId ? (string) $subjectId : null,
                'subject_label' => $subjectLabel,
                'event' => $event,
                'causer_type' => $causerType,
                'causer_id' => $causerId,
                'causer_name' => $causerName,
                'causer_email' => $causerEmail,
                'causer_role' => $causerRole,
                'properties' => ! empty($sanitizedProperties) ? $sanitizedProperties : null,
                'method' => $method,
                'url' => $url,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'device' => $device,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            // Never break main application flow on logging failure
            Log::error('Gagal mencatat ActivityLog: '.$e->getMessage(), [
                'exception' => $e,
                'description' => $description,
                'event' => $event,
            ]);

            return null;
        }
    }

    /**
     * Purge logs older than given days.
     */
    public static function cleanOldLogs(int $days = 30): int
    {
        if ($days <= 0) {
            return ActivityLog::query()->delete();
        }

        return ActivityLog::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Recursively mask sensitive fields in properties array.
     */
    public static function sanitizeProperties(array $properties): array
    {
        $sanitized = [];

        foreach ($properties as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;

            foreach (self::$sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '********';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeProperties($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Parse User Agent into friendly device & OS string.
     */
    public static function parseDevice(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Tidak Diketahui';
        }

        // Detect Platform / OS
        $platform = 'Lainnya';
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $platform = 'iOS';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Windows NT 10.0/i', $userAgent)) {
            $platform = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6.3/i', $userAgent)) {
            $platform = 'Windows 8.1';
        } elseif (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        }

        // Detect Browser
        $browser = 'Browser';
        if (preg_match('/Edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome/i', $userAgent) && ! preg_match('/Edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent) && ! preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }

        return "{$browser} • {$platform}";
    }
}
