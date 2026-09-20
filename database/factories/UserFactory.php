<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Bcrypt hash of "asdasd" - the plaintext every seeded account uses.
     *
     * Hashing per row costs ~50ms, which is minutes across a large seed, so the
     * hash is precomputed here and reused.
     */
    const PASSWORD_HASH = '$2y$10$smMKvmulMB7sE8frWdKNm.dQ6mxoI7F0rOxi2sagrq5KfMNG0qBem';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_type_id' => UserType::Viewer,
            'company_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cell_phone' => fake()->numerify('(###) ###-####'),
            'liquidity' => 0,
            'user_handle' => fake()->unique()->userName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-25 years')->format('Y-m-d'),
            'ssn_number' => fake()->numerify('###-##-####'),
            'postal_code' => fake()->postcode(),
            'state' => fake()->stateAbbr(),
            'city' => fake()->city(),
            'home_address' => fake()->streetAddress(),
            'email_verified_at' => now(),
            'password' => self::PASSWORD_HASH,
            'status_id' => User::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin()
    {
        return $this->state(['user_type_id' => UserType::Admin]);
    }

    public function investor()
    {
        return $this->state(['user_type_id' => UserType::Investor]);
    }

    public function merchant()
    {
        return $this->state(['user_type_id' => UserType::Merchant]);
    }

    public function company()
    {
        return $this->state(['user_type_id' => UserType::Company]);
    }

    public function lender()
    {
        return $this->state(['user_type_id' => UserType::Lender]);
    }

    public function overPayment()
    {
        return $this->state(['user_type_id' => UserType::OverPayment]);
    }

    /**
     * Attach the user to a company account. Note that company_id points at
     * another row in users, not at a companies table.
     */
    public function forCompany(User|int $company)
    {
        return $this->state(['company_id' => $company instanceof User ? $company->id : $company]);
    }

    /**
     * Give the user its own company account.
     *
     * Several model hooks copy company_id off the user - InvestorTransaction's
     * created hook writes it to a NOT NULL column - so an investor without one
     * cannot take a transaction.
     */
    public function withCompany()
    {
        return $this->state(['company_id' => self::new()->company()]);
    }

    public function deactivated()
    {
        return $this->state(['status_id' => User::Deactive]);
    }

    public function softDeleted()
    {
        return $this->state(['deleted_at' => now()]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
