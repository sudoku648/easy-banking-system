<?php

declare(strict_types=1);

namespace App\Tests\Presentation\UserManagement\Controller;

use App\Tests\Presentation\PresentationTestCase;

final class ChangePasswordControllerTest extends PresentationTestCase
{
    public function testCustomerCanAccessChangePasswordPage(): void
    {
        $customer = $this->createCustomer('customer1', 'password123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/customer/change-password');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleMatches('Change Password');
    }

    public function testEmployeeCanAccessChangePasswordPage(): void
    {
        $employee = $this->createEmployee('employee1', 'password123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/change-password');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleMatches('Change Password');
    }

    public function testUnauthenticatedUserCannotAccessCustomerChangePassword(): void
    {
        $this->client->request('GET', '/customer/change-password');

        $this->assertRedirectsToRoute('login');
    }

    public function testUnauthenticatedUserCannotAccessEmployeeChangePassword(): void
    {
        $this->client->request('GET', '/employee/change-password');

        $this->assertRedirectsToRoute('login');
    }

    public function testCustomerCanChangePassword(): void
    {
        $customer = $this->createCustomer('customer1', 'OldPassword123');
        $this->loginAsCustomerUser($customer);

        $crawler = $this->client->request('GET', '/customer/change-password');

        $form = $crawler->selectButton('Change Password')->form([
            'change_password_form[currentPassword]' => 'OldPassword123',
            'change_password_form[newPassword]' => 'NewPassword456',
            'change_password_form[confirmNewPassword]' => 'NewPassword456',
        ]);

        $this->client->submit($form);

        $this->assertRedirectsToRoute('customer_dashboard');
        $this->client->followRedirect();
        $this->assertPageContains('Password changed successfully');

        // Verify old password no longer works
        $this->client->request('GET', '/logout');
        $this->loginAs('customer1', 'OldPassword123');
        $this->assertPageContains('Invalid credentials');

        // Verify new password works
        $this->loginAs('customer1', 'NewPassword456');
        $this->client->request('GET', '/customer/dashboard');
        $this->assertResponseIsSuccessful();
    }

    public function testEmployeeCanChangePassword(): void
    {
        $employee = $this->createEmployee('employee1', 'OldPassword123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/change-password');

        $form = $crawler->selectButton('Change Password')->form([
            'change_password_form[currentPassword]' => 'OldPassword123',
            'change_password_form[newPassword]' => 'NewPassword456',
            'change_password_form[confirmNewPassword]' => 'NewPassword456',
        ]);

        $this->client->submit($form);

        $this->assertRedirectsToRoute('employee_dashboard');
        $this->client->followRedirect();
        $this->assertPageContains('Password changed successfully');
    }

    public function testChangePasswordFailsWithIncorrectCurrentPassword(): void
    {
        $customer = $this->createCustomer('customer1', 'CorrectPassword123');
        $this->loginAsCustomerUser($customer);

        $crawler = $this->client->request('GET', '/customer/change-password');

        $form = $crawler->selectButton('Change Password')->form([
            'change_password_form[currentPassword]' => 'WrongPassword123',
            'change_password_form[newPassword]' => 'NewPassword456',
            'change_password_form[confirmNewPassword]' => 'NewPassword456',
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Current password is incorrect');
        $this->assertPageNotContains('Password changed successfully');
    }

    public function testChangePasswordFailsWhenNewPasswordTooShort(): void
    {
        $customer = $this->createCustomer('customer1', 'password123');
        $this->loginAsCustomerUser($customer);

        $crawler = $this->client->request('GET', '/customer/change-password');

        $form = $crawler->selectButton('Change Password')->form([
            'change_password_form[currentPassword]' => 'password123',
            'change_password_form[newPassword]' => 'short',
            'change_password_form[confirmNewPassword]' => 'short',
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertPageNotContains('Password changed successfully');
    }

    public function testChangePasswordFailsWhenConfirmationDoesNotMatch(): void
    {
        $customer = $this->createCustomer('customer1', 'password123');
        $this->loginAsCustomerUser($customer);

        $crawler = $this->client->request('GET', '/customer/change-password');

        $form = $crawler->selectButton('Change Password')->form([
            'change_password_form[currentPassword]' => 'password123',
            'change_password_form[newPassword]' => 'NewPassword456',
            'change_password_form[confirmNewPassword]' => 'DifferentPassword',
        ]);

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Passwords must match');
        $this->assertPageNotContains('Password changed successfully');
    }

    public function testChangePasswordAcceptsMinimumLengthPassword(): void
    {
        $customer = $this->createCustomer('customer1', 'password123');
        $this->loginAsCustomerUser($customer);

        $crawler = $this->client->request('GET', '/customer/change-password');

        $form = $crawler->selectButton('Change Password')->form([
            'change_password_form[currentPassword]' => 'password123',
            'change_password_form[newPassword]' => '12345678', // Exactly 8 characters
            'change_password_form[confirmNewPassword]' => '12345678',
        ]);

        $this->client->submit($form);

        $this->assertRedirectsToRoute('customer_dashboard');
        $this->client->followRedirect();
        $this->assertPageContains('Password changed successfully');
    }

    public function testEmployeeCannotAccessCustomerChangePassword(): void
    {
        $employee = $this->createEmployee('employee1', 'password123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/customer/change-password');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCustomerCannotAccessEmployeeChangePassword(): void
    {
        $customer = $this->createCustomer('customer1', 'password123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/employee/change-password');

        $this->assertResponseStatusCodeSame(403);
    }
}
