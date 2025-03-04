<?php

namespace Tests\Feature;

use Dcat\Admin\Models\Administrator;
use Tests\TestCase;
use Illuminate\Support\Facades\File;

class UserSettingTest extends TestCase
{
    public function testVisitSettingPage()
    {
        $this->visit('admin/auth/setting')
            ->see('User setting')
            ->see('Username')
            ->see('Name')
            ->see('Avatar')
            ->see('Password')
            ->see('Password confirmation');

        $this->seeElement('input[value=Administrator]')
            ->seeInElement('.box-body', 'administrator');
    }

    public function testUpdateName()
    {
        $data = [
            'name' => 'tester',
        ];

        $this->visit('admin/auth/setting')
            ->submitForm('Submit', $data)
            ->seePageIs('admin/auth/setting');

        $this->seeInDatabase('admin_users', ['name' => $data['name']]);
    }

    public function testUpdatePasswordConfirmation()
    {
        $data = [
            'password'              => '123456',
            'password_confirmation' => '123',
        ];

        $this->visit('admin/auth/setting')
            ->submitForm('Submit', $data)
            ->seePageIs('admin/auth/setting')
            ->see('The Password confirmation does not match.');
    }

    public function testUpdatePassword()
    {
        $data = [
            'old_password'          => 'admin',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ];

        $this->visit('admin/auth/setting')
            ->submitForm('Submit', $data)
            ->seePageIs('admin/auth/setting');

        $this->assertTrue(app('hash')->check($data['password'], Administrator::first()->makeVisible('password')->password));

        $this->visit('admin/auth/logout')
            ->seePageIs('admin/auth/login')
            ->dontSeeIsAuthenticated('admin');

        $credentials = ['username' => 'admin', 'password' => '123456'];

        $this->visit('admin/auth/login')
            ->see('login')
            ->submitForm('Login', $credentials)
            ->see('dashboard')
            ->seeCredentials($credentials, 'admin')
            ->seeIsAuthenticated('admin')
            ->seePageIs('admin');
    }
}
