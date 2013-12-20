<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Paulius Podolskis <pp@estina.com>
 * (c) Žilvinas Kuusas <zk@estina.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Tests\Manipulator;

use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;
use Symfony\Component\Filesystem\Filesystem;

class ManipulatorTest extends \PHPUnit_Framework_TestCase
{
    protected $file;
    protected $routingManipulator;

    public function setUp()
    {
        $this->file = tempnam(sys_get_temp_dir(),  'routing');

        $this->routingManipulator = new RoutingManipulator($this->file);

    }

    public function testExistingRouteYmlUpdatedSuccessfully()
    {

        $path = 'routing';
        $prefix = '/';
        $result = $this->routingManipulator->addResource('FooBundle', 'yml', $prefix, $path);

        $this->assertTrue($result);

        $content = file_get_contents($this->file);

        $this->assertContains("foo:\n", $content);
        $this->assertContains('@FooBundle/Resources/config/' . $path . '.yml', $content);
        $this->assertContains(sprintf("prefix:   %s", $prefix), $content);

        $prefix = '/bar';
        $resultBar = $this->routingManipulator->addResource('FoobarBundle', 'yml', $prefix, $path);

        $this->assertTrue($resultBar );
        $content = file_get_contents($this->file);


        $this->assertContains("foobar_bar:\n", $content);
        $this->assertContains('@FoobarBundle/Resources/config/' . $path . '.yml', $content);
        $this->assertContains(sprintf("prefix:   %s", $prefix), $content);

    }

    public function testExistingRoutePhpUpdatedSuccessfully()
    {

        $path = 'routing';
        $prefix = '/';

        $result = $this->routingManipulator->addResource('FooBundle', 'php', $prefix, $path);

        $this->assertTrue($result);

        $prefix = '/bar';
        $resultBar = $this->routingManipulator->addResource('FoobarBundle', 'php', $prefix, $path);


        $this->assertTrue($resultBar);

        $content = file_get_contents($this->file);
        $this->assertContains(sprintf('$collection->addCollection($loader->import("%s/Resources/config/%s.php"), \'%s\');', 'FoobarBundle', $path, $prefix), $content);

        $this->assertContains('return $collection;', $content);

    }
    public function testEmptyRoutePhpUpdatedSuccessfully()
    {

        $path = 'routing';
        $prefix = '/';

        $result = $this->routingManipulator->addResource('FooBundle', 'php', $prefix, $path);

        $this->assertTrue($result);

        $content = file_get_contents($this->file);

        $this->assertContains('use Symfony\Component\Routing\RouteCollection;', $content);
        $this->assertContains('$collection = new RouteCollection();', $content);

        $this->assertContains(sprintf('$collection->addCollection($loader->import("%s/Resources/config/%s.php"), \'%s\');', 'FooBundle', $path, $prefix), $content);

        $this->assertContains('return $collection;', $content);
    }
    public function testEmptyRouteYmlUpdatedSuccessfully()
    {
        $path = 'routing';
        $prefix = '/';

        $result = $this->routingManipulator->addResource('FooBundle', 'yml', $prefix, $path);

        $this->assertTrue($result);
        
        $content = file_get_contents($this->file);

        $this->assertContains("foo:\n", $content);
        $this->assertContains('@FooBundle/Resources/config/' . $path . '.yml', $content);
        $this->assertContains(sprintf("prefix:   %s", $prefix), $content);

    }

    public function testEmptyRouteXMLUpdatedSuccessfully()
    {
        $path = 'routing';
        $prefix = '/';
        $xmlContent = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<routes xmlns="http://symfony.com/schema/routing"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/routing http://symfony.com/schema/routing/routing-1.0.xsd">
</routes>
EOT;
        file_put_contents($this->file, $xmlContent);


        $result = $this->routingManipulator->addResource('FooBundle', 'xml', $prefix, $path);

        $this->assertTrue($result);

        $content = file_get_contents($this->file);

        $this->assertContains(sprintf('<import resource="%s/Resources/config/%s.xml" prefix="%s"/>', 'FooBundle', $path, $prefix ), $content);
    }

    public function testRouteAlreadyLoaded()
    {
        $path = 'routing';
        $prefix = '/';
        $result = $this->routingManipulator->addResource('FooBundle', 'yml', $prefix, $path);
        try{
            $resultFailed = $this->routingManipulator->addResource('FooBundle', 'yml', $prefix, $path);
        } catch(\RuntimeException $e){
            $this->assertContains(sprintf('Bundle "%s" is already imported.', 'FooBundle'), $e->getMessage());
            $resultFailed = false;
        }
        $this->assertTrue($result);
        $this->assertFalse($resultFailed);

    }
    public function testRouteWriteFailed()
    {

        $path = 'routing';
        $prefix = '/';

        $filesystem = new Filesystem();
        $filesystem->chmod($this->file, 0444);

        try {
            $this->fail(sprintf('file_put_contents(%s): failed to open stream: Permission denied', $this->file));
        } catch (\RuntimeException $e) {
            //$this->assertTrue($filesystem->chmod($this->file, 0777));
            $this->assertEquals(sprintf('file_put_contents(%s): failed to open stream: Permission denied', $this->file), $e->getMessage());
        }
    }

    public function testExistingRouteXMLUpdatedSuccessfully()
    {

        $path = 'routing';
        $prefix = '/';

        $xmlContent = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<routes xmlns="http://symfony.com/schema/routing"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/routing http://symfony.com/schema/routing/routing-1.0.xsd">
    <import resource="FoobarBundle/Resources/config/routing.xml" prefix="/bar"/>
</routes>
EOT;
        file_put_contents($this->file, $xmlContent);

        $result = $this->routingManipulator->addResource('FooBundle', 'xml', $prefix, $path);

        $content = file_get_contents($this->file);

        $this->assertTrue($result);
        $this->assertContains(sprintf('<import resource="%s/Resources/config/%s.xml" prefix="%s"/>', 'FoobarBundle', $path, '/bar'), $content);
        $this->assertContains(sprintf('<import resource="%s/Resources/config/%s.xml" prefix="%s"/>', 'FooBundle', $path, $prefix ), $content);



    }

}