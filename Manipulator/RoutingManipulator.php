<?php

/**
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Manipulator;

use Symfony\Component\DependencyInjection\Container;

/**
 * Changes the PHP code of a YAML routing file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RoutingManipulator extends Manipulator
{
    private $file;

    /**
     * Constructor.
     *
     * @param string $file The YAML routing file path
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Adds a routing resource at the top of the existing ones.
     *
     * @param string $bundle
     * @param string $format
     * @param string $prefix
     * @param string $path
     *
     * @return Boolean true if it worked, false otherwise
     *
     * @throws \RuntimeException If bundle is already imported
     */
    public function addResource($bundle, $format, $prefix = '/', $path = 'routing')
    {
        $current = '';
        if (file_exists($this->file)) {
            $current = file_get_contents($this->file);

            // Don't add same bundle twice
            if (false !== strpos($current, $bundle)) {
                throw new \RuntimeException(sprintf('Bundle "%s" is already imported.', $bundle));
            }
        } elseif (!is_dir($dir = dirname($this->file))) {
            mkdir($dir, 0777, true);
        }

        $code = sprintf("%s:\n", Container::underscore(substr($bundle, 0, -6)).('/' !== $prefix ? '_'.str_replace('/', '_', substr($prefix, 1)) : ''));
        if ('php' == $format) {
            $code = $current;
            if (empty($current)) {
                $code .= '<?php' . "\n" ;
                $code .= 'use Symfony\Component\Routing\RouteCollection;' . "\n" ;
                $code .= '$collection = new RouteCollection();' . "\n" ;
            } else {
                $code = str_replace('return $collection;', '', $code);
            }
            $code .= sprintf('$collection->addCollection($loader->import("%s/Resources/config/routing.php"), \'%s\');',
                $bundle, $prefix);
            $code .= "\n";
            $code .= 'return $collection;';

        } elseif ('xml' == $format) {
            $xml = simplexml_load_string($current);
            $newRoute = $xml->addChild('import');
            $newRoute->addAttribute('resource', $bundle . '/Resources/config/routing.xml');
            $newRoute->addAttribute('prefix', $prefix);

            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            $code = $dom->saveXML();

        } elseif ('yml' == $format) {
            $code .= sprintf("    resource: \"@%s/Resources/config/%s.%s\"\n", $bundle, $path, $format);

            $code .= sprintf("    prefix:   %s\n", $prefix);
            $code .= "\n";
            $code .= $current;
        } else {
            // If $format == annotations or unknown format - do nothing
            return true;
        }

        if (false === file_put_contents($this->file, $code)) {
            return false;
        }

        return true;
    }
}
