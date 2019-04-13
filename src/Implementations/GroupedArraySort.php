<?php
/**
 * Copyright © Marc J. Schmidt. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vaimo\TopSort\Implementations;

use Vaimo\TopSort\ElementNotFoundException;
use Vaimo\TopSort\GroupedTopSortInterface;

/**
 * Implements grouped topological-sort based on arrays.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GroupedArraySort extends BaseImplementation implements GroupedTopSortInterface
{
    protected $elements = array();
    protected $sorted;
    protected $position = 0;
    protected $groups = array();
    protected $groupLevel = 0;

    protected $debugging = false;

    /**
     * When active the sorter creates a new group when a element has a dependency to the same type.
     *
     * @var bool
     */
    protected $sameTypeGrouping = false;

    /**
     * @return boolean
     */
    public function isSameTypeExtraGrouping()
    {
        return $this->sameTypeGrouping;
    }

    /**
     * @param boolean $flagState
     */
    public function setSameTypeExtraGrouping($flagState)
    {
        $this->sameTypeGrouping = $flagState;
    }

    /**
     * @param string   $name
     * @param string   $type
     * @param string[] $dependencies
     */
    public function add($name, $type, $dependencies = array())
    {
        $dependencies = (array)$dependencies;
        $this->elements[$name] = (object)array(
            'id' => $name,
            'type' => $type,
            'dependencies' => $dependencies,
            'dependenciesCount' => count($dependencies),
            'visited' => false,
            'addedAtLevel' => -1
        );
    }


    /**
     * @param array[] $elements ['id' => ['type', ['dep1', 'dep2']], 'id2' => ...]
     */
    public function set(array $elements)
    {
        foreach ($elements as $element => $typeAndDependencies) {
            $this->add(
                $element,
                $typeAndDependencies[0],
                isset($typeAndDependencies[1]) ? $typeAndDependencies[1] : array()
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @inheritDoc
     *
     * @return integer level of group in which it has been added
     */
    protected function visit($element, &$parents = null)
    {
        $this->throwCircularExceptionIfNeeded($element, $parents);

        // If element has not been visited
        if (!$element->visited) {
            $parents[$element->id] = true;

            $element->visited = true;

            $minLevel = -1;
            foreach ($element->dependencies as $dependency) {
                if (!isset($this->elements[$dependency])) {
                    throw ElementNotFoundException::create($element->id, $dependency);
                }

                $newParents = $parents;
                $addedAtGroupLevel = $this->visit($this->elements[$dependency], $newParents, $element);
                
                if ($addedAtGroupLevel > $minLevel) {
                    $minLevel = $addedAtGroupLevel;
                }
                
                if ($this->isSameTypeExtraGrouping()) {
                    if ($this->elements[$dependency]->type === $element->type) {
                        //add a new group
                        $minLevel = $this->groupLevel;
                    }
                }
            }
            
            $this->injectElement($element, $minLevel);

            return $minLevel === -1 ? $element->addedAtLevel : $minLevel;
        }

        return $element->addedAtLevel;
    }

    /**
     * @param object  $element
     * @param integer $minLevel
     */
    protected function injectElement($element, $minLevel)
    {
        $group = $this->getFirstGroup($element->type, $minLevel);
        
        if ($group) {
            $this->addItemAt($group->position + $group->length, $element);
            $group->length++;
            
            $position = $group->position;
            
            foreach ($this->groups as $tempGroup) {
                if ($tempGroup->position > $position) {
                    $tempGroup->position++;
                }
            }
            
            $element->addedAtLevel = $group->level;
            $this->position++;
            
            return;
        }

        $this->groups[] = (object)array(
            'type' => $element->type,
            'level' => $this->groupLevel,
            'position' => $this->position,
            'length' => 1
        );

        $element->addedAtLevel = $this->groupLevel;
        $this->sorted[] = $element->id;
        $this->position++;

        $this->groupLevel++;
    }

    /**
     * @param integer $position
     * @param object  $element
     */
    public function addItemAt($position, $element)
    {
        array_splice($this->sorted, $position, 0, $element->id);
    }

    /**
     * @inheritDoc
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return array
     */
    public function sortGrouped()
    {
        $items = $this->sort();
        $groups = array();
        foreach ($this->getGroups() as $group) {
            $groups[] = array(
                'type' => $group->type,
                'elements' => array_slice($items, $group->position, $group->length)
            );
        }

        return $groups;
    }

    /**
     * @param string  $type
     * @param integer $minLevel
     *
     * @return object|null
     */
    protected function getFirstGroup($type, $minLevel)
    {
        $level = $this->groupLevel;
        
        while ($level--) {
            $group = $this->groups[$level];

            if ($group->type === $type && $level >= $minLevel) {
                return $group;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function sort()
    {
        return $this->doSort();
    }

    /**
     * @inheritDoc
     */
    public function doSort()
    {
        if ($this->sorted) {
            foreach ($this->elements as $element) {
                $element->visited = false;
            }
        }

        $this->sorted = array();
        $this->groups = array();
        $this->position = 0;
        $this->groupLevel = 0;

        foreach ($this->elements as $element) {
            $parents = array();
            $this->visit($element, $parents);
        }

        return $this->sorted;
    }
}
