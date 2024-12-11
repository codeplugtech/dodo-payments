<?php

namespace Codeplugtech\DodoPayments\Tests;

use Codeplugtech\DodoPayments\DodoPaymentsServiceProvider;
use Codeplugtech\DodoPayments\Tests\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Encryption\Encrypter;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [DodoPaymentsServiceProvider::class];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testbench');
        config()->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        config()->set('auth.providers.users.model', User::class);
        config()->set('app.key', 'base64:' . base64_encode(
                Encrypter::generateKey(config()['app.cipher'])
            ));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate', ['--database' => 'testbench']);
        $this->createUserTable();
    }

    protected function createUserTable(): void
    {

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

    }


}
