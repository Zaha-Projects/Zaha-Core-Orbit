<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (QueryException $exception, Request $request) {
            if (! $this->isDatabaseConnectionError($exception)) {
                return null;
            }

            $message = __('app.common.database_unavailable');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 503);
            }

            if ($request->isMethod('get')) {
                return response()->view('errors.database-unavailable', [
                    'message' => $message,
                ], 503);
            }

            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['database' => $message]);
        });
    }

    private function isDatabaseConnectionError(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo ?? [];
        $driverCode = $errorInfo[1] ?? null;

        return in_array($driverCode, [2002, 2006, 1045], true);
    }
}
