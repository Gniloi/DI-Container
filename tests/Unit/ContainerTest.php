<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Container;
use App\Exceptions\Container\NotFoundException;
use App\Exceptions\Container\ContainerException;
use App\Services\EmailService;
use App\Services\InvoiceService;
use App\Services\SalesTaxService;
use App\Services\PaymentGatewayServiceInterface;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
    }

    /** @test */
    public function it_registers_an_entries(): void
    {
        $this->container->set('1', fn () => true);

        $expected = true;

        $this->assertSame($expected, $this->container->has('1'));
    }

    /** @test */
    public function it_returns_callable_function(): void
    {
        $this->container->set('1', fn () => true);

        $expected = true;

        $this->assertSame($expected, $this->container->get('1'));
    }

    /** @test */
    public function it_throws_notFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->resolve('id');
    }

    /** @test */
    public function it_throws_ContainerException(): void
    {
        $class = new class extends TestCase
        {
        };

        $this->expectException(ContainerException::class);
        $this->container->resolve($class::class);
    }

    /** @test */
    public function creates_new_class_when_no_constructor(): void
    {
        $class = new class()
        {
        };

        $this->assertEquals($class, $this->container->resolve($class::class));
    }

    /** @test */
    public function creates_new_class_when_no_parametrs(): void
    {
        $class = new class()
        {
            public function __construct()
            {
            }
        };

        $this->assertEquals($class, $this->container->resolve($class::class));
    }

    /** @test */
    public function throws_container_exception_when_no_type_for_param(): void
    {
        $class = new class($count = 1)
        {
            public function __construct(
                protected $count
            ) {
            }
        };

        $this->expectException(ContainerException::class);
        $this->container->resolve($class::class);
    }

    /** @test */
    public function throws_container_exception_when_inseance_of_ReflectionUnionType(): void
    {
        $class = new class($count = 1)
        {
            public function __construct(
                protected array|int $count
            ) {
            }
        };

        $this->expectException(ContainerException::class);
        $this->container->resolve($class::class);
    }

    /** @test */
    public function throws_container_exception_when_inseance_of_ReflectionNamedType(): void
    {
        $class = new class($count = 1)
        {
            public function __construct(
                protected int $count
            ) {
            }
        };

        $this->expectException(ContainerException::class);
        $this->container->resolve($class::class);
    }

    /** @test */
    public function creates_new_class_with_all_dependecies(): void
    {
        $salesMock = $this->createMock(SalesTaxService::class);
        $emailMock = $this->createMock(EmailService::class);
        $gatewayMock = $this->createMock(PaymentGatewayServiceInterface::class);

        $this->container->set(PaymentGatewayServiceInterface::class, PaymentGatewayService::class);

        $gatewayMock->method('charge')->willReturn(true);

        $invoiceService = new InvoiceService($salesMock, $gatewayMock, $emailMock);

        $customer = ['name' => 'Nikolay'];
        $amount = 22;

        $result = $invoiceService->process($customer, $amount);

        $this->assertTrue($result);
    }
}
