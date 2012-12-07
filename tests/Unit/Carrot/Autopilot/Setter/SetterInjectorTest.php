<?php

namespace Carrot\Autopilot\Setter;

use StdClass,
    PHPUnit_Framework_TestCase,
    Carrot\Autopilot\Identifier,
    Carrot\Autopilot\DependencyList,
    Carrot\Autopilot\Foo\Bar,
    Carrot\Autopilot\Foo\Egg\Baz;

/**
 * Unit test for the Autopilot SetterInjector class.
 * 
 * @author  Ricky Christie <seven.rchristie@gmail.com>
 *
 */
class SetterInjectorTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test object construction and the adding of dependency lists.
     * 
     * @use Carrot\Autopilot\Identifier
     * @use Carrot\Autopilot\DependencyList
     *
     */
    public function testNormalInjection()
    {
        $identifier = new Identifier('Carrot\Autopilot\Foo\Bar@Default');
        $setter = new SetterInjector($identifier);
        $list = $setter->getDependencyList();
        
        $this->assertEquals($identifier, $setter->getIdentifier());
        $this->assertEquals(TRUE, $list instanceof DependencyList);
        
        $object = new StdClass;
        $bar = new Bar;
        $baz = new Baz;
        $objectIdentifier = new Identifier('StdClass@Default');
        $bazIdentifier = new Identifier('Carrot\Autopilot\Foo\Egg\Baz@Default');
        $stringOne = 'One';
        $stringTwo = 'Two';
        $stringThree = 'Three';
        
        $setter->addToInjectionList(
            'setObject',
            array('object' => $objectIdentifier)
        );
        
        $setter->addToInjectionList(
            'setBaz',
            array(
                'stringThree' => $stringThree,
                'stringOne' => $stringOne,
                'baz' => $bazIdentifier,
                'stringTwo' => $stringTwo
            )
        );
        
        $setter->addToInjectionList(
            'setString',
            array('string' => 'Test')
        );
        
        $setter->addToInjectionList(
            'emptySetter',
            array()
        );
        
        $this->assertEquals(FALSE, $setter->isReadyForInjection());
        
        $list->setObject(
            $objectIdentifier->get(),
            $object
        );
        
        $list->setObject(
            $bazIdentifier->get(),
            $baz
        );
        
        $this->assertEquals(TRUE, $setter->isReadyForInjection());
        $setter->inject($bar);
        $this->assertEquals(TRUE, $baz === $bar->getBaz());
        $this->assertEquals(TRUE, $object === $bar->getObject());
        $this->assertEquals('Test', $bar->getString()); 
        $this->assertEquals($stringOne, $bar->getStringOne());
        $this->assertEquals($stringTwo, $bar->getStringTwo());
        $this->assertEquals($stringThree, $bar->getStringThree());
        $this->assertEquals('default', $bar->getStringDefault());
    }
}