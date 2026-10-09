<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

/**
 * Finding candidate images on the open web (Google Lens-style visual search).
 *
 * NOT IMPLEMENTED — no visual-search API is configured on this host, and
 * scraping search engines or social platforms is forbidden by the research
 * rules. The null implementation returns an explicit capability_limited result
 * rather than an empty list, so a caller can never mistake "we cannot look"
 * for "we looked and found nothing".
 */
interface WebVisualSearchProvider extends ProviderContract
{
    /** @return array{status:string,results:array,note:string} */
    public function searchByImage(string $imagePath, array $params = []): array;
}
