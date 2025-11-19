<?php

declare(strict_types=1);

namespace App\Tests\Presentation\UserManagement\Controller;

use App\Tests\Presentation\PresentationTestCase;

final class SecurityControllerTest extends PresentationTestCase
{
    public function testLoginPageRendersCorrectly(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleMatches('Login');
        $this->assertPageContains('Easy Banking System');
        $this->assertPageContains('Sign In');
        
        // Check form fields exist
        $this->assertFormFieldExists($crawler, '_username');
        $this->assertFormFieldExists($crawler, '_password');
        $this->assertFormFieldExists($crawler, '_csrf_token');
    }

    public function testSuccessfulLoginAsCustomerRedirectsToDashboard(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');

        $crawler = $this->loginAsCustomer($customer, 'pass123');

        $this->assertOnRoute('customer_dashboard');
        $this->assertPageContains('Customer Dashboard');
    }

    public function testSuccessfulLoginAsEmployeeRedirectsToDashboard(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');

        $crawler = $this->loginAsEmployee($employee, 'pass123');

        $this->assertOnRoute('employee_dashboard');
        $this->assertPageContains('Employee Dashboard');
    }

    public function testLoginWithInvalidCredentialsShowsError(): void
    {
        $this->createCustomer('customer1', 'correctpass');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'customer1',
            '_password' => 'wrongpass',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertOnRoute('login');
        $this->assertPageContains('Invalid credentials');
    }

    public function testLoginWithNonExistentUserShowsError(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'nonexistent',
            '_password' => 'somepass',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertOnRoute('login');
        $this->assertPageContains('Invalid credentials');
    }

    public function testAlreadyLoggedInUserRedirectsFromLoginPage(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/login');

        $this->assertRedirectsToRoute('home');
    }

    public function testHomePageRedirectsUnauthenticatedUserToLogin(): void
    {
        $this->client->request('GET', '/');

        $this->assertRedirectsToRoute('login');
    }

    public function testHomePageRedirectsCustomerToCustomerDashboard(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/');

        $this->assertRedirectsToRoute('customer_dashboard');
    }

    public function testHomePageRedirectsEmployeeToEmployeeDashboard(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/');

        $this->assertRedirectsToRoute('employee_dashboard');
    }

    public function testLogoutRedirectsToLogin(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/logout');

        $this->assertRedirectsToRoute('login');
        
        // Verify user is actually logged out by trying to access protected page
        $this->client->request('GET', '/customer/dashboard');
        $this->assertRedirectsToRoute('login');
    }

    public function testLoginFormPreservesUsernameOnError(): void
    {
        $this->createCustomer('customer1', 'correctpass');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Sign In')->form([
            '_username' => 'customer1',
            '_password' => 'wrongpass',
        ]);

        $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $this->assertFormFieldExists($crawler, '_username', 'customer1');
    }

    public function testLoginFormHasCsrfProtection(): void
    {
        $this->createCustomer('customer1', 'pass123');

        // Submit without CSRF token
        $this->client->request('POST', '/login', [
            '_username' => 'customer1',
            '_password' => 'pass123',
        ]);

        // Should fail due to missing CSRF token
        $this->assertResponseStatusCodeSame(302);
        $this->client->followRedirect();
        $this->assertOnRoute('login');
    }
}
