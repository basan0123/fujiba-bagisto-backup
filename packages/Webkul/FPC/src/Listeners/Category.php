<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Category\Repositories\CategoryRepository;

class Category
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(protected CategoryRepository $categoryRepository) {}

    /**
     * After category update
     *
     * @param  \Webkul\Category\Contracts\Category  $category
     * @return void
     */
    public function afterUpdate($category)
    {
        $this->forgetSlugCaches($category);
    }

    /**
     * Before category delete
     *
     * @param  int  $categoryId
     * @return void
     */
    public function beforeDelete($categoryId)
    {
        $category = $this->categoryRepository->find($categoryId);

        if (! $category) {
            return;
        }

        $this->forgetSlugCaches($category);
    }

    /**
     * Forget the FPC entry for every locale slug the category has,
     * plus the default-channel locale slug (once, not per loop iteration).
     * Null-safe: missing translations or empty slugs are skipped.
     */
    protected function forgetSlugCaches($category): void
    {
        foreach (core()->getAllLocales() as $locale) {
            $translation = $category->translate($locale->code);
            if ($translation && ! empty($translation->slug)) {
                ResponseCache::forget($translation->slug);
            }
        }

        $defaultLocale = core()->getDefaultLocaleCodeFromDefaultChannel();
        $defaultTranslation = $defaultLocale ? $category->translate($defaultLocale) : null;
        if ($defaultTranslation && ! empty($defaultTranslation->slug)) {
            ResponseCache::forget($defaultTranslation->slug);
        }
    }
}
