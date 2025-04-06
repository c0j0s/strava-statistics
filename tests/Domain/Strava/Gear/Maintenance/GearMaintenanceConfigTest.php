<?php

namespace App\Tests\Domain\Strava\Gear\Maintenance;

use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\InvalidGearMaintenanceConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class GearMaintenanceConfigTest extends TestCase
{
    public function testFromYmlStringWhenEmpty(): void
    {
        $this->assertEquals(
            'The gear maintenance feature is disabled.',
            (string) GearMaintenanceConfig::fromYmlString(null),
        );

        $this->assertEquals(
            'The gear maintenance feature is disabled.',
            (string) GearMaintenanceConfig::fromYmlString(''),
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromYmlStringItShouldThrow(string $yml, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidGearMaintenanceConfig($expectedException));
        GearMaintenanceConfig::fromYmlString($yml);
    }

    public static function provideInvalidConfig(): iterable
    {
        yield 'could not parse' => ['[string[', 'Malformed inline YAML string at line 1 (near "[string[").'];
        yield 'invalid yml' => ['string', 'YML expected to be an array'];

        $yml = self::getValidYmlString();
        unset($yml['enabled']);
        yield 'missing "enabled" key' => [Yaml::dump($yml), '"enabled" property is required'];

        $yml = self::getValidYmlString();
        unset($yml['hashtagPrefix']);
        yield 'missing "hashtagPrefix" key' => [Yaml::dump($yml), '"hashtagPrefix" property is required'];

        $yml = self::getValidYmlString();
        unset($yml['components']);
        yield 'missing "components" key' => [Yaml::dump($yml), '"components" property is required'];

        $yml = self::getValidYmlString();
        unset($yml['gears']);
        yield 'missing "gears" key' => [Yaml::dump($yml), '"gears" property is required'];

        $yml = self::getValidYmlString();
        $yml['components'] = 'string';
        yield '"components" is not an array' => [Yaml::dump($yml), '"components" property must be an array'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['tag']);
        yield 'missing "components[tag]" key' => [Yaml::dump($yml), '"tag" property is required for each component'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['label']);
        yield 'missing "components[label]" key' => [Yaml::dump($yml), '"label" property is required for each component'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['attachedTo']);
        yield 'missing "components[attachedTo]" key' => [Yaml::dump($yml), '"attachedTo" property is required for each component'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['maintenance']);
        yield 'missing "components[maintenance]" key' => [Yaml::dump($yml), '"maintenance" property is required for each component'];

        $yml = self::getValidYmlString();
        $yml['components'][0]['attachedTo'] = 'string';
        yield '"components[attachedTo]" is not an array' => [Yaml::dump($yml), '"attachedTo" property must be an array'];

        $yml = self::getValidYmlString();
        $yml['components'][0]['maintenance'] = 'string';
        yield '"components[maintenance]" is not an array' => [Yaml::dump($yml), '"maintenance" property must be an array'];

        $yml = self::getValidYmlString();
        $yml['components'][0]['imgSrc'] = [];
        yield '"components[imgSrc]" is not an string' => [Yaml::dump($yml), '"imgSrc" property must be a string'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['maintenance'][0]['tag']);
        yield 'missing "components[maintenance][tag]" key' => [Yaml::dump($yml), '"tag" property is required for each maintenance task'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['maintenance'][0]['label']);
        yield 'missing "components[maintenance][label]" key' => [Yaml::dump($yml), '"label" property is required for each maintenance task'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['maintenance'][0]['interval']);
        yield 'missing "components[maintenance][interval]" key' => [Yaml::dump($yml), '"interval" property is required for each maintenance task'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['maintenance'][0]['interval']['value']);
        yield 'missing "components[maintenance][interval][value]" key' => [Yaml::dump($yml), '"interval" property must have "value" and "unit" properties'];

        $yml = self::getValidYmlString();
        unset($yml['components'][0]['maintenance'][0]['interval']['unit']);
        yield 'missing "components[maintenance][interval][unit]" key' => [Yaml::dump($yml), '"interval" property must have "value" and "unit" properties'];

        $yml = self::getValidYmlString();
        $yml['components'][0]['maintenance'][0]['tag'] = 'lubed';
        $yml['components'][0]['maintenance'][1]['tag'] = 'lubed';
        yield 'duplicate maintenance tags' => [Yaml::dump($yml), 'duplicate maintenance tags found for component "Some cool chain:" lubed'];

        $yml = self::getValidYmlString();
        $yml['components'][0]['tag'] = 'chain';
        $yml['components'][1]['tag'] = 'chain';
        yield 'duplicate component tags' => [Yaml::dump($yml), 'duplicate component tags found: chain'];

        $yml = self::getValidYmlString();
        $yml['gears'] = 'string';
        yield '"gears" is not an array' => [Yaml::dump($yml), '"gears" property must be an array'];

        $yml = self::getValidYmlString();
        $yml['gears'][0]['gearId'] = '';
        yield '"gears[gearId]" is empty' => [Yaml::dump($yml), '"gearId" property is required for each gear'];

        $yml = self::getValidYmlString();
        $yml['gears'][0]['imgSrc'] = '';
        yield '"gears[imgSrc]" is empty' => [Yaml::dump($yml), '"imgSrc" property is required for each gear'];
    }

    private static function getValidYmlString(): array
    {
        return Yaml::parse(<<<YML
enabled: false
hashtagPrefix: 'sfs'
components:
  - tag: 'chain'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - 'bike-one-gear-id'
      - 'bike-two-gear-id'
    maintenance:
      - tag: lubed
        label: Lube
        interval:
          value: 500
          unit: km
      - label: Clean
        tag: cleaned
        interval:
          value: 200
          unit: hours
      - label: Replace
        tag: replaced
        interval:
          value: 500
          unit: days
  - tag: 'chain-two'
    label: 'Some cool chain'
    imgSrc: 'chain.png'
    attachedTo:
      - 'bike-one-gear-id'
      - 'bike-two-gear-id'
    maintenance:
      - tag: lubed
        label: Lube
        interval:
          value: 500
          unit: km
gears:
  - gearId: 'g12337767'
    imgSrc: 'gear1.png'
YML);
    }
}
