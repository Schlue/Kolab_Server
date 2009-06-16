<?php
/**
 * Test the organizationalPerson object.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test the organizationalPerson object.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_OrgPersonTest extends Horde_Kolab_Test_Server
{
    /**
     * Objects used within this test
     *
     * @var array
     */
    private $objects = array(
        /* Default organizationalPerson */
        array(
            'type' => 'Horde_Kolab_Server_Object_Organizationalperson',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_OrgPersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_OrgPersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Invalid person (no sn) */
        array(
            'type' => 'Horde_Kolab_Server_Object_Organizationalperson',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_OrgPersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
        ),
    );

    /**
     * Provide different server types.
     *
     * @return array The different server types.
     */
    public function &provideServers()
    {
        $servers = array();
        /**
         * We always use the test server
         */
        $servers[] = array($this->prepareEmptyKolabServer());
        if (false) {
            $real = $this->prepareLdapKolabServer();
            if (!empty($real)) {
                $servers[] = array($real);
            }
        }
        return $servers;
    }

    /**
     * Test ID generation for a person.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testGenerateId($server)
    {
        $a = new Horde_Kolab_Server_Object_Organizationalperson($server, null, $this->objects[0]);
        $this->assertContains(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN . '=' . $this->objects[0][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN],
                              $a->get(Horde_Kolab_Server_Object_Person::ATTRIBUTE_UID));
    }

    /**
     * Test adding an invalid person.
     *
     * @dataProvider provideServers
     * @expectedException Horde_Kolab_Server_Exception
     *
     * @return NULL
     */
    public function testAddInvalidPerson($server)
    {
        $result = $server->add($this->objects[1]);
    }

    /**
     * Test handling simple attributes.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testSimpleAttributes($server)
    {
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => ''));
        $this->assertSimpleAttributes($person, $server,
                                      array(
                                      ));
    }

    /**
     * Test handling the postal address.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testHandlingAPostalAddress($server)
    {
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => 'Kolab_Server_OrgPersonTest_123$$$ '));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_SN => 'Kolab_Server_OrgPersonTest_456'),
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => array('Kolab_Server_OrgPersonTest_456$$$ ')));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_SN => 'Kolab_Server_OrgPersonTest_123',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => 'Street 1',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALCODE => '12345',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CITY => 'Nowhere'),
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => array('Kolab_Server_OrgPersonTest_123$$Street 1$12345 Nowhere')));
        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTOFFICEBOX => 'öäü/)(="§%$&§§$\'*',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => null),
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => array('Kolab_Server_OrgPersonTest_123$$öäü/)(="§%\24&§§\24\'*$12345 Nowhere')));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => null,
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALCODE => null,
                                      //FIXME: Why does this need a string?
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => '',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTOFFICEBOX => null,
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CITY => null),
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => array('Kolab_Server_OrgPersonTest_123$$$ ')));
    }


    /**
     * Test handling easy attributes.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testEasyAttributes($server)
    {
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => ''));
        $this->assertEasyAttributes($person, $server,
                                    array(
                                        Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => array(
                                            'Teacher',
                                            '0',
                                            'Something',
                                            null,
                                            '',
                                            array('This', 'That'),
                                        ),
                                        Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_FAX => array(
                                            '123456789',
                                            '+1234567890',
                                            array('1', '2'),
                                            '0',
                                            //FIXME: How to delete?
                                            //null
                                        )
                                    )
        );
    }
}
