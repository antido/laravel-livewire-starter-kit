<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Builds the sidebar and the dashboard quick links from config/navigation.php for one user: an item is
 * kept only when its route exists and the user holds its permission. Hiding an item is a convenience,
 * not a security control; every route is still protected by middleware and policies.
 *
 * @phpstan-type NavigationItem array{label: string, route: string, active: string, icon: string, permission: string|null, description: string, quick_link?: bool}
 * @phpstan-type NavigationSection array{label: string|null, items: list<NavigationItem>}
 */
class NavigationService
{
    /**
     * The most quick links the dashboard shows.
     */
    public const int MAX_QUICK_LINKS = 4;

    /**
     * The sidebar sections and items this user may see. Sections without visible items are dropped.
     *
     * @return list<NavigationSection>
     */
    public function sectionsFor(User $user): array
    {
        $sections = [];

        foreach ($this->sections() as $section) {
            $items = $this->visible($user, $section['items']);

            if ($items !== []) {
                $sections[] = ['label' => $section['label'], 'items' => $items];
            }
        }

        return $sections;
    }

    /**
     * Every sidebar item this user may see, flattened, in menu order.
     *
     * @return list<NavigationItem>
     */
    public function itemsFor(User $user): array
    {
        $items = [];

        foreach ($this->sectionsFor($user) as $section) {
            array_push($items, ...$section['items']);
        }

        return $items;
    }

    /**
     * The dashboard quick links this user may see (sidebar items flagged as quick links, then the account links).
     *
     * @return list<NavigationItem>
     */
    public function quickLinksFor(User $user): array
    {
        $candidates = [];

        foreach ($this->sections() as $section) {
            array_push($candidates, ...$section['items']);
        }

        array_push($candidates, ...$this->accountItems());

        $quickLinks = array_filter($candidates, fn (array $item): bool => ($item['quick_link'] ?? false) === true);

        return array_slice($this->visible($user, array_values($quickLinks)), 0, self::MAX_QUICK_LINKS);
    }

    /**
     * @param  NavigationItem  $item
     */
    public function isActive(array $item): bool
    {
        return request()->routeIs($item['active']);
    }

    /**
     * @param  list<NavigationItem>  $items
     * @return list<NavigationItem>
     */
    private function visible(User $user, array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item): bool => Route::has($item['route'])
                && ($item['permission'] === null || $user->can($item['permission'])),
        ));
    }

    /**
     * @return list<NavigationSection>
     */
    private function sections(): array
    {
        /** @var list<NavigationSection> $sections */
        $sections = config('navigation.sections', []);

        return $sections;
    }

    /**
     * @return list<NavigationItem>
     */
    private function accountItems(): array
    {
        /** @var list<NavigationItem> $items */
        $items = config('navigation.account', []);

        return $items;
    }
}
