<?php
declare(strict_types=1);

namespace App\Core;

use App\Controller\AdminController;
use App\Controller\AddressController;
use App\Controller\AuthController;
use App\Controller\DishController;
use App\Controller\MenuCompositionController;
use App\Controller\ContactController;
use App\Controller\ErrorController;
use App\Controller\OrderController;
use App\Controller\PublicController;
use App\Controller\QuoteController;
use App\Controller\NotificationController;
use App\Repository\CommentRepository;
use App\Repository\DishRepository;
use App\Repository\MenuImageRepository;
use App\Repository\MenuStatisticsRepository;
use App\Repository\QuoteRequestRepository;
use App\Repository\MenuRepository;
use App\Repository\OpeningHoursRepository;
use App\Repository\OrderRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\AdminService;
use App\Service\AuthService;
use App\Service\CacheService;
use App\Service\CommentService;
use App\Service\DeliveryDistanceService;
use App\Service\DishService;
use App\Service\MailService;
use App\Service\MenuService;
use App\Service\MenuStatisticsService;
use App\Service\OrderService;
use App\Service\PasswordPolicy;
use App\Service\PhoneValidator;
use App\Service\QuoteService;
use App\Service\NotificationService;
use App\Service\RateLimiter;
use App\Middleware\Admin;
use App\Middleware\Auth;
use App\Middleware\Guest;
use App\Middleware\Security;
use App\Middleware\Staff;

use RuntimeException;

