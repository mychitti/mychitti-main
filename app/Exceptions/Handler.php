<?php

namespace App\Exceptions;

use App\Models\WebsiteError;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * These exception classes are noise — skip DB logging for them.
     */
    protected array $dontDbLog = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            $this->logToDatabase($e);
        });
    }

    private function logToDatabase(Throwable $e): void
    {
        foreach ($this->dontDbLog as $class) {
            if ($e instanceof $class) {
                return;
            }
        }

        try {
            $request = request();

            $adminId = null;
            $staffId = null;
            $userId  = null;
            $storeId = null;

            if (auth('admin')->check()) {
                $adminId = auth('admin')->id();
            }

            if (auth('vendor_employee')->check()) {
                $staffId = auth('vendor_employee')->id();
            }

            if (auth('api')->check()) {
                $userId = auth('api')->id();
            } elseif (auth()->check()) {
                $userId = auth()->id();
            }

            if (auth('vendor')->check()) {
                $storeId = \App\Models\Store::withoutGlobalScopes()
                    ->where('vendor_id', auth('vendor')->id())
                    ->value('id');
            }

            // Capture request data — strip sensitive fields
            $requestData = null;
            if ($request) {
                $sensitive = ['password', 'password_confirmation', 'token', 'secret', 'api_key', 'auth_token', '_token'];
                $all = $request->except($sensitive);
                $requestData = json_encode([
                    'get'  => $request->query(),
                    'post' => collect($all)->except($sensitive)->toArray(),
                ]);
            }

            WebsiteError::create([
                'class'        => get_class($e),
                'message'      => $e->getMessage(),
                'file'         => str_replace(base_path(), '', $e->getFile()),
                'line'         => $e->getLine(),
                'trace'        => collect($e->getTrace())->take(10)->map(fn($f) =>
                    ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?') . ' → ' . ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '')
                )->implode("\n"),
                'url'          => $request?->fullUrl(),
                'method'       => $request?->method(),
                'referer'      => $request?->headers->get('referer'),
                'request_data' => $requestData,
                'admin_id'     => $adminId,
                'staff_id'     => $staffId,
                'user_id'      => $userId,
                'store_id'     => $storeId,
                'ip'           => $request?->ip(),
                'user_agent'   => $request?->userAgent(),
            ]);
        } catch (Throwable $inner) {
            // Never let the logger crash the app
            Log::error('WebsiteError DB log failed: ' . $inner->getMessage());
        }
    }
}
