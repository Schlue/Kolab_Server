<?php
/**
 * Test the kolabExternalPop3Account object.
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
 * Test the kolabExternalPop3Account object.
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
class Horde_Kolab_Server_Kolabpop3accountTest extends Horde_Kolab_Test_Server
{
    /**
     * Objects used within this test
     *
     * @var array
     */
    private $objects = array(
        /* Default bank account owner */
        array(
            'type' => 'Horde_Kolab_Server_Object_Kolabinetorgperson',
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_GIVENNAME    => 'Frank',
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SN           => 'Mustermann',
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Default account */
        array(
            'type' => 'Horde_Kolab_Server_Object_Kolabpop3account',
            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_MAIL      => 'frank@example.com',
            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER    => 'pop.example.com',
            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_LOGINNAME => 'frank',
            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_PASSWORD  => 'test',
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
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
        $account_data = $this->objects[1];
        $account_data[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID] = $person->getUid();
        $a = new Horde_Kolab_Server_Object_Kolabpop3account($server, null, $account_data);
        $this->assertContains(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_MAIL . '=' . $this->objects[1][Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_MAIL],
                              $a->get(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_UID));
    }

    /**
     * Test adding an invalid Account.
     *
     * @dataProvider provideServers
     * @expectedException Horde_Kolab_Server_Exception
     *
     * @return NULL
     */
    public function testAddInvalidAccount($server)
    {
        $result = $server->add($this->objects[1]);
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
                                   array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
        $account_data = $this->objects[1];
        $account_data[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID] = $person->getUid();
        $account = $this->assertAdd($server, $account_data,
                                    array(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID => $person->getUid()));
        $this->assertEasyAttributes($account, $server,
                                    array(
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_PASSWORD => array(
                                            'something',
                                            'somewhere',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_DESCRIPTION => array(
                                            'something',
                                            'somewhere',
                                            null,
                                            '',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER => array(
                                            'something',
                                            'somewhere',
                                            array('a', 'b'),
                                        ),
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_PORT => array(
                                            '110',
                                            '111',
                                            null,
                                            '',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_USESSL => array(
                                            'TRUE',
                                            'FALSE',
                                            null,
                                            '',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_USETLS => array(
                                            'TRUE',
                                            'FALSE',
                                            null,
                                            '',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_LOGINMETHOD => array(
                                            'something',
                                            'somewhere',
                                            null,
                                            array('a', 'b'),
                                            '',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_CHECKCERTIFICATE => array(
                                            'TRUE',
                                            'FALSE',
                                            null,
                                            '',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_KEEPMAILONSERVER => array(
                                            'TRUE',
                                            'FALSE',
                                            null,
                                            '',
                                        ),
                                    )
        );
    }

    /**
     * Test modifying the attributes required for the UID of the account. This
     * should lead to renaming object.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testModifyUidElements($server)
    {
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
        $account_data = $this->objects[1];
        $account_data[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID] = $person->getUid();
        $account = $server->add($account_data);
        $this->assertNoError($account);

        $account = $server->fetch($account->getUid());
        $this->assertNoError($account);

        $this->assertEquals($this->objects[1][Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER],
                            $account->get(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER));

        $result = $account->save(array(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER => 'pop3s.example.com'));
        $this->assertNoError($result);

        $account = $server->fetch($account->getUid());
        $this->assertNoError($account);

        $this->assertEquals($account->get(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER),
                            'pop3s.example.com');

        $this->assertContains('frank@example.com', $account->getUid());

        $result = $server->delete($account->getUid());
        $this->assertNoError($result);
    }
}
