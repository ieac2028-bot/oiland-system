<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\BranchDirectoryController;
use App\Http\Controllers\BranchGeocodeController;
use App\Http\Controllers\BranchMapRouteController;
use App\Http\Controllers\Customer;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicInfoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/about', [PublicInfoController::class, 'about'])->name('about');
Route::get('/careers', [PublicInfoController::class, 'careers'])->name('careers.index');
Route::view('/how-it-works', 'platform')->name('platform');

Route::get('/branches', [BranchDirectoryController::class, 'index'])->name('branches.index');
Route::get('/branches/geocode', BranchGeocodeController::class)->middleware('throttle:10,1')->name('branches.geocode');
Route::get('/branches/{branch}', [BranchDirectoryController::class, 'show'])->name('branches.show');
Route::get('/branches/{branch}/route', BranchMapRouteController::class)->name('branches.route');

/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [Auth\RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [Auth\RegisteredUserController::class, 'store']);

    Route::get('/login', [Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [Auth\AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [Auth\PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [Auth\NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [Auth\NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function (): void {
    Route::view('/email/verify', 'auth.verify-email')->name('verification.notice');
    Route::post('/logout', [Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/email/verify/{id}/{hash}', Auth\VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [Auth\EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/confirm-password', [Auth\ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [Auth\ConfirmablePasswordController::class, 'store']);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
     * Customer engagement
     */
    Route::get('/dashboard', Customer\DashboardController::class)->name('dashboard');

    Route::resource('my/tickets', Customer\TicketController::class)
        ->parameters(['my/tickets' => 'ticket'])
        ->names([
            'index' => 'my.tickets.index',
            'create' => 'my.tickets.create',
            'store' => 'my.tickets.store',
            'show' => 'my.tickets.show',
        ])
        ->except(['edit', 'update', 'destroy']);
    Route::post('/my/tickets/{ticket}/replies', [Customer\TicketReplyController::class, 'store'])
        ->name('my.tickets.replies.store');

    Route::get('/my/reviews', [Customer\ReviewController::class, 'index'])->name('my.reviews.index');
    Route::get('/branches/{branch}/review', [Customer\ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/branches/{branch}/review', [Customer\ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/my/reviews/{review}', [Customer\ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/my/reviews/{review}', [Customer\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/my/branches', [Customer\FollowedBranchController::class, 'index'])->name('my.branches.index');
    Route::post('/branches/{branch}/follow', [Customer\FollowedBranchController::class, 'store'])->name('branches.follow');
    Route::delete('/branches/{branch}/follow', [Customer\FollowedBranchController::class, 'destroy'])->name('branches.unfollow');
});

/*
|--------------------------------------------------------------------------
| Staff administration
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:'.UserRole::SuperAdmin->value.','.UserRole::BranchAdmin->value.','.UserRole::BranchStaff->value])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        /*
         * Branches
         */
        Route::get('/branches', [Admin\BranchController::class, 'index'])->name('branches.index');

        Route::middleware('role:'.UserRole::SuperAdmin->value)->group(function (): void {
            // Registered before the {branch} routes below: a literal segment has
            // to come first or the binding swallows it and /branches/create 404s.
            Route::get('/branches/create', [Admin\BranchController::class, 'create'])->name('branches.create');
            Route::post('/branches', [Admin\BranchController::class, 'store'])->name('branches.store');
        });

        Route::get('/branches/{branch}', [Admin\BranchController::class, 'show'])->name('branches.show');
        Route::patch('/branches/{branch}/toggle-active', [Admin\BranchController::class, 'toggleActive'])->name('branches.toggle-active');
        Route::get('/branches/{branch}/availability', [Admin\BranchAvailabilityController::class, 'edit'])->name('branches.availability.edit');
        Route::put('/branches/{branch}/availability', [Admin\BranchAvailabilityController::class, 'update'])->name('branches.availability.update');

        Route::middleware('role:'.UserRole::SuperAdmin->value)->group(function (): void {
            Route::get('/branches/{branch}/edit', [Admin\BranchController::class, 'edit'])->name('branches.edit');
            Route::put('/branches/{branch}', [Admin\BranchController::class, 'update'])->name('branches.update');
            Route::delete('/branches/{branch}', [Admin\BranchController::class, 'destroy'])->name('branches.destroy');

            /*
             * Catalog
             */
            Route::get('/catalog', [Admin\CatalogItemController::class, 'index'])->name('catalog.index');
            Route::get('/catalog/create', [Admin\CatalogItemController::class, 'create'])->name('catalog.create');
            Route::post('/catalog', [Admin\CatalogItemController::class, 'store'])->name('catalog.store');
            Route::get('/catalog/{catalogItem}/edit', [Admin\CatalogItemController::class, 'edit'])->name('catalog.edit');
            Route::put('/catalog/{catalogItem}', [Admin\CatalogItemController::class, 'update'])->name('catalog.update');
            Route::delete('/catalog/{catalogItem}', [Admin\CatalogItemController::class, 'destroy'])->name('catalog.destroy');

            /*
             * Direct price publishing
             */
            Route::get('/prices/create', [Admin\PriceController::class, 'create'])->name('prices.create');
            Route::post('/prices', [Admin\PriceController::class, 'store'])->name('prices.store');

            /*
             * Announcements
             */
            Route::get('/announcements', [Admin\AnnouncementController::class, 'index'])->name('announcements.index');
            Route::get('/announcements/create', [Admin\AnnouncementController::class, 'create'])->name('announcements.create');
            Route::post('/announcements', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
            Route::get('/announcements/{announcement}/edit', [Admin\AnnouncementController::class, 'edit'])->name('announcements.edit');
            Route::put('/announcements/{announcement}', [Admin\AnnouncementController::class, 'update'])->name('announcements.update');
            Route::delete('/announcements/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');

            /*
             * Staff accounts
             */
            Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [Admin\UserController::class, 'create'])->name('users.create');
            Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
        });

        /*
         * Price listing and the approval workflow
         */
        Route::get('/prices', [Admin\PriceController::class, 'index'])->name('prices.index');
        Route::get('/prices/{price}', [Admin\PriceController::class, 'show'])->name('prices.show');

        Route::get('/price-changes', [Admin\PriceChangeRequestController::class, 'index'])->name('price-changes.index');
        Route::get('/price-changes/create', [Admin\PriceChangeRequestController::class, 'create'])->name('price-changes.create');
        Route::post('/price-changes', [Admin\PriceChangeRequestController::class, 'store'])->name('price-changes.store');
        Route::get('/price-changes/{priceChangeRequest}', [Admin\PriceChangeRequestController::class, 'show'])->name('price-changes.show');
        Route::delete('/price-changes/{priceChangeRequest}', [Admin\PriceChangeRequestController::class, 'destroy'])->name('price-changes.destroy');
        Route::post('/price-changes/{priceChangeRequest}/approve', [Admin\PriceChangeRequestController::class, 'approve'])
            ->name('price-changes.approve');
        Route::post('/price-changes/{priceChangeRequest}/reject', [Admin\PriceChangeRequestController::class, 'reject'])
            ->name('price-changes.reject');

        /*
         * Inquiry desk
         */
        Route::get('/tickets', [Admin\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [Admin\TicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/replies', [Admin\TicketController::class, 'reply'])->name('tickets.reply');
        Route::patch('/tickets/{ticket}/status', [Admin\TicketController::class, 'updateStatus'])->name('tickets.status');
        Route::patch('/tickets/{ticket}/assign', [Admin\TicketController::class, 'assign'])->name('tickets.assign');
        Route::patch('/tickets/{ticket}/priority', [Admin\TicketController::class, 'updatePriority'])->name('tickets.priority');

        /*
         * Review moderation
         */
        Route::get('/reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/{review}', [Admin\ReviewController::class, 'show'])->name('reviews.show');
        Route::post('/reviews/{review}/flag', [Admin\ReviewController::class, 'flag'])->name('reviews.flag');
        Route::post('/reviews/{review}/publish', [Admin\ReviewController::class, 'publish'])->name('reviews.publish');
        Route::post('/reviews/{review}/hide', [Admin\ReviewController::class, 'hide'])->name('reviews.hide');
        Route::delete('/reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

        /*
         * Sales
         */
        Route::get('/sales', [Admin\SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/dashboard', Admin\SalesDashboardController::class)->name('sales.dashboard');
        Route::get('/sales/import', [Admin\SaleController::class, 'import'])->name('sales.import');
        Route::get('/sales/import/template', [Admin\SaleController::class, 'template'])->name('sales.import.template');
        Route::post('/sales/import', [Admin\SaleController::class, 'import'])->name('sales.import.store');
        Route::get('/sales/create', [Admin\SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [Admin\SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/export/csv', [Admin\SalesExportController::class, 'csv'])->name('sales.export.csv');
        Route::get('/sales/export/pdf', [Admin\SalesExportController::class, 'pdf'])->name('sales.export.pdf');
        Route::patch('/sales/{sale}', [Admin\SaleController::class, 'update'])->name('sales.update');
        Route::delete('/sales/{sale}', [Admin\SaleController::class, 'destroy'])->name('sales.destroy');

        /*
         * Audit trail
         */
        Route::get('/activity-log', [Admin\ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/activity-log/{activityLog}', [Admin\ActivityLogController::class, 'show'])->name('activity-log.show');
    });

require __DIR__.'/timekeeping.php';
