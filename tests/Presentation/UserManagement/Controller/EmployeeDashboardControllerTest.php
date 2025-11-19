<?php

declare(strict_types=1);

namespace App\Tests\Presentation\UserManagement\Controller;

use App\Tests\Presentation\PresentationTestCase;

final class EmployeeDashboardControllerTest extends PresentationTestCase
{
    public function testUnauthenticatedUserCannotAccessEmployeeDashboard(): void
    {
        $this->client->request('GET', '/employee/dashboard');

        $this->assertRedirectsToRoute('login');
    }

    public function testCustomerCannotAccessEmployeeDashboard(): void
    {
        $customer = $this->createCustomer('customer1', 'pass123');
        $this->loginAsCustomerUser($customer);

        $this->client->request('GET', '/employee/dashboard');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEmployeeCanAccessDashboard(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertPageContains('Employee Dashboard');
        $this->assertPageTitleMatches('Employee Dashboard');
    }

    public function testDashboardShowsNavigationToOpenNewCustomerAccount(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/dashboard');

        $this->assertResponseIsSuccessful();
        
        $link = $crawler->selectLink('New Customer')->count();
        self::assertGreaterThan(0, $link, 'Expected to find "New Customer" link on dashboard');
    }

    public function testDashboardShowsNavigationToOpenExistingCustomerAccount(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/dashboard');

        $this->assertResponseIsSuccessful();
        
        $link = $crawler->selectLink('Existing Customer')->count();
        self::assertGreaterThan(0, $link, 'Expected to find "Existing Customer" link on dashboard');
    }

    public function testDashboardShowsNavigationToCloseAccount(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/dashboard');

        $this->assertResponseIsSuccessful();
        
        $link = $crawler->selectLink('Close Account')->count();
        self::assertGreaterThan(0, $link, 'Expected to find "Close Account" link on dashboard');
    }

    public function testDashboardShowsLogoutOption(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123');
        $this->loginAsEmployeeUser($employee);

        $crawler = $this->client->request('GET', '/employee/dashboard');

        $this->assertResponseIsSuccessful();
        
        $link = $crawler->selectLink('Logout')->count();
        self::assertGreaterThan(0, $link, 'Expected to find "Logout" link on dashboard');
    }

    public function testEmployeeNameIsDisplayedOnDashboard(): void
    {
        $employee = $this->createEmployee('employee1', 'pass123', 'Jane', 'Smith');
        $this->loginAsEmployeeUser($employee);

        $this->client->request('GET', '/employee/dashboard');

        $this->assertResponseIsSuccessful();
        // Employee name should be displayed somewhere on the page
        $this->assertPageContains('Jane');
    }
}
