<?php

namespace Fujiba\AutoPoster\Services\Intel\Providers;

interface MusicProvider extends ProviderContract
{
    public function compose(array $params = []): array;
}

/**
 * Finding candidate images on the open web (Google Lens-style visual search).
 *
 * NOT IMPLEMENTED — no visual-search API is configured on this host, and
 * scraping search engines or social platforms is forbidden by the research
 * rules. The null implementation returns an explicit capability_limited result
 * rather than an empty list, so a caller can never mistake "we cannot look"
 * for "we looked and found nothing".
 */
