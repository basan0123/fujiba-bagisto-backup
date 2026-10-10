<?php

namespace Tests\Feature\Saas;

use App\Saas\Entitlements;
use InvalidArgumentException;
use Tests\TestCase;

class SaasLandingTest extends TestCase
{
    public function test_landing_renders_on_central_domain_in_japanese_by_default(): void
    {
        $this->get('http://snssale.com/', ['Accept-Language' => 'ja'])
            ->assertOk()
            ->assertSee('今日からあなたのネットショップを')
            ->assertSee('data-plan="business"', false)
            ->assertSee('id="demo"', false);
    }

    public function test_lang_query_switches_to_english_and_myanmar_and_untranslated_fallback(): void
    {
        $this->get('http://snssale.com/?lang=en')->assertOk()->assertSee('Open your online shop today');
        $this->get('http://snssale.com/?lang=my')->assertOk()->assertSee('သင့်အွန်လိုင်းဆိုင်ကို ယနေ့ပဲ ဖွင့်လိုက်ပါ');
        // Nepali is not offered until translated: no button, no English under a Nepali label.
        $this->get('http://snssale.com/?lang=ne', ['Accept-Language' => 'ja'])
            ->assertOk()->assertDontSee('lang=ne', false)->assertSee('lang="ja"', false);
    }

    public function test_unconfirmed_prices_show_contact_us_not_yen(): void
    {
        config(['saas.plans.starter.monthly_price' => null]);

        $html = $this->get('http://snssale.com/?lang=ja')->assertOk()->assertSee('お問い合わせ')->getContent();
        // Only the plan cards must be yen-free (the demo shows a real product price).
        $pricing = substr($html, strpos($html, 'id="pricing"'), strpos($html, 'id="faq"') - strpos($html, 'id="pricing"'));
        $this->assertStringNotContainsString('¥', $pricing);
        $this->get('http://snssale.com/?lang=my')->assertOk()->assertSee('ဆက်သွယ်ရန်');
    }

    public function test_myanmar_stays_noindex_even_when_site_is_public(): void
    {
        config(['saas.public' => true]);

        $this->get('http://snssale.com/?lang=my')->assertSee('<meta name="robots" content="noindex">', false);
        $this->get('http://snssale.com/?lang=ja')->assertDontSee('name="robots"', false);
    }

    public function test_landing_never_links_to_admin_login(): void
    {
        foreach (['ja', 'en', 'my'] as $lang) {
            $this->get("http://snssale.com/?lang={$lang}")->assertDontSee('superadmin')->assertDontSee('/admin');
        }
    }

    public function test_apex_shows_landing_not_admin_login(): void
    {
        $this->get('http://snssale.com/?lang=en')->assertOk()->assertSee('Open your online shop today')->assertDontSee('superadmin');
        $this->get('http://snssale.com/saas?lang=en')->assertRedirect('/?lang=en');
    }

    public function test_admin_and_login_routes_send_noindex_header(): void
    {
        $this->get('http://snssale.com/superadmin/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('http://snssale.com/?lang=en')->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_entitlements_limits_and_features(): void
    {
        $starter = Entitlements::for('starter');

        $this->assertTrue($starter->allows('products', 99));
        $this->assertFalse($starter->allows('products', 100));
        $this->assertTrue(Entitlements::for('pro')->allows('products', 1_000_000));
        $this->assertFalse($starter->hasFeature('custom_domain'));
        $this->assertTrue(Entitlements::for('business')->hasFeature('custom_domain'));
    }

    public function test_unknown_plan_or_limit_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Entitlements::for('free-forever');
    }
}
