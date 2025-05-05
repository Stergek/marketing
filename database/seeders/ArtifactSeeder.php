<?php

namespace Database\Seeders;

use App\Models\Artifact;
use App\Models\Version;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class ArtifactSeeder extends Seeder
{
    public function run(): void
    {
        $files = [
            [
                'file_path' => 'app/Models/User.php',
                'file_name' => 'User.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Added profile fields',
                'description' => 'User model for authentication',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Initial model with basic fields.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added email verification.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Introduced profile fields like bio and avatar, enhancing user customization. This update improves the user experience by allowing personalized profiles, supports avatar uploads with validation, and ensures database schema compatibility with existing users. The change was tested extensively to prevent data migration issues.'],
                ],
            ],
            [
                'file_path' => 'app/Filament/Resources/PostResource.php',
                'file_name' => 'PostResource.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 4: Enhanced form layout',
                'description' => 'Filament resource for blog posts',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Initial resource setup.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added rich text editor.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Included image upload support.'],
                    ['update_number' => 4, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Revamped the form layout to improve usability, adding tabbed sections for content, metadata, and SEO. This update streamlines the post creation process, reduces user errors, and integrates with the new media library for better image management. Thoroughly tested for compatibility with existing posts.'],
                ],
            ],
            [
                'file_path' => 'resources/views/components/button.blade.php',
                'file_name' => 'button.blade.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Added variants',
                'description' => 'Reusable button component',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic button with primary style.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Introduced variants for secondary and danger buttons, enhancing flexibility across the app. The update includes new Tailwind classes for consistent styling, supports dynamic props for size and disabled state, and was validated across all use cases to ensure no visual regressions.'],
                ],
            ],
            [
                'file_path' => 'app/Http/Controllers/AuthController.php',
                'file_name' => 'AuthController.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Social login support',
                'description' => 'Handles user authentication',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic login/register endpoints.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added password reset functionality.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Integrated social login with Google and GitHub, improving user onboarding. The update uses Laravel Socialite, includes secure token handling, and maps social profiles to user accounts. Extensive testing ensured compatibility with existing auth flows and prevented security vulnerabilities.'],
                ],
            ],
            [
                'file_path' => 'database/migrations/2025_01_01_000001_create_posts_table.php',
                'file_name' => '2025_01_01_000001_create_posts_table.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Added slug',
                'description' => 'Migration for posts table',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Initial posts table schema.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added slug column for SEO-friendly URLs, ensuring unique constraints and indexing for performance. The update required a data migration script to generate slugs for existing posts, was tested for backward compatibility, and improves routing efficiency across the blog module.'],
                ],
            ],
            [
                'file_path' => 'app/Filament/Widgets/SalesOverview.php',
                'file_name' => 'SalesOverview.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Added chart',
                'description' => 'Sales stats widget',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic sales stats display.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added trend indicators.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Integrated a line chart to visualize sales trends over time, using Chart.js for rendering. The update enhances data interpretation, supports dynamic date ranges, and was optimized for performance with large datasets. Thoroughly tested to ensure accurate data representation.'],
                ],
            ],
            [
                'file_path' => 'resources/css/app.css',
                'file_name' => 'app.css',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 4: Dark mode',
                'description' => 'Main application styles',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Initial Tailwind setup.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added custom components.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Optimized for mobile.'],
                    ['update_number' => 4, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Implemented dark mode support with Tailwind’s dark variant classes, improving accessibility and user experience. The update includes automatic theme detection, custom dark mode colors, and was tested across all pages to ensure visual consistency and no style conflicts.'],
                ],
            ],
            [
                'file_path' => 'app/Models/Product.php',
                'file_name' => 'Product.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Stock management',
                'description' => 'Product model for e-commerce',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic product fields.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added stock management with quantity tracking and low-stock alerts. The update integrates with the order system, supports bulk updates, and was tested to ensure accurate inventory updates during high-traffic scenarios.'],
                ],
            ],
            [
                'file_path' => 'app/Filament/Resources/OrderResource.php',
                'file_name' => 'OrderResource.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Status tracking',
                'description' => 'Manages customer orders',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Initial order resource.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added payment integration.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Introduced status tracking with pending, shipped, and delivered states, improving order management. The update includes notifications, a status change log, and was tested for scalability with large order volumes.'],
                ],
            ],
            [
                'file_path' => 'resources/views/layouts/app.blade.php',
                'file_name' => 'app.blade.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Improved navigation',
                'description' => 'Main application layout',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic layout setup.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added footer.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Enhanced navigation with a responsive navbar, dropdown menus, and accessibility improvements. The update ensures consistent rendering across devices, supports dynamic menu items, and was tested for performance with complex layouts.'],
                ],
            ],
            [
                'file_path' => 'app/Http/Middleware/Authenticate.php',
                'file_name' => 'Authenticate.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: API support',
                'description' => 'Authentication middleware',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic auth checks.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added support for API token authentication, enabling secure API endpoints. The update includes rate limiting, token validation, and was tested to ensure compatibility with web and API routes.'],
                ],
            ],
            [
                'file_path' => 'database/seeders/DatabaseSeeder.php',
                'file_name' => 'DatabaseSeeder.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Added roles',
                'description' => 'Main database seeder',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Initial seeder setup.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added user seeding.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Introduced role seeding for admin and user roles, enhancing permission management. The update supports dynamic role assignment, was tested for consistency across environments, and ensures no duplicate entries during seeding.'],
                ],
            ],
            [
                'file_path' => 'app/Filament/Widgets/UserStats.php',
                'file_name' => 'UserStats.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Real-time data',
                'description' => 'User statistics widget',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic user stats.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added real-time data updates using Livewire, improving dashboard interactivity. The update optimizes database queries, supports polling, and was tested for performance under high user loads.'],
                ],
            ],
            [
                'file_path' => 'resources/views/auth/login.blade.php',
                'file_name' => 'login.blade.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Improved UX',
                'description' => 'Login page template',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic login form.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added remember me option.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Enhanced user experience with better form validation, error messages, and a modern design. The update includes accessibility improvements, was tested across browsers, and ensures seamless integration with the auth system.'],
                ],
            ],
            [
                'file_path' => 'app/Models/Comment.php',
                'file_name' => 'Comment.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Moderation',
                'description' => 'Comment model for posts',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic comment fields.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added moderation features with approved/rejected states, improving content quality. The update includes admin notifications, was tested for scalability, and ensures compatibility with existing comments.'],
                ],
            ],
            [
                'file_path' => 'app/Http/Requests/StorePostRequest.php',
                'file_name' => 'StorePostRequest.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Validation rules',
                'description' => 'Post creation request',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic validation.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added image validation.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Strengthened validation rules for title, content, and tags, ensuring data integrity. The update includes custom error messages, supports multi-language validation, and was tested for edge cases to prevent invalid submissions.'],
                ],
            ],
            [
                'file_path' => 'resources/views/emails/welcome.blade.php',
                'file_name' => 'welcome.blade.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Responsive design',
                'description' => 'Welcome email template',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic email template.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added responsive design for mobile devices, improving email readability. The update uses Tailwind typography, was tested across email clients, and ensures consistent branding.'],
                ],
            ],
            [
                'file_path' => 'app/Filament/Resources/CategoryResource.php',
                'file_name' => 'CategoryResource.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Nested categories',
                'description' => 'Manages post categories',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic category resource.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added slug support.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Implemented nested categories with parent-child relationships, enhancing content organization. The update includes a tree view in the UI, was tested for performance with large category sets, and ensures backward compatibility.'],
                ],
            ],
            [
                'file_path' => 'app/Providers/AppServiceProvider.php',
                'file_name' => 'AppServiceProvider.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Added bindings',
                'description' => 'Application service provider',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic provider setup.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added service bindings for repository pattern, improving code maintainability. The update was tested for dependency injection consistency and ensures no performance overhead.'],
                ],
            ],
            [
                'file_path' => 'resources/views/posts/index.blade.php',
                'file_name' => 'index.blade.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Pagination',
                'description' => 'Post listing page',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic post listing.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added category filters.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Implemented pagination with dynamic page sizes, improving performance for large datasets. The update includes SEO-friendly URLs, was tested for accessibility, and ensures smooth user navigation.'],
                ],
            ],
            [
                'file_path' => 'app/Http/Kernel.php',
                'file_name' => 'Kernel.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Added middleware',
                'description' => 'HTTP kernel configuration',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Default middleware setup.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added custom middleware for request logging, enhancing debugging capabilities. The update was tested for performance and ensures no conflicts with existing middleware.'],
                ],
            ],
            [
                'file_path' => 'app/Filament/Widgets/RevenueChart.php',
                'file_name' => 'RevenueChart.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Interactive charts',
                'description' => 'Revenue visualization widget',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic chart setup.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added monthly data.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Introduced interactive charts with tooltips and zoom, using Chart.js. The update enhances data analysis, supports multiple timeframes, and was optimized for performance with large datasets.'],
                ],
            ],
            [
                'file_path' => 'resources/views/components/card.blade.php',
                'file_name' => 'card.blade.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Shadow effects',
                'description' => 'Reusable card component',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic card layout.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added shadow effects and hover states, improving visual appeal. The update uses Tailwind classes, was tested for consistency across pages, and supports dynamic content.'],
                ],
            ],
            [
                'file_path' => 'app/Models/Tag.php',
                'file_name' => 'Tag.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 2: Tagging system',
                'description' => 'Tag model for posts',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic tag fields.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Implemented a many-to-many tagging system for posts, improving content categorization. The update includes pivot table management, was tested for performance, and ensures no data integrity issues.'],
                ],
            ],
            [
                'file_path' => 'database/factories/UserFactory.php',
                'file_name' => 'UserFactory.php',
                'artifact_id' => Uuid::uuid4()->toString(),
                'latest_version' => 'Update 3: Realistic data',
                'description' => 'User factory for testing',
                'versions' => [
                    ['update_number' => 1, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Basic factory setup.'],
                    ['update_number' => 2, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Added faker data.'],
                    ['update_number' => 3, 'version_id' => Uuid::uuid4()->toString(), 'description' => 'Enhanced with realistic data for profiles and roles, improving test reliability. The update supports dynamic data generation, was tested for seeding consistency, and ensures compatibility with the User model.'],
                ],
            ],
        ];

        foreach ($files as $file) {
            $artifact = Artifact::create([
                'file_path' => $file['file_path'],
                'file_name' => $file['file_name'],
                'artifact_id' => $file['artifact_id'],
                'latest_version' => $file['latest_version'],
                'description' => $file['description'],
            ]);

            foreach ($file['versions'] as $version) {
                Version::create([
                    'artifact_id' => $artifact->id,
                    'version_id' => $version['version_id'],
                    'update_number' => $version['update_number'],
                    'description' => $version['description'],
                ]);
            }
        }
    }
}