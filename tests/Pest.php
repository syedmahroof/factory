<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| DatabaseTransactions rather than RefreshDatabase: the legacy migrations are
| MySQL specific and there are 70 of them, so the `testing` schema is migrated
| once out of band (DB_DATABASE=testing php artisan migrate) and each test rolls
| back its own writes. phpunit.xml already points the suite at that database.
|
*/
pest()->extend(TestCase::class)
    ->use(DatabaseTransactions::class)
    ->in('Feature');
