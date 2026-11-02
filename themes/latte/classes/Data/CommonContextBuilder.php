<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Core\Model\RequestState;
use WPFasty\Core\Model\SiteInfo;
use WPFasty\Core\Port\LabelProviderInterface;
use WPFasty\Core\Port\MenuProviderInterface;
use WPFasty\Core\Port\WidgetAreaProviderInterface;
use WPFasty\Data\Menu\MenuViewModelBuilder;
use WPFasty\Data\Site\SiteViewModelBuilder;

/** The keys every template context shares: site, menu, shell, sheet, labels, search, widgets. */
final class CommonContextBuilder
{
    public function __construct(
        private readonly MenuProviderInterface $menus,
        private readonly MenuViewModelBuilder $menuBuilder,
        private readonly LabelProviderInterface $labels,
        private readonly WidgetAreaProviderInterface $widgets,
        private readonly SiteViewModelBuilder $siteBuilder,
    ) {
    }

    /**
     * @return array{
     *   site: array<string, mixed>,
     *   menu: array<string, mixed>,
     *   shell: string,
     *   sheet: array<string, string>,
     *   labels: array<string, string>,
     *   search: array<string, string>,
     *   widgets: array<string, string>
     * }
     */
    public function build(SiteInfo $site, RequestState $request): array
    {
        $shell = $this->siteBuilder->shell($request->shell);

        return [
            'site' => $this->siteBuilder->site($site, $request->year),
            'menu' => $this->menuBuilder->build($this->menus->itemsByLocation()),
            'shell' => $shell,
            'sheet' => $this->siteBuilder->sheetIds($shell),
            'labels' => $this->labels->labels(),
            'search' => $this->siteBuilder->search($site, $request->searchQuery, $this->labels->search()),
            'widgets' => [
                'sidebar' => $request->fullWidth ? '' : (string) $this->widgets->render('sidebar-1'),
                'footer' => (string) $this->widgets->render('footer-1'),
            ],
        ];
    }
}
