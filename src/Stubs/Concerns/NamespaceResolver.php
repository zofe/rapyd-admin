<?php

namespace Zofe\Rapyd\Stubs\Concerns;

trait NamespaceResolver {

    /**
     * Resolve the class name and class store path from give class namespace
     * In case a full class namespace provides, need to extract class name
     *
     * @param  string $name
     * @return string
     */
    public function resolveClassName(string $name) {

        $classNameExplode = explode('\\', $name);

        if ( count($classNameExplode) <= 1 ) {

            return $name;
        }

        return last($classNameExplode);
    }


    /**
     * Resolve the class namespace from given class name
     *
     * @param  string $name
     * @return mixed<string|null>
     */
    public function resolveClassNamespace(string $name) {

        $classFullNameExplode = explode('\\', $name);

        if ( count($classFullNameExplode) <= 1 ) {

            return null;
        }

        array_pop($classFullNameExplode);

        return implode('\\', $classFullNameExplode);
    }


    /**
     * Generate class store path from give namespace
     *
     * @param  mixed<string|null> $namespace
     * @return mixed<string|null>
     */
    public function generateFilePathFromNamespace(string $namespace = null) {

        if ( ! $namespace ) {

            return null;
        }

        $namespaceSegments = explode('\\', $namespace);

        return '/' . implode('/', array_values(array_filter($namespaceSegments)));
    }

}
