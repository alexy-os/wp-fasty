<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

/** Translated UI strings (the adapter keeps the literal __() calls for the .pot extraction). */
interface LabelProviderInterface
{
    /** @return array<string, string> context `labels` */
    public function labels(): array;

    /** @return array{placeholder: string, submit: string, label: string} */
    public function search(): array;

    /** @return array{primaryLabel: string, secondaryLabel: string} */
    public function hero(): array;

    /** @return array{title: string, message: string, homeLabel: string} */
    public function error(): array;

    public function searchResultsTitle(string $query): string;
}