// The application classes are loaded by Composer at runtime. Keep the
// container independent from the editor's class-indexing state.
/** @noinspection PhpUndefinedClassInspection */
final class Container
{
    private array $factories = [];
    private array $instances = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new RuntimeException('Service introuvable : ' . $id);
        }

        $instance = ($this->factories[$id])($this);

        if (!is_object($instance)) {
            throw new RuntimeException('Le service doit retourner un objet : ' . $id);
        }

        $this->instances[$id] = $instance;

        return $instance;
    }

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    private function registerDefaults(): void
    {
        $this->set(
            UserRepository::class,
            static fn (): UserRepository => new UserRepository()
        );

        $this->set(
            MenuRepository::class,
            static fn (): MenuRepository => new MenuRepository()
        );

        $this->set(
            OrderRepository::class,
            static fn (): OrderRepository => new OrderRepository()
        );

        $this->set(
            CommentRepository::class,
            static fn (Container $container): CommentRepository => new CommentRepository(
                $container->get(UserRepository::class)
            )
        );


        $this->set(
            OpeningHoursRepository::class,
            static fn (): OpeningHoursRepository => new OpeningHoursRepository()
        );

        $this->set(
            CacheService::class,
            static fn (): CacheService => new CacheService()
        );

        $this->set(
            PhoneValidator::class,
            static fn (): PhoneValidator => new PhoneValidator()
        );

        $this->set(
            PasswordPolicy::class,
            static fn (): PasswordPolicy => new PasswordPolicy(
                (int) config('security.password_hash_cost', 12)
            )
        );

        $this->set(
            RateLimiter::class,
            static fn (Container $container): RateLimiter => new RateLimiter(
                $container->get(CacheService::class)
            )
        );

        $this->set(
            QuoteRequestRepository::class,
            static fn (): QuoteRequestRepository => new QuoteRequestRepository()
        );

        $this->set(
            MenuStatisticsRepository::class,
            static fn (): MenuStatisticsRepository => new MenuStatisticsRepository()
        );

        $this->set(
            MailService::class,
            static fn (): MailService => new MailService(
                is_array(config('mail')) ? config('mail') : []
            )
        );

        $this->set(
            MenuService::class,
            static fn (Container $container): MenuService => new MenuService(
                $container->get(MenuRepository::class),
                $container->get(CacheService::class),
                $container->get(DishRepository::class),
                $container->get(MenuImageRepository::class)
            )
        );

        $this->set(
            DishRepository::class,
            static fn (): DishRepository => new DishRepository()
        );

        $this->set(
            MenuImageRepository::class,
            static fn (): MenuImageRepository => new MenuImageRepository()
        );

        $this->set(
            DishService::class,
            static fn (Container $container): DishService => new DishService(
                $container->get(DishRepository::class)
            )
        );

        $this->set(
            DishController::class,
            static fn (Container $container): DishController => new DishController(
                $container->get(DishService::class)
            )
        );

        $this->set(
            MenuCompositionController::class,
            static fn (Container $container): MenuCompositionController => new MenuCompositionController(
                $container->get(MenuService::class),
                $container->get(DishService::class)
            )
        );

        $this->set(
            AuthService::class,
            static fn (Container $container): AuthService => new AuthService(
                $container->get(UserRepository::class),
                $container->get(PasswordPolicy::class),
                $container->get(PhoneValidator::class)
            )
        );

        $this->set(
            CommentService::class,
            static fn (Container $container): CommentService => new CommentService(
                $container->get(CommentRepository::class),
                $container->get(OrderRepository::class),
                $container->get(CacheService::class)
            )
        );

        $this->set(
            MenuStatisticsService::class,
            static fn (Container $container): MenuStatisticsService => new MenuStatisticsService(
                $container->get(OrderRepository::class),
                $container->get(MenuStatisticsRepository::class)
            )
        );

        $this->set(
            OrderService::class,
            static fn (Container $container): OrderService => new OrderService(
                $container->get(OrderRepository::class),
                $container->get(UserRepository::class),
                $container->get(MenuRepository::class),
                $container->get(OpeningHoursRepository::class),
                $container->get(MailService::class),
                $container->get(MenuStatisticsService::class),
                $container->get(NotificationService::class),
                $container->get(DeliveryDistanceService::class),
                $container->get(MenuService::class),
                $container->get(PhoneValidator::class)
            )
        );

        $this->set(
            DeliveryDistanceService::class,
            static fn (Container $container): DeliveryDistanceService => new DeliveryDistanceService(
                (string) config('app.google_maps_key', ''),
                $container->get(CacheService::class)
            )
        );

        $this->set(
            AdminService::class,
            static fn (Container $container): AdminService => new AdminService(
                $container->get(UserRepository::class),
                $container->get(OrderRepository::class),
                $container->get(MenuStatisticsService::class),
                $container->get(PasswordPolicy::class),
                $container->get(PhoneValidator::class)
            )
        );

        $this->set(
            PublicController::class,
            static fn (Container $container): PublicController => new PublicController(
                $container->get(MenuService::class),
                $container->get(CommentService::class),
                $container->get(MailService::class)
            )
        );

        $this->set(
            AuthController::class,
            static fn (Container $container): AuthController => new AuthController(
                $container->get(AuthService::class),
                $container->get(UserRepository::class),
                $container->get(MailService::class),
                $container->get(RateLimiter::class),
                $container->get(PhoneValidator::class),
                $container->get(PasswordPolicy::class)
            )
        );

        $this->set(
            OrderController::class,
            static fn (Container $container): OrderController => new OrderController(
                $container->get(OrderService::class),
                $container->get(UserRepository::class),
                $container->get(MenuService::class),
                $container->get(CommentService::class)
            )
        );

        $this->set(
            AdminController::class,
            static fn (Container $container): AdminController => new AdminController(
                $container->get(AdminService::class),
                $container->get(AuthService::class),
                $container->get(MenuService::class),
                $container->get(CommentService::class),
                $container->get(OrderService::class),
                $container->get(OpeningHoursRepository::class),
                $container->get(QuoteService::class),
                $container->get(RateLimiter::class),
                $container->get(MailService::class)
            )
        );

        $this->set(
            ErrorController::class,
            static fn (): ErrorController => new ErrorController()
        );

        $this->set(
            AddressController::class,
            static fn (): AddressController => new AddressController()
        );

        $this->set(
            ContactController::class,
            static fn (Container $container): ContactController => new ContactController(
                $container->get(MailService::class)
            )
        );

        $this->set(
            NotificationRepository::class,
            static fn (): NotificationRepository => new NotificationRepository()
        );

        $this->set(
            NotificationService::class,
            static fn (Container $container): NotificationService => new NotificationService(
                $container->get(NotificationRepository::class),
                $container->get(UserRepository::class)
            )
        );

        $this->set(
            QuoteService::class,
            static fn (Container $container): QuoteService => new QuoteService(
                $container->get(QuoteRequestRepository::class),
                $container->get(MailService::class),
                $container->get(NotificationService::class),
                $container->get(PhoneValidator::class)
            )
        );

        $this->set(
            QuoteController::class,
            static fn (Container $container): QuoteController => new QuoteController(
                $container->get(QuoteService::class)
            )
        );

        $this->set(
            NotificationController::class,
            static fn (Container $container): NotificationController => new NotificationController(
                $container->get(NotificationService::class)
            )
        );

        $this->set(
            Auth::class,
            static fn (Container $container): Auth => new Auth(
                $container->get(UserRepository::class)
            )
        );

        $this->set(
            Security::class,
            static fn (Container $container): Security => new Security(
                $container->get(RateLimiter::class)
            )
        );

        $this->set(
            Admin::class,
            static fn (Container $container): Admin => new Admin(
                $container->get(Auth::class)
            )
        );

        $this->set(
            Staff::class,
            static fn (Container $container): Staff => new Staff(
                $container->get(Auth::class)
            )
        );

        $this->set(
            Guest::class,
            static fn (): Guest => new Guest()
        );
    }
}
