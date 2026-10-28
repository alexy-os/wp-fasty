<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFastyTypes\Contact;

require_once dirname(__DIR__) . '/plugins/wpfasty-types/src/Contact.php';

/**
 * Settings → Contact & hours of wpfasty-types: the sanitizers keep only what the Codex settings contact block accepts
 * (@ui8kit/contract SettingsV04 contact, hours, hours_exceptions, map), and the text form of the lists round-trips.
 */
final class ContactTest extends TestCase
{
    public function testOptionsMatchTheContractAdapter(): void
    {
        // @ui8kit/contract src/adapters/wordpress.ts WP_CONTACT_OPTIONS
        $this->assertSame([
            'wpfasty_contact_phone' => 'contact.phone',
            'wpfasty_contact_email' => 'contact.email',
            'wpfasty_contact_address' => 'contact.address',
            'wpfasty_contact_links' => 'contact.links',
            'wpfasty_hours' => 'hours',
            'wpfasty_hours_exceptions' => 'hours_exceptions',
            'wpfasty_map' => 'map',
        ], Contact::OPTIONS);
    }

    public function testPhoneIsE164(): void
    {
        $this->assertSame('+12025550143', Contact::phone('+1 (202) 555-0143'));
        $this->assertSame('+442079460123', Contact::phone('0044 20 7946 0123'));
        $this->assertSame('+12', Contact::phone('+12'));
        foreach (['12025550143', '+0123', '+1234567890123456', 'call me', '', '+', null, ['+1']] as $bad) {
            $this->assertSame('', Contact::phone($bad), var_export($bad, true));
        }
    }

    public function testEmailAndAddress(): void
    {
        $this->assertSame('hello@example.com', Contact::email(' hello@example.com '));
        $this->assertSame('', Contact::email('hello@example'));
        $this->assertSame('', Contact::email('a b@example.com'));
        $this->assertSame('1 Example Street, Springfield', Contact::line("  1 Example Street,\n Springfield <b></b>"));
    }

    public function testLinksKeepTheirOrderAndNormalizeHandles(): void
    {
        $text = "whatsapp 1 202 555 0188\ntelegram @example_shop\ninstagram example.shop\nTelegram https://t.me/second\nbad\nx ";
        $links = Contact::links($text, $bad);
        $this->assertSame([
            ['network' => 'whatsapp', 'handle' => '+12025550188'],
            ['network' => 'telegram', 'handle' => 'example_shop'],
            ['network' => 'instagram', 'handle' => 'example.shop'],
            ['network' => 'telegram', 'handle' => 'second'],
        ], $links);
        $this->assertSame([5, 6], $bad);
        $this->assertSame($links, Contact::links(Contact::linksText($links)));
        // REST arrays pass the same checks
        $this->assertSame([['network' => 'x', 'handle' => 'shop']], Contact::links([['network' => 'X', 'handle' => 'shop'], ['network' => 'x']]));
        $this->assertSame([], Contact::links(['network' => 'x', 'handle' => 'y']));
    }

    public function testHoursFromTextAndArrays(): void
    {
        $hours = Contact::hours("mon-fri 9:00-18:00\nsat,sun 10:00 – 14:00\nfri-mon 20:00-02:00\nsun 00:00-24:00\nmon 09:00-09:00\nxyz 09:00-10:00\nmon 24:00-10:00", $bad);
        $this->assertSame([
            ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'open' => '09:00', 'close' => '18:00'],
            ['days' => ['sat', 'sun'], 'open' => '10:00', 'close' => '14:00'],
            ['days' => ['fri', 'sat', 'sun', 'mon'], 'open' => '20:00', 'close' => '02:00'],
            ['days' => ['sun'], 'open' => '00:00', 'close' => '24:00'],
        ], $hours);
        $this->assertSame([5, 6, 7], $bad);
        $this->assertSame("mon-fri 09:00-18:00\nsat,sun 10:00-14:00\nfri,sat,sun,mon 20:00-02:00\nsun 00:00-24:00", Contact::hoursText($hours));
        $this->assertSame($hours, Contact::hours(Contact::hoursText($hours)));
        $this->assertSame([], Contact::hours([['days' => ['mon', 'mon'], 'open' => '09:00', 'close' => '10:00']]));
        $this->assertSame([], Contact::hours([['days' => [], 'open' => '09:00', 'close' => '10:00']]));
    }

    public function testExceptionsOnePerDate(): void
    {
        $x = Contact::exceptions("2026-12-25 closed\n2026-12-24 9:00-13:00\n2026-12-25 10:00-12:00\n2026-02-30 closed\n2026-12-31\n2026-12-30 10:00-10:00", $bad);
        $this->assertSame([
            ['date' => '2026-12-25', 'closed' => true],
            ['date' => '2026-12-24', 'open' => '09:00', 'close' => '13:00'],
        ], $x);
        $this->assertSame([3, 4, 5, 6], $bad);
        $this->assertSame($x, Contact::exceptions(Contact::exceptionsText($x)));
        $this->assertSame([], Contact::exceptions([['date' => '2026-01-01', 'closed' => true, 'open' => '10:00', 'close' => '11:00']]));
        $this->assertSame([], Contact::exceptions([['date' => '2026-01-01', 'closed' => false]]));
    }

    public function testMapProviderAndCoordinatesTogether(): void
    {
        $this->assertSame(['provider' => 'osm', 'lat' => 38.8977, 'lng' => -77.0365], Contact::map(['provider' => 'OSM', 'lat' => '38.8977', 'lng' => '-77,0365']));
        $this->assertSame(['provider' => 'google'], Contact::map(['provider' => 'google', 'lat' => '91', 'lng' => '0']));
        $this->assertSame([], Contact::map(['provider' => '', 'lat' => '10', 'lng' => '']));
        $this->assertSame(['lat' => 0.0, 'lng' => 0.0], Contact::map(['lat' => 0, 'lng' => '0']));
        $this->assertSame([], Contact::map('osm'));
    }

    public function testBlockIsEmptyWithoutOptions(): void
    {
        // tests/bootstrap.php get_option() returns the default: nothing set
        $this->assertSame([], Contact::block());
    }
}
