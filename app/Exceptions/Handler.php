<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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

        // Add this custom rule to catch 419 Page Expired errors
        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            // If they were trying to log in or submit a form, send them back politely
            return redirect()->back()->withInput()->with('warning', 'For your security, your session expired due to inactivity. Please try again.');
        });
    }
}
